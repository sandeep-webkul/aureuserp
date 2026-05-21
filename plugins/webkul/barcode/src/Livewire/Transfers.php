<?php

namespace Webkul\Barcode\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Webkul\Barcode\Services\OperationCatalog;
use Webkul\Barcode\Services\OperationLookup;
use Webkul\Inventory\Models\Operation;
use Webkul\Inventory\Models\OperationType;

class Transfers extends Component
{
    public OperationType $operationType;

    public string $search = '';

    public ?string $operationNotice = null;

    public array $matchingOperationIds = [];

    public function mount(OperationType $operationType): void
    {
        $this->operationType = $operationType;
    }

    public function openOperation(OperationLookup $lookup)
    {
        $this->operationNotice = null;

        $operations = $lookup->findActiveOperations($this->search, $this->operationType);

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
                'scan' => $lookup->normalize($this->search),
            ], navigate: true);
        }

        $this->matchingOperationIds = $operations->pluck('id')->all();
        $this->operationNotice = __('barcode::app.operation-search.multiple-found', ['count' => $operations->count()]);

        return null;
    }

    public function updatedSearch(): void
    {
        if ($this->search === '') {
            $this->matchingOperationIds = [];
            $this->operationNotice = null;
        }
    }

    public function render(OperationCatalog $catalog): View
    {
        $transfers = $this->matchingOperationIds !== []
            ? Operation::query()->with(['operationType', 'partner'])->whereIn('id', $this->matchingOperationIds)->get()
            : $catalog->transfers($this->operationType)
                ->filter(function ($operation): bool {
                    if ($this->search === '') {
                        return true;
                    }

                    $search = mb_strtolower($this->search);

                    return str_contains(mb_strtolower($operation->name), $search)
                        || str_contains(mb_strtolower((string) $operation->origin), $search)
                        || str_contains(mb_strtolower((string) $operation->partner?->name), $search);
                });

        return view('barcode::livewire.transfers', [
            'transfers' => $transfers,
        ])->layout('barcode::layouts.app', [
            'title' => $this->operationType->name,
        ]);
    }
}
