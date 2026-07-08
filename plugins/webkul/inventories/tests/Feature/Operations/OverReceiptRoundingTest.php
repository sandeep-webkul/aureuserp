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

it('receives more than the demanded quantity', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    InventoryHelper::pick($operation->refresh()->moves->first(), 12);

    Inventory::doneTransfer($operation->refresh());

    $operation->refresh();
    $move = $operation->moves->first();

    expect($operation->state)->toBe(OperationState::DONE)
        ->and($move->state)->toBe(MoveState::DONE)
        ->and((float) $move->quantity)->toBe(12.0);

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(12.0);
});

it('creates no backorder when more than the demand is received', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    InventoryHelper::pick($operation->refresh()->moves->first(), 12);

    Inventory::doneTransfer($operation->refresh());

    expect(InventoryHelper::backorderOf($operation))->toBeNull();
});

it('receives an exact decimal quantity', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [[$this->product, 2.5]]);

    Inventory::confirmTransfer($operation);

    $move = $operation->refresh()->moves->first();

    expect((float) $move->product_uom_qty)->toBe(2.5)
        ->and((float) $move->product_qty)->toBe(2.5);

    Inventory::doneTransfer($operation->refresh());

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(2.5);
});

it('reserves a decimal quantity from stock', function () {
    InventoryHelper::stockUp($this->product, $this->stock, 2.5);

    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 1.25]]);

    Inventory::confirmTransfer($operation);

    $move = $operation->refresh()->moves->first();

    expect($move->state)->toBe(MoveState::ASSIGNED)
        ->and((float) $move->quantity)->toBe(1.25)
        ->and((float) $move->lines->first()->qty)->toBe(1.25);

    expect(InventoryHelper::reserved($this->product, $this->stock))->toBe(1.25);
});

it('leaves the decimal remainder in stock after a decimal delivery', function () {
    InventoryHelper::stockUp($this->product, $this->stock, 2.5);

    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 1.25]]);

    Inventory::confirmTransfer($operation);

    Inventory::doneTransfer($operation->refresh());

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(1.25)
        ->and(InventoryHelper::reserved($this->product, $this->stock))->toBe(0.0);
});

it('rounds a partial dozen into whole product units', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [
        [$this->product, 0.5, InventoryHelper::dozensUom()],
    ]);

    $move = $operation->moves->first();

    expect((float) $move->product_uom_qty)->toBe(0.5)
        ->and((float) $move->product_qty)->toBe(6.0);
});

it('stocks a partial dozen as whole units', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [
        [$this->product, 0.5, InventoryHelper::dozensUom()],
    ]);

    Inventory::confirmTransfer($operation);

    Inventory::doneTransfer($operation->refresh());

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(6.0);
});

it('backorders the decimal remainder of a partially picked receipt', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [[$this->product, 2.5]]);

    Inventory::confirmTransfer($operation);

    InventoryHelper::pick($operation->refresh()->moves->first(), 1.25);

    Inventory::doneTransfer($operation->refresh());

    $backorder = InventoryHelper::backorderOf($operation);

    expect($backorder)->not->toBeNull()
        ->and((float) $backorder->moves->first()->product_uom_qty)->toBe(1.25);

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(1.25);
});
