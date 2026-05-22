<?php

namespace Webkul\Barcode\Services;

use Illuminate\Support\Collection;
use Webkul\Inventory\Enums\OperationState;
use Webkul\Inventory\Models\Operation;
use Webkul\Inventory\Models\OperationType;

class OperationCatalog
{
    public function operationTypes(): Collection
    {
        return OperationType::query()
            ->select('inventories_operation_types.*')
            ->selectSub(
                Operation::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('inventories_operations.operation_type_id', 'inventories_operation_types.id')
                    ->whereNotIn('state', [
                        OperationState::DRAFT->value,
                        OperationState::DONE->value,
                        OperationState::CANCELED->value,
                    ]),
                'waiting_count'
            )
            ->orderBy('sort')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (OperationType $operationType): string => $operationType->name.'|'.$operationType->type?->value)
            ->map(function (Collection $operationTypes) {
                $primaryOperationType = $operationTypes->first();
                
                $primaryOperationType->waiting_count = $operationTypes->sum('waiting_count');

                return $primaryOperationType;
            })
            ->values();
    }

    public function transfers(OperationType $operationType): Collection
    {
        return Operation::query()
            ->with(['partner', 'operationType'])
            ->where('operation_type_id', $operationType->id)
            ->whereNotIn('state', [
                OperationState::DRAFT->value,
                OperationState::DONE->value,
                OperationState::CANCELED->value,
            ])
            ->latest('scheduled_at')
            ->latest('id')
            ->limit(200)
            ->get();
    }

    public function loadOperation(Operation $operation): Operation
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
}
