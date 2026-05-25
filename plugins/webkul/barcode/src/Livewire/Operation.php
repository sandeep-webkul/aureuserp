<?php

namespace Webkul\Barcode\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Livewire\Component;
use Throwable;
use Webkul\Inventory\Enums\CreateBackorder;
use Webkul\Inventory\Enums\OperationState;
use Webkul\Inventory\Facades\Inventory;
use Webkul\Inventory\Models\Lot;
use Webkul\Inventory\Models\MoveLine;
use Webkul\Inventory\Models\Operation as InventoryOperation;
use Webkul\Inventory\Models\OperationType;
use Webkul\Inventory\Models\Package;
use Webkul\Inventory\Models\Packaging;
use Webkul\Inventory\Models\ProductQuantity;
use Webkul\Product\Models\Product;

class Operation extends Component
{
    public OperationType $operationType;

    public InventoryOperation $operation;

    public string $barcode = '';

    public ?array $scanResult = null;

    public ?string $notice = null;

    public ?int $selectedMoveLineId = null;

    public array $countedMoveLineQuantities = [];

    public array $countedMoveLineIds = [];

    public ?int $editingMoveLineId = null;

    public ?string $editingMoveLineLotName = null;

    public array $moveLineSourceLocationOptions = [];

    public function mount(OperationType $operationType, InventoryOperation $operation): void
    {
        abort_unless((int) $operation->operation_type_id === (int) $operationType->id, 404);

        $this->operationType = $operationType;
        $this->operation = $operation;

        if (request()->filled('scan')) {
            $this->scanResult = $this->resolveScan($this->operation, (string) request('scan'));
            $this->notice = $this->scanResult['message'];

            if (isset($this->scanResult['moveLine']['id'])) {
                $moveLineId = (int) $this->scanResult['moveLine']['id'];

                $this->selectedMoveLineId = $moveLineId;
                $this->dispatch('barcode-move-line-located', moveLineId: $moveLineId, scannedAt: now()->getTimestampMs());
            }
        }
    }

    public function scan(): void
    {
        $this->scanResult = $this->resolveScan($this->operation, $this->barcode);
        $this->notice = $this->scanResult['message'];
        $this->barcode = '';

        if (isset($this->scanResult['moveLine']['id'])) {
            $moveLineId = (int) $this->scanResult['moveLine']['id'];

            $this->selectedMoveLineId = $moveLineId;
            $this->countedMoveLineQuantities[$moveLineId] ??= 0.0;
            $this->dispatch('barcode-move-line-located', moveLineId: $moveLineId, scannedAt: now()->getTimestampMs());
        }

        $this->operation->refresh();
    }

    public function updateMoveLineQuantity(int $moveLineId): void
    {
        $moveLine = MoveLine::query()->findOrFail($moveLineId);
        $quantity = min((float) $moveLine->qty, max(0, (float) ($this->countedMoveLineQuantities[$moveLineId] ?? 0)));

        $this->countedMoveLineQuantities[$moveLineId] = $quantity;
        $this->countedMoveLineIds[$moveLineId] = true;
        $this->notice = __('barcode::app.scan.move-updated');
    }

    public function adjustMoveLineQuantity(int $moveLineId, float $amount): void
    {
        $moveLine = MoveLine::query()->findOrFail($moveLineId);

        abort_unless((int) $moveLine->operation_id === (int) $this->operation->id, 404);

        $quantity = min((float) $moveLine->qty, max(0, (float) ($this->countedMoveLineQuantities[$moveLineId] ?? 0) + $amount));

        $this->countedMoveLineQuantities[$moveLineId] = $quantity;
        $this->countedMoveLineIds[$moveLineId] = true;
        $this->selectedMoveLineId = $quantity > 0 ? $moveLineId : ($this->selectedMoveLineId === $moveLineId ? null : $this->selectedMoveLineId);
    }

    public function setMoveLineQuantity(int $moveLineId, float $quantity): void
    {
        $moveLine = MoveLine::query()->findOrFail($moveLineId);

        abort_unless((int) $moveLine->operation_id === (int) $this->operation->id, 404);

        $this->countedMoveLineQuantities[$moveLineId] = min((float) $moveLine->qty, max(0, $quantity));
        $this->countedMoveLineIds[$moveLineId] = true;
        $this->selectedMoveLineId = $this->countedMoveLineQuantities[$moveLineId] > 0
            ? $moveLineId
            : ($this->selectedMoveLineId === $moveLineId ? null : $this->selectedMoveLineId);
    }

    public function updatedCountedMoveLineQuantities($value, $key): void
    {
        $moveLineId = (int) $key;

        if ($moveLineId <= 0) {
            return;
        }

        $moveLine = MoveLine::query()->find($moveLineId);

        if (! $moveLine || (int) $moveLine->operation_id !== (int) $this->operation->id) {
            return;
        }

        $this->countedMoveLineQuantities[$moveLineId] = min((float) $moveLine->qty, max(0, (float) $value));
        $this->selectedMoveLineId = $this->countedMoveLineQuantities[$moveLineId] > 0
            ? $moveLineId
            : ($this->selectedMoveLineId === $moveLineId ? null : $this->selectedMoveLineId);
    }

    public function editMoveLine(int $moveLineId): void
    {
        $moveLine = MoveLine::query()
            ->with(['lot', 'product', 'sourceLocation', 'uom'])
            ->findOrFail($moveLineId);

        abort_unless((int) $moveLine->operation_id === (int) $this->operation->id, 404);

        $this->editingMoveLineId = $moveLineId;
        $this->selectedMoveLineId = $moveLineId;
        $this->countedMoveLineQuantities[$moveLineId] ??= 0.0;
        $this->editingMoveLineLotName = $moveLine->lot?->name ?? $moveLine->lot_name;
        $this->moveLineSourceLocationOptions = $this->moveLineSourceLocationOptions($moveLine);
    }

    public function discardMoveLineEdit(): void
    {
        $this->editingMoveLineId = null;
        $this->editingMoveLineLotName = null;
        $this->moveLineSourceLocationOptions = [];
    }

    public function confirmMoveLineEdit(): void
    {
        if (! $this->editingMoveLineId) {
            return;
        }

        $moveLine = MoveLine::query()->findOrFail($this->editingMoveLineId);
        $quantity = min((float) $moveLine->qty, max(0, (float) ($this->countedMoveLineQuantities[$moveLine->id] ?? 0)));

        $this->countedMoveLineQuantities[$moveLine->id] = $quantity;
        $this->countedMoveLineIds[$moveLine->id] = true;
        $this->notice = __('barcode::app.scan.move-counted');

        $this->discardMoveLineEdit();
    }

    public function executeAction(string $action): void
    {
        try {
            $this->operation = $this->executeOperationAction($this->operation, $action);
            $this->notice = __('barcode::app.actions.completed');
        } catch (Throwable $e) {
            $this->notice = $e->getMessage();
        }
    }

    public function render(): View
    {
        $operation = $this->loadOperation($this->operation);

        foreach ($operation->moveLines as $moveLine) {
            $this->countedMoveLineQuantities[$moveLine->id] ??= 0.0;
        }

        return view('barcode::livewire.operation', [
            'actions'               => $this->availableActions($operation),
            'operation'             => $operation,
            'moveLines'             => $this->filteredMoveLines($operation),
            'backorderMoveLines'    => $this->backorderMoveLines($operation),
            'shouldAskBackorder'    => $this->shouldAskBackorder($operation),
        ])->layout('barcode::layouts.app', [
            'title' => $operation->name,
        ]);
    }

    protected function filteredMoveLines(InventoryOperation $operation): Collection
    {
        $moveLines = $operation->moveLines->map(function (MoveLine $moveLine): MoveLine {
            $moveLine->product_uom_qty = $moveLine->qty;

            return $moveLine;
        });

        if ($this->barcode === '') {
            return $moveLines;
        }

        $search = mb_strtolower($this->barcode);

        return $moveLines->filter(function (MoveLine $moveLine) use ($search): bool {
            return str_contains(mb_strtolower((string) $moveLine->reference), $search)
                || str_contains(mb_strtolower((string) $moveLine->product?->name), $search)
                || str_contains(mb_strtolower((string) $moveLine->product?->reference), $search)
                || str_contains(mb_strtolower((string) $moveLine->product?->barcode), $search)
                || str_contains(mb_strtolower((string) $moveLine->lot?->name), $search)
                || str_contains(mb_strtolower((string) $moveLine->lot_name), $search);
        });
    }

    protected function backorderMoveLines(InventoryOperation $operation): array
    {
        $result = [];

        foreach ($operation->moveLines as $moveLine) {
            $counted = (float) ($this->countedMoveLineQuantities[$moveLine->id] ?? 0);
            $required = (float) $moveLine->qty;

            if ($counted < $required) {
                $result[] = [
                    'id'        => $moveLine->id,
                    'name'      => $moveLine->product?->name ?? $moveLine->reference,
                    'counted'   => $counted,
                    'required'  => $required,
                    'backorder' => $required - $counted,
                    'uom'       => $moveLine->uom?->name ?? '',
                ];
            }
        }

        return $result;
    }

    protected function shouldAskBackorder(InventoryOperation $operation): bool
    {
        return $operation->operationType?->create_backorder === CreateBackorder::ASK;
    }

    private function loadOperation(InventoryOperation $operation): InventoryOperation
    {
        return $operation->load([
            'operationType',
            'partner',
            'sourceLocation',
            'destinationLocation',
            'moveLines.sourceLocation',
            'moveLines.product',
            'moveLines.uom',
            'moveLines.lot',
            'moveLines.package',
            'moveLines.resultPackage',
            'moveLines.move',
        ]);
    }

    private function availableActions(InventoryOperation $operation): array
    {
        if (in_array($operation->state, [OperationState::DONE, OperationState::CANCELED], true)) {
            return [];
        }

        return [
            ['key' => 'validate', 'label' => __('barcode::app.actions.validate'), 'variant' => 'primary'],
            ['key' => 'cancel', 'label' => __('barcode::app.actions.cancel'), 'variant' => 'danger'],
        ];
    }

    private function executeOperationAction(InventoryOperation $operation, string $action): InventoryOperation
    {
        return match ($action) {
            'validate', 'done' => Inventory::doneTransfer($operation),
            'cancel'           => Inventory::cancelTransfer($operation),
            default            => throw new InvalidArgumentException(__('barcode::app.actions.unsupported')),
        };
    }

    private function resolveScan(InventoryOperation $operation, string $barcode): array
    {
        $barcode = trim($barcode);

        if ($barcode === '') {
            return [
                'matched' => false,
                'message' => __('barcode::app.scan.empty'),
            ];
        }

        if ($operation->name === $barcode) {
            return [
                'matched' => true,
                'type'    => 'operation',
                'message' => __('barcode::app.scan.operation-matched'),
                'record'  => [
                    'id'     => $operation->id,
                    'name'   => $operation->name,
                    'state'  => $operation->state?->value,
                    'origin' => $operation->origin,
                ],
            ];
        }

        if ($product = $this->findProduct($barcode)) {
            return $this->resolveProductMoveLine($operation, $product);
        }

        if ($packaging = $this->findPackaging($barcode)) {
            return $this->resolveProductMoveLine($operation, $packaging->product, 'packaging', [
                'id'      => $packaging->id,
                'name'    => $packaging->name,
                'barcode' => $packaging->barcode,
            ]);
        }

        if ($lot = $this->findLot($barcode)) {
            $moveLine = $operation->moveLines()
                ->with(['product', 'uom', 'sourceLocation', 'lot'])
                ->where('lot_id', $lot->id)
                ->orWhere('lot_name', $lot->name)
                ->first();

            if ($moveLine) {
                return [
                    'matched'  => true,
                    'type'     => 'lot',
                    'message'  => __('barcode::app.scan.move-located'),
                    'moveLine' => $this->moveLinePayload($moveLine),
                    'record'   => [
                        'id'   => $lot->id,
                        'name' => $lot->name,
                    ],
                ];
            }
        }

        if ($package = $this->findPackage($barcode)) {
            $moveLine = $operation->moveLines()
                ->with(['product', 'uom', 'sourceLocation', 'lot'])
                ->where('package_id', $package->id)
                ->orWhere('result_package_id', $package->id)
                ->first();

            if ($moveLine) {
                return [
                    'matched'  => true,
                    'type'     => 'package',
                    'message'  => __('barcode::app.scan.package-matched'),
                    'moveLine' => $this->moveLinePayload($moveLine),
                    'record'   => [
                        'id'   => $package->id,
                        'name' => $package->name,
                    ],
                ];
            }
        }

        return [
            'matched' => false,
            'message' => __('barcode::app.scan.not-found'),
        ];
    }

    private function resolveProductMoveLine(InventoryOperation $operation, Product $product, string $type = 'product', array $record = []): array
    {
        $moveLine = $operation->moveLines()
            ->with(['product', 'uom', 'sourceLocation', 'lot'])
            ->where('product_id', $product->id)
            ->first();

        if (! $moveLine) {
            return [
                'matched' => false,
                'type'    => $type,
                'message' => __('barcode::app.scan.product-not-on-operation'),
                'record'  => [
                    'id'        => $product->id,
                    'name'      => $product->name,
                    'reference' => $product->reference,
                    'barcode'   => $product->barcode,
                    ...$record,
                ],
            ];
        }

        return [
            'matched'  => true,
            'type'     => $type,
            'message'  => __('barcode::app.scan.move-located'),
            'moveLine' => $this->moveLinePayload($moveLine),
            'record'   => [
                'id'        => $product->id,
                'name'      => $product->name,
                'reference' => $product->reference,
                'barcode'   => $product->barcode,
                ...$record,
            ],
        ];
    }

    private function moveLinePayload(MoveLine $moveLine): array
    {
        return [
            'id'          => $moveLine->id,
            'name'        => $moveLine->reference,
            'product'     => $moveLine->product?->name,
            'reference'   => $moveLine->product?->reference,
            'barcode'     => $moveLine->product?->barcode,
            'quantity'    => (float) $moveLine->qty,
            'demand'      => (float) $moveLine->qty,
            'uom'         => $moveLine->uom?->name,
            'is_picked'   => (bool) $moveLine->is_picked,
            'is_complete' => false,
        ];
    }

    private function moveLineSourceLocationOptions(MoveLine $moveLine): array
    {
        $moveLine->loadMissing(['product.uom', 'uom', 'sourceLocation']);

        if (! $moveLine->product || ! $moveLine->sourceLocation) {
            return [];
        }

        return [[
            'location'  => $moveLine->sourceLocation->full_name ?? $moveLine->sourceLocation->name,
            'available' => ProductQuantity::getAvailableQuantity($moveLine->product, $moveLine->sourceLocation),
            'quantity'  => ProductQuantity::query()
                ->where('product_id', $moveLine->product_id)
                ->where('location_id', $moveLine->source_location_id)
                ->sum('quantity'),
            'uom' => $moveLine->uom?->name,
        ]];
    }

    private function findProduct(string $barcode): ?Product
    {
        return Product::query()
            ->where('barcode', $barcode)
            ->orWhere('reference', $barcode)
            ->first();
    }

    private function findPackaging(string $barcode): ?Packaging
    {
        return Packaging::query()
            ->with('product')
            ->where('barcode', $barcode)
            ->first();
    }

    private function findLot(string $barcode): ?Lot
    {
        return Lot::query()
            ->with('product')
            ->where('name', $barcode)
            ->orWhere('reference', $barcode)
            ->first();
    }

    private function findPackage(string $barcode): ?Package
    {
        return Package::query()
            ->where('name', $barcode)
            ->first();
    }
}
