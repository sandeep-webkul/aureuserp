<?php

namespace Webkul\Barcode\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Webkul\Barcode\Services\OperationCatalog;
use Webkul\Barcode\Services\OperationLookup;
use Webkul\Inventory\Models\Operation;

class Dashboard extends Component
{
    public string $operationBarcode = '';

    public ?string $operationNotice = null;

    public array $matchingOperationIds = [];

    public function openOperation(OperationLookup $lookup)
    {
        $this->operationNotice = null;

        $operations = $lookup->findActiveOperations($this->operationBarcode);

        if ($operations->isEmpty()) {
            $this->matchingOperationIds = [];

            $this->operationNotice = __('barcode::app.operation-search.not-found');

            return null;
        }

        if ($operations->count() === 1) {
            $operation = $operations->first();

            return $this->redirectRoute('barcode.operation', [
                $operation->operationType,
                $operation,
                'scan' => $lookup->normalize($this->operationBarcode),
            ], navigate: true);
        }

        $this->matchingOperationIds = $operations->pluck('id')->all();
        $this->operationNotice = __('barcode::app.operation-search.multiple-found', ['count' => $operations->count()]);

        return null;
    }

    public function updatedOperationBarcode(): void
    {
        if ($this->operationBarcode === '') {
            $this->matchingOperationIds = [];
            
            $this->operationNotice = null;
        }
    }

    public function render(OperationCatalog $catalog): View
    {
        return view('barcode::livewire.dashboard', [
            'matchingOperations' => $this->matchingOperationIds === []
                ? collect()
                : Operation::query()->with(['operationType', 'partner'])->whereIn('id', $this->matchingOperationIds)->get(),
            'operationTypes'      => $catalog->operationTypes(),
        ])->layout('barcode::layouts.app', [
            'title' => __('barcode::app.title'),
        ]);
    }
}
