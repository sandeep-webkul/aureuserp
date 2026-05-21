<?php

namespace Webkul\Barcode\Services;

use Illuminate\Database\Eloquent\Builder;
use Webkul\Inventory\Models\Lot;
use Webkul\Inventory\Models\Move;
use Webkul\Inventory\Models\MoveLine;
use Webkul\Inventory\Models\Operation;
use Webkul\Inventory\Models\Package;
use Webkul\Inventory\Models\Packaging;
use Webkul\Inventory\Models\ProductQuantity;
use Webkul\Product\Models\Product;

class ScanResolver
{
    /**
     * @return array{matched: bool, type?: string, message: string, move?: array<string, mixed>, record?: array<string, mixed>}
     */
    public function resolve(Operation $operation, string $barcode): array
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
                'record'  => $this->operationPayload($operation),
            ];
        }

        if ($product = $this->findProduct($barcode)) {
            return $this->resolveProduct($operation, $product);
        }

        if ($packaging = $this->findPackaging($barcode)) {
            return $this->resolveProduct($operation, $packaging->product, 'packaging', [
                'id'      => $packaging->id,
                'name'    => $packaging->name,
                'barcode' => $packaging->barcode,
            ]);
        }

        if ($lot = $this->findLot($barcode)) {
            return $this->resolveProduct($operation, $lot->product, 'lot', [
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

    public function updateMoveQuantity(Operation $operation, Move $move, float $quantity): Move
    {
        abort_unless((int) $move->operation_id === (int) $operation->id, 404);

        $move->quantity = max(0, $quantity);
        $move->save();

        return $move->refresh()->load(['product', 'uom']);
    }

    public function updateMoveDetails(Operation $operation, Move $move, float $quantity, ?string $lotName = null): Move
    {
        abort_unless((int) $move->operation_id === (int) $operation->id, 404);

        $this->updateMoveQuantity($operation, $move, $quantity);

        $moveLine = $move->lines()->first()
            ?? new MoveLine([
                'move_id'                 => $move->id,
                'operation_id'            => $operation->id,
                'product_id'              => $move->product_id,
                'uom_id'                  => $move->uom_id,
                'source_location_id'      => $move->source_location_id,
                'destination_location_id' => $move->destination_location_id,
                'company_id'              => $move->company_id,
                'scheduled_at'            => $move->scheduled_at ?? now(),
            ]);

        $moveLine->qty = $quantity;
        $moveLine->lot_name = $lotName ?: null;

        if ($lotName) {
            $lot = Lot::query()
                ->where('product_id', $move->product_id)
                ->where('name', $lotName)
                ->first();

            $moveLine->lot_id = $lot?->id;
        }

        $moveLine->save();

        return $move->refresh()->load(['product', 'uom', 'lines.lot']);
    }

    public function markMoveCounted(Operation $operation, Move $move): Move
    {
        abort_unless((int) $move->operation_id === (int) $operation->id, 404);

        $move->is_picked = true;
        $move->save();

        return $move->refresh()->load(['product', 'uom']);
    }

    /**
     * @return array<int, array{location: string, available: float, quantity: float, uom: ?string}>
     */
    public function sourceLocationOptions(Move $move): array
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
            'uom'       => $move->uom?->name,
        ]];
    }

    /**
     * @return array<string, mixed>
     */
    public function movePayload(Move $move): array
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
            ->with('product')
            ->where('barcode', $barcode)
            ->first();
    }

    protected function findLot(string $barcode): ?Lot
    {
        return Lot::query()
            ->with('product')
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

    /**
     * @param  array<string, mixed>  $record
     * @return array{matched: bool, type: string, message: string, move?: array<string, mixed>, record: array<string, mixed>}
     */
    protected function resolveProduct(Operation $operation, Product $product, string $type = 'product', array $record = []): array
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

    /**
     * @return array<string, mixed>
     */
    protected function operationPayload(Operation $operation): array
    {
        return [
            'id'     => $operation->id,
            'name'   => $operation->name,
            'state'  => $operation->state?->value,
            'origin' => $operation->origin,
        ];
    }
}
