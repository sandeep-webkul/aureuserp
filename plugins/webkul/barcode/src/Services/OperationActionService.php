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
            'validate', 'done' => Inventory::doneTransfer($operation),
            'cancel'           => Inventory::cancelTransfer($operation),
            default            => throw new InvalidArgumentException(__('barcode::app.actions.unsupported')),
        };
    }

    /**
     * @return array<int, array{key: string, label: string, variant: string}>
     */
    public function availableActions(Operation $operation): array
    {
        if ($operation->state === OperationState::DONE) {
            return [];
        }

        if ($operation->state === OperationState::CANCELED) {
            return [];
        }

        $actions = [];

        $actions[] = ['key' => 'validate', 'label' => __('barcode::app.actions.validate'), 'variant' => 'primary'];
        
        $actions[] = ['key' => 'cancel', 'label' => __('barcode::app.actions.cancel'), 'variant' => 'danger'];

        return $actions;
    }

}
