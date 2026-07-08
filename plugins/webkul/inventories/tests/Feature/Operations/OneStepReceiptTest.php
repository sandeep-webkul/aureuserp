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

it('creates a one step receipt in draft with a single draft move', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [[$this->product, 10]]);

    expect($operation->state)->toBe(OperationState::DRAFT)
        ->and($operation->moves)->toHaveCount(1);

    $move = $operation->moves->first();

    expect($move->state)->toBe(MoveState::DRAFT)
        ->and((float) $move->product_uom_qty)->toBe(10.0)
        ->and((float) $move->product_qty)->toBe(10.0)
        ->and((float) $move->quantity)->toBe(0.0)
        ->and($move->lines)->toHaveCount(0);
});

it('routes a one step receipt from the supplier location into stock', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [[$this->product, 10]]);

    expect($operation->sourceLocation->type->value)->toBe('supplier')
        ->and($operation->destination_location_id)->toBe($this->stock->id);
});

it('assigns a receipt on confirm because the supplier location bypasses reservation', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    $operation->refresh();
    $move = $operation->moves->first()->refresh();

    expect($operation->state)->toBe(OperationState::ASSIGNED)
        ->and($move->state)->toBe(MoveState::ASSIGNED)
        ->and((float) $move->quantity)->toBe(10.0)
        ->and($move->lines)->toHaveCount(1);

    $line = $move->lines->first();

    expect((float) $line->qty)->toBe(10.0)
        ->and((float) $line->uom_qty)->toBe(10.0);
});

it('reserves nothing in stock while the receipt is only confirmed', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(0.0)
        ->and(InventoryHelper::reserved($this->product, $this->stock))->toBe(0.0);
});

it('moves the quantity into stock when the receipt is validated', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    Inventory::doneTransfer($operation->refresh());

    $operation->refresh();
    $move = $operation->moves->first()->refresh();

    expect($operation->state)->toBe(OperationState::DONE)
        ->and($move->state)->toBe(MoveState::DONE)
        ->and((float) $move->quantity)->toBe(10.0)
        ->and($move->lines->first()->state)->toBe(MoveState::DONE);

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(10.0)
        ->and(InventoryHelper::reserved($this->product, $this->stock))->toBe(0.0);
});

it('debits the supplier location when the receipt is validated', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [[$this->product, 10]]);

    $supplier = $operation->sourceLocation;

    Inventory::confirmTransfer($operation);

    Inventory::doneTransfer($operation->refresh());

    expect(InventoryHelper::onHand($this->product, $supplier))->toBe(-10.0);
});

it('creates no backorder when the full demand is received', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    Inventory::doneTransfer($operation->refresh());

    expect(Webkul\Inventory\Models\Operation::query()->where('back_order_id', $operation->id)->count())->toBe(0);
});

it('converts the move demand into the product uom', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [
        [$this->product, 1, InventoryHelper::dozensUom()],
    ]);

    $move = $operation->moves->first();

    expect((float) $move->product_uom_qty)->toBe(1.0)
        ->and((float) $move->product_qty)->toBe(12.0);
});

it('receives a dozen as twelve units into stock', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [
        [$this->product, 1, InventoryHelper::dozensUom()],
    ]);

    Inventory::confirmTransfer($operation);

    Inventory::doneTransfer($operation->refresh());

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(12.0);
});
