<?php

namespace Webkul\Barcode\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Livewire\Component;
use Throwable;
use Webkul\Inventory\Enums\OperationState;
use Webkul\Inventory\Facades\Inventory;
use Webkul\Inventory\Models\Move;
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

    public ?int $selectedMoveId = null;

    public array $countedQuantities = [];

    public array $countedMoveIds = [];

    public ?int $editingMoveId = null;

    public ?string $editingLotName = null;

    public array $sourceLocationOptions = [];

    public function mount(OperationType $operationType, InventoryOperation $operation): void
    {
        abort_unless((int) $operation->operation_type_id === (int) $operationType->id, 404);

        $this->operationType = $operationType;

        $this->operation = $operation;

        if (request()->filled('scan')) {
            $this->scanResult = $this->resolveScan($this->operation, (string) request('scan'));

            $this->notice = $this->scanResult['message'];

            if (isset($this->scanResult['move']['id'])) {
                $moveId = (int) $this->scanResult['move']['id'];

                $this->selectedMoveId = $moveId;

                $this->dispatch('barcode-move-located', moveId: $moveId, scannedAt: now()->getTimestampMs());
            }
        }
    }

    public function scan(): void
    {
        $this->scanResult = $this->resolveScan($this->operation, $this->barcode);

        $this->notice = $this->scanResult['message'];

        $this->barcode = '';

        if (isset($this->scanResult['move']['id'])) {
            $moveId = (int) $this->scanResult['move']['id'];

            $this->selectedMoveId = $moveId;

            $this->countedQuantities[$moveId] ??= 0.0;

            $this->dispatch('barcode-move-located', moveId: $moveId, scannedAt: now()->getTimestampMs());
        }

        $this->operation->refresh();
    }

    public function updateMoveQuantity(int $moveId): void
    {
        $move = Move::query()->findOrFail($moveId);
        $quantity = min((float) $move->product_uom_qty, max(0, (float) ($this->countedQuantities[$moveId] ?? 0)));

        $this->countedQuantities[$moveId] = $quantity;
        $this->countedMoveIds[$moveId] = true;
        $this->notice = __('barcode::app.scan.move-updated');
    }

    public function adjustMoveQuantity(int $moveId, float $amount): void
    {
        $move = Move::query()->findOrFail($moveId);

        abort_unless((int) $move->operation_id === (int) $this->operation->id, 404);

        $quantity = min((float) $move->product_uom_qty, max(0, (float) ($this->countedQuantities[$moveId] ?? 0) + $amount));

        $this->countedQuantities[$moveId] = $quantity;

        $this->countedMoveIds[$moveId] = true;

        $this->selectedMoveId = $quantity > 0 ? $moveId : ($this->selectedMoveId === $moveId ? null : $this->selectedMoveId);
    }

    public function setMoveQuantity(int $moveId, float $quantity): void
    {
        $move = Move::query()->findOrFail($moveId);

        abort_unless((int) $move->operation_id === (int) $this->operation->id, 404);

        $this->countedQuantities[$moveId] = min((float) $move->product_uom_qty, max(0, $quantity));

        $this->countedMoveIds[$moveId] = true;

        $this->selectedMoveId = $this->countedQuantities[$moveId] > 0 ? $moveId : ($this->selectedMoveId === $moveId ? null : $this->selectedMoveId);
    }

    public function updatedCountedQuantities($value, $key): void
    {
        $moveId = (int) $key;

        if ($moveId <= 0) {
            return;
        }

        $move = Move::query()->find($moveId);

        if (! $move || (int) $move->operation_id !== (int) $this->operation->id) {
            return;
        }

        $this->countedQuantities[$moveId] = min((float) $move->product_uom_qty, max(0, (float) $value));

        $this->selectedMoveId = $this->countedQuantities[$moveId] > 0 ? $moveId : ($this->selectedMoveId === $moveId ? null : $this->selectedMoveId);
    }

    public function editMove(int $moveId): void
    {
        $move = Move::query()
            ->with(['lines.lot', 'product', 'sourceLocation', 'uom'])
            ->findOrFail($moveId);

        abort_unless((int) $move->operation_id === (int) $this->operation->id, 404);

        $this->editingMoveId = $moveId;

        $this->selectedMoveId = $moveId;

        $this->countedQuantities[$moveId] ??= 0.0;

        $this->editingLotName = $move->lines->first()?->lot?->name ?? $move->lines->first()?->lot_name;

        $this->sourceLocationOptions = $this->sourceLocationOptions($move);
    }

    public function discardMoveEdit(): void
    {
        $this->editingMoveId = null;

        $this->editingLotName = null;

        $this->sourceLocationOptions = [];
    }

    public function confirmMoveEdit(): void
    {
        if (! $this->editingMoveId) {
            return;
        }

        $move = Move::query()->findOrFail($this->editingMoveId);

        $quantity = min((float) $move->product_uom_qty, max(0, (float) ($this->countedQuantities[$move->id] ?? 0)));

        $this->countedQuantities[$move->id] = $quantity;

        $this->countedMoveIds[$move->id] = true;

        $this->notice = __('barcode::app.scan.move-counted');

        $this->discardMoveEdit();
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

        foreach ($operation->moves as $move) {
            $this->countedQuantities[$move->id] ??= 0.0;
        }

        return view('barcode::livewire.operation', [
            'actions'        => $this->availableActions($operation),
            'operation'      => $operation,
            'moves'          => $this->filteredMoves($operation),
            'backorderMoves' => $this->backorderMoves($operation),
        ])->layout('barcode::layouts.app', [
            'title' => $operation->name,
        ]);
    }

    protected function filteredMoves(InventoryOperation $operation): Collection
    {
        if ($this->barcode === '') {
            return $operation->moves;
        }

        $search = mb_strtolower($this->barcode);

        return $operation->moves->filter(function (Move $move) use ($search): bool {
            return str_contains(mb_strtolower((string) $move->name), $search)
                || str_contains(mb_strtolower((string) $move->product?->name), $search)
                || str_contains(mb_strtolower((string) $move->product?->reference), $search)
                || str_contains(mb_strtolower((string) $move->product?->barcode), $search);
        });
    }

    protected function backorderMoves(InventoryOperation $operation): array
    {
        $result = [];
        foreach ($operation->moves as $move) {
            $counted = (float) ($this->countedQuantities[$move->id] ?? 0);

            $required = (float) $move->product_uom_qty;

            if ($counted < $required) {
                $result[] = [
                    'name'       => $move->product?->name ?? $move->name,
                    'counted'    => $counted,
                    'required'   => $required,
                    'backorder'  => $required - $counted,
                    'uom'        => $move->uom?->name ?? '',
                ];
            }
        }

        return $result;
    }

    private function loadOperation(InventoryOperation $operation): InventoryOperation
    {
        return $operation->load([
            'operationType',
            'partner',
            'sourceLocation',
            'destinationLocation',
            'moves.product',
            'moves.sourceLocation',
            'moves.uom',
            'moves.productPackaging',
            'moves.lines.lot',
            'moveLines.sourceLocation',
            'moveLines.product',
            'moveLines.uom',
            'moveLines.lot',
            'moveLines.package',
            'moveLines.resultPackage',
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
            return $this->resolveProductMove($operation, $product);
        }

        if ($packaging = $this->findPackaging($barcode)) {
            return $this->resolveProductMove($operation, $packaging->product, 'packaging', [
                'id'      => $packaging->id,
                'name'    => $packaging->name,
                'barcode' => $packaging->barcode,
            ]);
        }

        if ($lot = $this->findLot($barcode)) {
            return $this->resolveProductMove($operation, $lot->product, 'lot', [
                'id'   => $lot->id,
                'name' => $lot->name,
            ]);
        }

        if ($package = $this->findPackage($barcode)) {
            $move = $operation->moves()
                ->whereHas('lines', fn (Builder $query) => $query
                    ->where('package_id', $package->id)
                    ->orWhere('result_package_id', $package->id))
                ->with(['product', 'uom'])
                ->first();

            if ($move) {
                return [
                    'matched' => true,
                    'type'    => 'package',
                    'message' => __('barcode::app.scan.package-matched'),
                    'move'    => $this->movePayload($move),
                    'record'  => [
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

    private function resolveProductMove(InventoryOperation $operation, Product $product, string $type = 'product', array $record = []): array
    {
        $move = $operation->moves()
            ->with(['product', 'uom'])
            ->where('product_id', $product->id)
            ->first();

        if (! $move) {
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
            'matched' => true,
            'type'    => $type,
            'message' => __('barcode::app.scan.move-located'),
            'move'    => $this->movePayload($move),
            'record'  => [
                'id'        => $product->id,
                'name'      => $product->name,
                'reference' => $product->reference,
                'barcode'   => $product->barcode,
                ...$record,
            ],
        ];
    }

    private function movePayload(Move $move): array
    {
        return [
            'id'          => $move->id,
            'name'        => $move->name,
            'product'     => $move->product?->name,
            'reference'   => $move->product?->reference,
            'barcode'     => $move->product?->barcode,
            'quantity'    => (float) $move->quantity,
            'demand'      => (float) $move->product_uom_qty,
            'uom'         => $move->uom?->name,
            'is_picked'   => (bool) $move->is_picked,
            'is_complete' => $move->quantity >= $move->product_uom_qty,
        ];
    }

    private function sourceLocationOptions(Move $move): array
    {
        $move->loadMissing(['product.uom', 'uom', 'sourceLocation']);

        if (! $move->product || ! $move->sourceLocation) {
            return [];
        }

        return [[
            'location'  => $move->sourceLocation->full_name ?? $move->sourceLocation->name,
            'available' => ProductQuantity::getAvailableQuantity($move->product, $move->sourceLocation),
            'quantity'  => ProductQuantity::query()
                ->where('product_id', $move->product_id)
                ->where('location_id', $move->source_location_id)
                ->sum('quantity'),
            'uom' => $move->uom?->name,
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
