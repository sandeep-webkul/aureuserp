<?php

use Illuminate\Support\Facades\Auth;
use Webkul\Inventory\Enums\DeliveryStep;
use Webkul\Inventory\Enums\LocationType;
use Webkul\Inventory\Enums\MoveState;
use Webkul\Inventory\Enums\MoveType;
use Webkul\Inventory\Enums\OperationState;
use Webkul\Inventory\Enums\ReceptionStep;
use Webkul\Inventory\Models\Location;
use Webkul\Inventory\Models\Move;
use Webkul\Inventory\Models\Operation;
use Webkul\Inventory\Models\OperationType;
use Webkul\Inventory\Models\Product;
use Webkul\Inventory\Models\ProductQuantity;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\UOM;

class InventoryHelper
{
    public static function unitsUom(): UOM
    {
        return UOM::query()->where('name', 'Units')->firstOrFail();
    }

    public static function dozensUom(): UOM
    {
        return UOM::query()->where('name', 'Dozens')->firstOrFail();
    }

    public static function company(): Company
    {
        return Company::query()->firstOrFail();
    }

    public static function actingAsAdmin(): User
    {
        $user = User::query()->firstOrFail();

        Auth::login($user);

        return $user;
    }

    public static function warehouse(
        ReceptionStep $reception = ReceptionStep::ONE_STEP,
        DeliveryStep $delivery = DeliveryStep::ONE_STEP,
    ): Warehouse {
        $warehouse = Warehouse::factory()->create([
            'company_id' => static::company()->id,
            'sort'       => 1,
        ]);

        $warehouse->update([
            'reception_steps' => $reception,
            'delivery_steps'  => $delivery,
            'sort'            => 2,
        ]);

        return $warehouse->refresh();
    }

    public static function sublocation(Location $parent, ?string $name = null): Location
    {
        return Location::factory()->create([
            'name'         => $name ?? fake()->unique()->word(),
            'type'         => LocationType::INTERNAL,
            'parent_id'    => $parent->id,
            'warehouse_id' => $parent->warehouse_id,
            'company_id'   => static::company()->id,
        ]);
    }

    public static function product(array $overrides = []): Product
    {
        $uom = static::unitsUom();

        return Product::factory()->create(array_merge([
            'is_storable' => true,
            'uom_id'      => $uom->id,
            'uom_po_id'   => $uom->id,
            'company_id'  => static::company()->id,
        ], $overrides));
    }

    public static function stockUp(
        Product $product,
        Location $location,
        float $quantity,
        ?int $lotId = null,
        ?int $packageId = null,
    ): ProductQuantity {
        return ProductQuantity::create([
            'product_id'        => $product->id,
            'location_id'       => $location->id,
            'lot_id'            => $lotId,
            'package_id'        => $packageId,
            'quantity'          => $quantity,
            'reserved_quantity' => 0,
            'incoming_at'       => now(),
            'company_id'        => static::company()->id,
        ]);
    }

    public static function operation(OperationType $operationType, array $lines): Operation
    {
        $operation = Operation::create([
            'operation_type_id'       => $operationType->id,
            'source_location_id'      => $operationType->source_location_id,
            'destination_location_id' => $operationType->destination_location_id,
            'state'                   => OperationState::DRAFT,
            'move_type'               => MoveType::DIRECT,
            'scheduled_at'            => now(),
            'company_id'              => static::company()->id,
        ]);

        foreach ($lines as $line) {
            [$product, $demand] = $line;

            $uom = $line[2] ?? null;

            Move::create([
                'name'                    => $product->name,
                'state'                   => MoveState::DRAFT,
                'product_id'              => $product->id,
                'uom_id'                  => $uom?->id ?? $product->uom_id,
                'product_uom_qty'         => $demand,
                'quantity'                => 0,
                'operation_id'            => $operation->id,
                'operation_type_id'       => $operationType->id,
                'source_location_id'      => $operation->source_location_id,
                'destination_location_id' => $operation->destination_location_id,
                'company_id'              => static::company()->id,
            ]);
        }

        return $operation->refresh();
    }

    public static function receipt(Warehouse $warehouse, array $lines): Operation
    {
        return static::operation($warehouse->inType, $lines);
    }

    public static function delivery(Warehouse $warehouse, array $lines): Operation
    {
        $operationType = $warehouse->delivery_steps === DeliveryStep::ONE_STEP
            ? $warehouse->outType
            : $warehouse->pickType;

        return static::operation($operationType, $lines);
    }

    public static function pick(Move $move, float $quantity): Move
    {
        $move->update(['quantity' => $quantity]);

        return $move->refresh();
    }

    public static function backorderOf(Operation $operation): ?Operation
    {
        return Operation::query()->where('back_order_id', $operation->id)->first();
    }

    public static function returnOf(Operation $operation): ?Operation
    {
        return Operation::query()->where('return_id', $operation->id)->first();
    }

    public static function quantOf(Product $product, Location $location, ?int $lotId = null): ?ProductQuantity
    {
        return ProductQuantity::query()
            ->where('product_id', $product->id)
            ->where('location_id', $location->id)
            ->where('lot_id', $lotId)
            ->first();
    }

    public static function onHand(Product $product, Location $location): float
    {
        return (float) ProductQuantity::query()
            ->where('product_id', $product->id)
            ->where('location_id', $location->id)
            ->sum('quantity');
    }

    public static function reserved(Product $product, Location $location): float
    {
        return (float) ProductQuantity::query()
            ->where('product_id', $product->id)
            ->where('location_id', $location->id)
            ->sum('reserved_quantity');
    }

    public static function onHandUnder(Product $product, Location $parent): float
    {
        return (float) static::quantsUnder($product, $parent)->sum('quantity');
    }

    public static function reservedUnder(Product $product, Location $parent): float
    {
        return (float) static::quantsUnder($product, $parent)->sum('reserved_quantity');
    }

    protected static function quantsUnder(Product $product, Location $parent)
    {
        return ProductQuantity::query()
            ->where('product_id', $product->id)
            ->whereHas('location', fn ($query) => $query->where('parent_path', 'like', $parent->parent_path.'%'));
    }

    public static function lineQuantities(Move $move): array
    {
        return $move->lines
            ->pluck('qty')
            ->map(fn ($qty) => (float) $qty)
            ->sort()
            ->values()
            ->all();
    }
}
