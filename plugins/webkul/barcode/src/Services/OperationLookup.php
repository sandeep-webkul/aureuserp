<?php

namespace Webkul\Barcode\Services;

use Illuminate\Support\Collection;
use Webkul\Inventory\Enums\OperationState;
use Webkul\Inventory\Models\Lot;
use Webkul\Inventory\Models\Move;
use Webkul\Inventory\Models\MoveLine;
use Webkul\Inventory\Models\Operation;
use Webkul\Inventory\Models\OperationType;
use Webkul\Inventory\Models\Package;
use Webkul\Inventory\Models\Packaging;
use Webkul\Product\Models\Product;

class OperationLookup
{
    public function findActiveOperation(string $barcode, ?OperationType $operationType = null): ?Operation
    {
        return $this->findActiveOperations($barcode, $operationType)->first();
    }

    public function findActiveOperations(string $barcode, ?OperationType $operationType = null): Collection
    {
        $barcode = $this->normalizeBarcode($barcode);

        if ($barcode === '') {
            return collect();
        }

        $operations = Operation::query()
            ->with('operationType')
            ->when($operationType, fn ($query) => $query->where('operation_type_id', $operationType->id))
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

        return $this->findActiveOperationsByScannedInventory($barcode, $operationType);
    }

    public function normalize(string $barcode): string
    {
        return $this->normalizeBarcode($barcode);
    }

    protected function findActiveOperationsByScannedInventory(string $barcode, ?OperationType $operationType): Collection
    {
        if ($product = $this->findProduct($barcode)) {
            return $this->findActiveOperationsByMoveQuery(
                Move::query()->where('product_id', $product->id),
                $operationType
            );
        }

        if ($packaging = $this->findPackaging($barcode)) {
            return $this->findActiveOperationsByMoveQuery(
                Move::query()
                    ->where('product_packaging_id', $packaging->id)
                    ->orWhere('product_id', $packaging->product_id),
                $operationType
            );
        }

        if ($lot = $this->findLot($barcode)) {
            return $this->findActiveOperationsByMoveLineQuery(
                MoveLine::query()->where('lot_id', $lot->id),
                $operationType
            );
        }

        if ($package = $this->findPackage($barcode)) {
            return $this->findActiveOperationsByMoveLineQuery(
                MoveLine::query()
                    ->where('package_id', $package->id)
                    ->orWhere('result_package_id', $package->id),
                $operationType
            );
        }

        return collect();
    }

    protected function findActiveOperationsByMoveQuery($moveQuery, ?OperationType $operationType): Collection
    {
        return Operation::query()
            ->with('operationType')
            ->whereNotIn('state', [
                OperationState::DRAFT->value,
                OperationState::DONE->value,
                OperationState::CANCELED->value,
            ])
            ->when($operationType, fn ($query) => $query->where('operation_type_id', $operationType->id))
            ->whereIn('id', $moveQuery->select('operation_id'))
            ->latest('scheduled_at')
            ->latest('id')
            ->get();
    }

    protected function findActiveOperationsByMoveLineQuery($moveLineQuery, ?OperationType $operationType): Collection
    {
        return Operation::query()
            ->with('operationType')
            ->whereNotIn('state', [
                OperationState::DRAFT->value,
                OperationState::DONE->value,
                OperationState::CANCELED->value,
            ])
            ->when($operationType, fn ($query) => $query->where('operation_type_id', $operationType->id))
            ->whereIn('id', $moveLineQuery->select('operation_id'))
            ->latest('scheduled_at')
            ->latest('id')
            ->get();
    }

    protected function findProduct(string $barcode): ?Product
    {
        return Product::query()
            ->where('barcode', $barcode)
            ->orWhere('reference', $barcode)
            ->first();
    }

    protected function findPackaging(string $barcode): ?Packaging
    {
        return Packaging::query()
            ->where('barcode', $barcode)
            ->first();
    }

    protected function findLot(string $barcode): ?Lot
    {
        return Lot::query()
            ->where('name', $barcode)
            ->orWhere('reference', $barcode)
            ->first();
    }

    protected function findPackage(string $barcode): ?Package
    {
        return Package::query()
            ->where('name', $barcode)
            ->first();
    }

    protected function normalizeBarcode(string $barcode): string
    {
        $barcode = trim($barcode);

        $barcode = preg_replace('/\s+/', ' ', $barcode) ?: '';
        
        $barcode = preg_replace('/^packing\s+slip\s*/i', '', $barcode) ?: $barcode;

        return trim($barcode, " \t\n\r\0\x0B#");
    }
}
