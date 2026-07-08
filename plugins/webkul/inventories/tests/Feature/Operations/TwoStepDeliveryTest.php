<?php

use Webkul\Inventory\Enums\DeliveryStep;
use Webkul\Inventory\Enums\MoveState;
use Webkul\Inventory\Enums\OperationState;
use Webkul\Inventory\Enums\ProcureMethod;
use Webkul\Inventory\Enums\ReceptionStep;
use Webkul\Inventory\Facades\Inventory;
use Webkul\Inventory\Models\Operation;

require_once __DIR__.'/../../../../support/tests/Helpers/TestBootstrapHelper.php';
require_once __DIR__.'/../../Helpers/InventoryHelper.php';

beforeEach(function () {
    TestBootstrapHelper::ensurePluginInstalled('inventories');

    InventoryHelper::actingAsAdmin();

    $this->warehouse = InventoryHelper::warehouse(ReceptionStep::ONE_STEP, DeliveryStep::TWO_STEPS);
    $this->product = InventoryHelper::product();
    $this->stock = $this->warehouse->lotStockLocation;
    $this->output = $this->warehouse->outputStockLocation;
});

function shipOperation($warehouse): ?Operation
{
    return Operation::query()
        ->where('operation_type_id', $warehouse->out_type_id)
        ->first();
}

it('routes a two step delivery from stock into the output location', function () {
    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 10]]);

    expect($operation->operation_type_id)->toBe($this->warehouse->pick_type_id)
        ->and($operation->source_location_id)->toBe($this->stock->id)
        ->and($operation->destination_location_id)->toBe($this->output->id);
});

it('creates no ship operation before the pick is validated', function () {
    InventoryHelper::stockUp($this->product, $this->stock, 10);

    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    expect(shipOperation($this->warehouse))->toBeNull();
});

it('reserves the pick against stock on confirm', function () {
    InventoryHelper::stockUp($this->product, $this->stock, 10);

    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    $move = $operation->refresh()->moves->first();

    expect($move->state)->toBe(MoveState::ASSIGNED)
        ->and(InventoryHelper::reserved($this->product, $this->stock))->toBe(10.0);
});

it('lands the picked quantity in output and not at the customer', function () {
    InventoryHelper::stockUp($this->product, $this->stock, 10);

    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    Inventory::doneTransfer($operation->refresh());

    expect($operation->refresh()->state)->toBe(OperationState::DONE)
        ->and(InventoryHelper::onHand($this->product, $this->stock))->toBe(0.0)
        ->and(InventoryHelper::onHand($this->product, $this->output))->toBe(10.0);
});

it('pushes a ship operation from output to the customer when the pick is validated', function () {
    InventoryHelper::stockUp($this->product, $this->stock, 10);

    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    Inventory::doneTransfer($operation->refresh());

    $ship = shipOperation($this->warehouse);

    expect($ship)->not->toBeNull()
        ->and($ship->moves)->toHaveCount(1);

    $move = $ship->moves->first();

    expect($move->source_location_id)->toBe($this->output->id)
        ->and($move->destinationLocation->type->value)->toBe('customer')
        ->and($move->procure_method)->toBe(ProcureMethod::MAKE_TO_ORDER)
        ->and((float) $move->product_uom_qty)->toBe(10.0);
});

it('links the ship move back to the pick move as its origin', function () {
    InventoryHelper::stockUp($this->product, $this->stock, 10);

    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    Inventory::doneTransfer($operation->refresh());

    $pickMove = $operation->refresh()->moves->first();
    $shipMove = shipOperation($this->warehouse)->moves->first();

    expect($pickMove->moveDestinations->pluck('id')->all())->toContain($shipMove->id)
        ->and($shipMove->moveOrigins->pluck('id')->all())->toContain($pickMove->id);
});

it('reserves the ship move against the output location', function () {
    InventoryHelper::stockUp($this->product, $this->stock, 10);

    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    Inventory::doneTransfer($operation->refresh());

    $move = shipOperation($this->warehouse)->moves->first();

    expect($move->state)->toBe(MoveState::ASSIGNED)
        ->and((float) $move->quantity)->toBe(10.0)
        ->and($move->lines)->toHaveCount(1);

    expect(InventoryHelper::reserved($this->product, $this->output))->toBe(10.0);
});

it('empties the output location when the ship operation is validated', function () {
    InventoryHelper::stockUp($this->product, $this->stock, 10);

    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    Inventory::doneTransfer($operation->refresh());

    $ship = shipOperation($this->warehouse);

    $customer = $ship->destinationLocation;

    Inventory::doneTransfer($ship->refresh());

    expect($ship->refresh()->state)->toBe(OperationState::DONE)
        ->and(InventoryHelper::onHand($this->product, $this->output))->toBe(0.0)
        ->and(InventoryHelper::reserved($this->product, $this->output))->toBe(0.0)
        ->and(InventoryHelper::onHand($this->product, $customer))->toBe(10.0);
});

it('pushes only the picked quantity when the pick is partially validated', function () {
    InventoryHelper::stockUp($this->product, $this->stock, 10);

    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    InventoryHelper::pick($operation->refresh()->moves->first(), 4);

    Inventory::doneTransfer($operation->refresh());

    $ship = shipOperation($this->warehouse);

    expect((float) $ship->moves->first()->product_uom_qty)->toBe(4.0)
        ->and(InventoryHelper::onHand($this->product, $this->output))->toBe(4.0)
        ->and(InventoryHelper::onHand($this->product, $this->stock))->toBe(6.0);
});
