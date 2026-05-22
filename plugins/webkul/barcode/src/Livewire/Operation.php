<?php

namespace Webkul\Barcode\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Throwable;
use Webkul\Barcode\Services\OperationActionService;
use Webkul\Barcode\Services\OperationCatalog;
use Webkul\Barcode\Services\ScanResolver;
use Webkul\Inventory\Models\Move;
use Webkul\Inventory\Models\Operation as InventoryOperation;
use Webkul\Inventory\Models\OperationType;

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
            $this->scanResult = app(ScanResolver::class)->resolve($this->operation, (string) request('scan'));
            $this->notice = $this->scanResult['message'];

            if (isset($this->scanResult['move']['id'])) {
                $moveId = (int) $this->scanResult['move']['id'];

                $this->selectedMoveId = $moveId;
                $this->dispatch('barcode-move-located', moveId: $moveId, scannedAt: now()->getTimestampMs());
            }
        }
    }

    public function scan(ScanResolver $resolver): void
    {
        $this->scanResult = $resolver->resolve($this->operation, $this->barcode);
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

    public function updateMoveQuantity(int $moveId, ScanResolver $resolver): void
    {
        // intentionally left blank (logic removed for now)
    }

    public function adjustMoveQuantity(int $moveId, float $amount): void
    {
        $move = Move::query()->findOrFail($moveId);

        abort_unless((int) $move->operation_id === (int) $this->operation->id, 404);

        $quantity = min((float) $move->product_uom_qty, max(0, (float) ($this->countedQuantities[$moveId] ?? 0) + $amount));
        $this->countedQuantities[$moveId] = $quantity;
        $this->countedMoveIds[$moveId] = true;

        $this->selectedMoveId = $moveId;
    }

    public function setMoveQuantity(int $moveId, float $quantity): void
    {
        $move = Move::query()->findOrFail($moveId);

        abort_unless((int) $move->operation_id === (int) $this->operation->id, 404);

        $this->countedQuantities[$moveId] = min((float) $move->product_uom_qty, max(0, $quantity));
        $this->countedMoveIds[$moveId] = true;

        $this->selectedMoveId = $moveId;
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
    }

    public function editMove(int $moveId, ScanResolver $resolver): void
    {
        $move = Move::query()
            ->with(['lines.lot', 'product', 'sourceLocation', 'uom'])
            ->findOrFail($moveId);

        abort_unless((int) $move->operation_id === (int) $this->operation->id, 404);

        $this->editingMoveId = $moveId;
        $this->selectedMoveId = $moveId;
        $this->countedQuantities[$moveId] ??= 0.0;
        $this->editingLotName = $move->lines->first()?->lot?->name ?? $move->lines->first()?->lot_name;
        $this->sourceLocationOptions = $resolver->sourceLocationOptions($move);
    }

    public function discardMoveEdit(): void
    {
        $this->editingMoveId = null;
        $this->editingLotName = null;
        $this->sourceLocationOptions = [];
    }

    public function confirmMoveEdit(ScanResolver $resolver): void
    {
        if (! $this->editingMoveId) {
            return;
        }

        $move = Move::query()->findOrFail($this->editingMoveId);
        $quantity = min((float) $move->product_uom_qty, max(0, (float) ($this->countedQuantities[$move->id] ?? 0)));

        $this->countedQuantities[$move->id] = $quantity;
        $this->countedMoveIds[$move->id] = true;

        $resolver->updateMoveDetails($this->operation, $move, $quantity, $this->editingLotName);
        $resolver->markMoveCounted($this->operation, $move->refresh());

        $this->notice = __('barcode::app.scan.move-counted');
        $this->discardMoveEdit();
        $this->operation->refresh();
    }

    public function markCounted(int $moveId, ScanResolver $resolver): void
    {
        // intentionally left blank (logic removed for now)
    }

    public function executeAction(string $action, OperationActionService $actions, ScanResolver $resolver): void
    {
        try {
            if (in_array($action, ['validate', 'done'], true)) {
                $this->applyCountedQuantities($resolver);
            }

            $this->operation = $actions->execute($this->operation, $action);
            $this->notice = __('barcode::app.actions.completed');
        } catch (Throwable $e) {
            $this->notice = $e->getMessage();
        }
    }

    public function render(OperationCatalog $catalog, OperationActionService $actions): View
    {
        $operation = $catalog->loadOperation($this->operation);

        foreach ($operation->moves as $move) {
            $this->countedQuantities[$move->id] ??= 0.0;
        }

        return view('barcode::livewire.operation', [
            'actions'        => $actions->availableActions($operation),
            'operation'      => $operation,
            'moves'          => $this->filteredMoves($operation),
            'backorderMoves' => $this->backorderMoves($operation),
        ])->layout('barcode::layouts.app', [
            'title' => $operation->name,
        ]);
    }

    protected function filteredMoves(InventoryOperation $operation)
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

    protected function applyCountedQuantities(ScanResolver $resolver): void
    {
        $this->operation->loadMissing(['moves.product', 'moves.uom', 'moves.lines.lot']);

        // intentionally left blank (logic removed for now)
    }
}
