<?php

namespace Webkul\Barcode\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;
use Webkul\Inventory\Enums\OperationState;
use Webkul\Inventory\Models\Lot;
use Webkul\Inventory\Models\Move;
use Webkul\Inventory\Models\MoveLine;
use Webkul\Inventory\Models\Operation;
use Webkul\Inventory\Models\OperationType;
use Webkul\Inventory\Models\Package;
use Webkul\Inventory\Models\Packaging;
use Webkul\Product\Models\Product;

class Dashboard extends Component
{
    public string $operationBarcode = '';

    public ?string $operationNotice = null;

    public array $matchingOperationIds = [];

    public function openOperation()
    {
        $this->operationNotice = null;

        $operations = $this->findMatchingOperations($this->operationBarcode);

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
                'scan' => $this->normalizeBarcode($this->operationBarcode),
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

    public function render(): View
    {
        return view('barcode::livewire.dashboard', [
            'matchingOperations' => $this->matchingOperationIds === []
                ? collect()
                : Operation::query()->with(['operationType', 'partner'])->whereIn('id', $this->matchingOperationIds)->get(),
            'operationTypes'     => $this->operationTypes(),
        ])->layout('barcode::layouts.app', [
            'title' => __('barcode::app.title'),
        ]);
    }

    private function operationTypes(): Collection
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

    private function findMatchingOperations(string $barcode): Collection
    {
        $barcode = $this->normalizeBarcode($barcode);

        if ($barcode === '') {
            return collect();
        }

        $operations = Operation::query()
            ->with('operationType')
            ->whereNotIn('state', [
                OperationState::DRAFT->value,
                OperationState::DONE->value,
                OperationState::CANCELED->value,
            ])
            ->where(function ($query) use ($barcode): void {
                $query->whereRaw('LOWER(name) = ?', [mb_strtolower($barcode)])
                    ->orWhereRaw('LOWER(origin) = ?', [mb_strtolower($barcode)])
                    ->orWhere('id', $barcode);
            })
            ->latest('scheduled_at')
            ->latest('id')
            ->get();

        if ($operations->isNotEmpty()) {
            return $operations;
        }

        return $this->findMatchingOperationsByInventory($barcode);
    }

    private function findMatchingOperationsByInventory(string $barcode): Collection
    {
        if ($product = $this->findProduct($barcode)) {
            return $this->findOperationsByMoveQuery(
                Move::query()->where('product_id', $product->id)
            );
        }

        if ($packaging = $this->findPackaging($barcode)) {
            return $this->findOperationsByMoveQuery(
                Move::query()
                    ->where('product_packaging_id', $packaging->id)
                    ->orWhere('product_id', $packaging->product_id)
            );
        }

        if ($lot = $this->findLot($barcode)) {
            return $this->findOperationsByMoveLineQuery(
                MoveLine::query()->where('lot_id', $lot->id)
            );
        }

        if ($package = $this->findPackage($barcode)) {
            return $this->findOperationsByMoveLineQuery(
                MoveLine::query()
                    ->where('package_id', $package->id)
                    ->orWhere('result_package_id', $package->id)
            );
        }

        return collect();
    }

    private function findOperationsByMoveQuery($moveQuery): Collection
    {
        return Operation::query()
            ->with('operationType')
            ->whereNotIn('state', [
                OperationState::DRAFT->value,
                OperationState::DONE->value,
                OperationState::CANCELED->value,
            ])
            ->whereIn('id', $moveQuery->select('operation_id'))
            ->latest('scheduled_at')
            ->latest('id')
            ->get();
    }

    private function findOperationsByMoveLineQuery($moveLineQuery): Collection
    {
        return Operation::query()
            ->with('operationType')
            ->whereNotIn('state', [
                OperationState::DRAFT->value,
                OperationState::DONE->value,
                OperationState::CANCELED->value,
            ])
            ->whereIn('id', $moveLineQuery->select('operation_id'))
            ->latest('scheduled_at')
            ->latest('id')
            ->get();
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
            ->where('barcode', $barcode)
            ->first();
    }

    private function findLot(string $barcode): ?Lot
    {
        return Lot::query()
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

    private function normalizeBarcode(string $barcode): string
    {
        $barcode = trim($barcode);
        $barcode = preg_replace('/\s+/', ' ', $barcode) ?: '';
        $barcode = preg_replace('/^packing\s+slip\s*/i', '', $barcode) ?: $barcode;

        return trim($barcode, " \t\n\r\0\x0B#");
    }
}
