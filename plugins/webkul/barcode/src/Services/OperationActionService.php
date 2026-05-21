<?php

namespace Webkul\Barcode\Services;

use InvalidArgumentException;
use Webkul\Inventory\Enums\OperationState;
use Webkul\Inventory\Facades\Inventory;
use Webkul\Inventory\Models\Operation;

class OperationActionService
{
    public function execute(Operation $operation, string $action): Operation
    {
        return match ($action) {
            'confirm', 'todo'        => $this->confirm($operation),
            'assign', 'availability' => Inventory::assignTransfer($operation),
            'validate', 'done'       => Inventory::doneTransfer($operation),
            'cancel'                 => Inventory::cancelTransfer($operation),
            'return'                 => $this->return($operation),
            default                  => throw new InvalidArgumentException(__('barcode::app.actions.unsupported')),
        };
    }

    /**
     * @return array<int, array{key: string, label: string, variant: string}>
     */
    public function availableActions(Operation $operation): array
    {
        if ($operation->state === OperationState::DONE) {
            return [
                ['key' => 'return', 'label' => __('barcode::app.actions.return'), 'variant' => 'secondary'],
            ];
        }

        if ($operation->state === OperationState::CANCELED) {
            return [];
        }

        $actions = [];

        if ($operation->state === OperationState::DRAFT) {
            $actions[] = ['key' => 'confirm', 'label' => __('barcode::app.actions.confirm'), 'variant' => 'secondary'];
        }

        if (in_array($operation->state, [OperationState::CONFIRMED, OperationState::ASSIGNED], true)) {
            $actions[] = ['key' => 'assign', 'label' => __('barcode::app.actions.check-availability'), 'variant' => 'secondary'];
        }

        $actions[] = ['key' => 'validate', 'label' => __('barcode::app.actions.validate'), 'variant' => 'primary'];
        $actions[] = ['key' => 'cancel', 'label' => __('barcode::app.actions.cancel'), 'variant' => 'danger'];

        return $actions;
    }

    protected function confirm(Operation $operation): Operation
    {
        if (! $operation->moves()->exists()) {
            throw new InvalidArgumentException(__('barcode::app.actions.no-moves'));
        }

        return Inventory::confirmTransfer($operation);
    }

    protected function return(Operation $operation): Operation
    {
        $moveQuantities = $operation->moves()
            ->pluck('quantity', 'id')
            ->map(fn ($quantity): float => (float) $quantity)
            ->filter(fn (float $quantity): bool => $quantity > 0)
            ->all();

        if ($moveQuantities === []) {
            throw new InvalidArgumentException(__('barcode::app.actions.no-return-quantities'));
        }

        return Inventory::returnTransfer($operation, $moveQuantities);
    }
}
