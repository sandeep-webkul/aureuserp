<?php

use Webkul\Inventory\Enums\MoveState;
use Webkul\Inventory\Enums\OperationState;
use Webkul\Inventory\Facades\Inventory;

require_once __DIR__.'/../../../../support/tests/Helpers/TestBootstrapHelper.php';
require_once __DIR__.'/../../Helpers/InventoryHelper.php';

beforeEach(function () {
    TestBootstrapHelper::ensurePluginInstalled('inventories');

    InventoryHelper::actingAsAdmin();

    $this->warehouse = InventoryHelper::warehouse();
    $this->product = InventoryHelper::product();
    $this->stock = $this->warehouse->lotStockLocation;
});

it('creates a one step delivery in draft with a single draft move', function () {
    InventoryHelper::stockUp($this->product, $this->stock, 10);

    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 10]]);

    expect($operation->state)->toBe(OperationState::DRAFT)
        ->and($operation->moves)->toHaveCount(1);

    $move = $operation->moves->first();

    expect($move->state)->toBe(MoveState::DRAFT)
        ->and((float) $move->product_uom_qty)->toBe(10.0)
        ->and((float) $move->quantity)->toBe(0.0)
        ->and($move->lines)->toHaveCount(0);
});

it('routes a one step delivery from stock to the customer location', function () {
    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 10]]);

    expect($operation->source_location_id)->toBe($this->stock->id)
        ->and($operation->destinationLocation->type->value)->toBe('customer');
});

it('reserves the full demand from stock on confirm when quantity is available', function () {
    InventoryHelper::stockUp($this->product, $this->stock, 10);

    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    $operation->refresh();
    $move = $operation->moves->first()->refresh();

    expect($operation->state)->toBe(OperationState::ASSIGNED)
        ->and($move->state)->toBe(MoveState::ASSIGNED)
        ->and((float) $move->quantity)->toBe(10.0)
        ->and($move->lines)->toHaveCount(1);

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(10.0)
        ->and(InventoryHelper::reserved($this->product, $this->stock))->toBe(10.0);
});

it('creates a move line pointing at the stock source location', function () {
    InventoryHelper::stockUp($this->product, $this->stock, 10);

    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    $line = $operation->refresh()->moves->first()->lines->first();

    expect($line->source_location_id)->toBe($this->stock->id)
        ->and((float) $line->qty)->toBe(10.0)
        ->and((float) $line->uom_qty)->toBe(10.0)
        ->and($line->state)->toBe(MoveState::ASSIGNED);
});

it('releases the reservation and removes the stock when the delivery is validated', function () {
    InventoryHelper::stockUp($this->product, $this->stock, 10);

    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    Inventory::doneTransfer($operation->refresh());

    $operation->refresh();
    $move = $operation->moves->first()->refresh();

    expect($operation->state)->toBe(OperationState::DONE)
        ->and($move->state)->toBe(MoveState::DONE)
        ->and((float) $move->quantity)->toBe(10.0);

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(0.0)
        ->and(InventoryHelper::reserved($this->product, $this->stock))->toBe(0.0);
});

it('credits the customer location when the delivery is validated', function () {
    InventoryHelper::stockUp($this->product, $this->stock, 10);

    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 10]]);

    $customer = $operation->destinationLocation;

    Inventory::confirmTransfer($operation);

    Inventory::doneTransfer($operation->refresh());

    expect(InventoryHelper::onHand($this->product, $customer))->toBe(10.0);
});

it('leaves the remaining stock untouched when delivering part of it', function () {
    InventoryHelper::stockUp($this->product, $this->stock, 25);

    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    expect(InventoryHelper::reserved($this->product, $this->stock))->toBe(10.0);

    Inventory::doneTransfer($operation->refresh());

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(15.0)
        ->and(InventoryHelper::reserved($this->product, $this->stock))->toBe(0.0);
});

it('creates no backorder when the full demand is delivered', function () {
    InventoryHelper::stockUp($this->product, $this->stock, 10);

    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    Inventory::doneTransfer($operation->refresh());

    expect(Webkul\Inventory\Models\Operation::query()->where('back_order_id', $operation->id)->count())->toBe(0);
});
