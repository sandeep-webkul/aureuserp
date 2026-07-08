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

function partiallyPickedDelivery($warehouse, $product, float $stock, float $demand, float $picked)
{
    InventoryHelper::stockUp($product, $warehouse->lotStockLocation, $stock);

    $operation = InventoryHelper::delivery($warehouse, [[$product, $demand]]);

    Inventory::confirmTransfer($operation);

    InventoryHelper::pick($operation->refresh()->moves->first(), $picked);

    return $operation->refresh();
}

it('creates a backorder for the unpicked remainder', function () {
    $operation = partiallyPickedDelivery($this->warehouse, $this->product, 10, 10, 6);

    Inventory::doneTransfer($operation);

    $operation->refresh();

    expect($operation->state)->toBe(OperationState::DONE);

    $backorder = InventoryHelper::backorderOf($operation);

    expect($backorder)->not->toBeNull()
        ->and($backorder->back_order_id)->toBe($operation->id)
        ->and($backorder->moves)->toHaveCount(1);
});

it('splits the demand between the validated move and the backorder move', function () {
    $operation = partiallyPickedDelivery($this->warehouse, $this->product, 10, 10, 6);

    Inventory::doneTransfer($operation);

    $doneMove = $operation->refresh()->moves->first();

    expect($doneMove->state)->toBe(MoveState::DONE)
        ->and((float) $doneMove->product_uom_qty)->toBe(6.0)
        ->and((float) $doneMove->quantity)->toBe(6.0);

    $backorderMove = InventoryHelper::backorderOf($operation)->moves->first();

    expect((float) $backorderMove->product_uom_qty)->toBe(4.0)
        ->and($backorderMove->state)->not->toBe(MoveState::DONE);
});

it('never marks a backorder move as additional', function () {
    $operation = partiallyPickedDelivery($this->warehouse, $this->product, 10, 10, 6);

    Inventory::doneTransfer($operation);

    $backorderMove = InventoryHelper::backorderOf($operation)->moves->first();

    expect($backorderMove->additional)->toBeFalse();
});

it('leaves the validated operation with exactly one move after the split', function () {
    $operation = partiallyPickedDelivery($this->warehouse, $this->product, 10, 10, 6);

    Inventory::doneTransfer($operation);

    expect($operation->refresh()->moves)->toHaveCount(1);
});

it('moves only the picked quantity out of stock and reserves the rest for the backorder', function () {
    $operation = partiallyPickedDelivery($this->warehouse, $this->product, 10, 10, 6);

    Inventory::doneTransfer($operation);

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(4.0)
        ->and(InventoryHelper::reserved($this->product, $this->stock))->toBe(4.0);
});

it('reserves the backorder immediately when the operation type reserves at confirm', function () {
    $operation = partiallyPickedDelivery($this->warehouse, $this->product, 10, 10, 6);

    Inventory::doneTransfer($operation);

    $backorderMove = InventoryHelper::backorderOf($operation)->moves->first();

    expect($backorderMove->state)->toBe(MoveState::ASSIGNED)
        ->and((float) $backorderMove->quantity)->toBe(4.0);
});

it('creates no backorder when validating with cancelBackOrder', function () {
    $operation = partiallyPickedDelivery($this->warehouse, $this->product, 10, 10, 6);

    Inventory::doneTransfer($operation, cancelBackOrder: true);

    $operation->refresh();

    expect($operation->state)->toBe(OperationState::DONE)
        ->and(InventoryHelper::backorderOf($operation))->toBeNull();
});

it('keeps the original demand on the move when the backorder is cancelled', function () {
    $operation = partiallyPickedDelivery($this->warehouse, $this->product, 10, 10, 6);

    Inventory::doneTransfer($operation, cancelBackOrder: true);

    $move = $operation->refresh()->moves->first();

    expect($move->state)->toBe(MoveState::DONE)
        ->and((float) $move->product_uom_qty)->toBe(10.0)
        ->and((float) $move->quantity)->toBe(6.0);
});

it('releases the unpicked reservation when the backorder is cancelled', function () {
    $operation = partiallyPickedDelivery($this->warehouse, $this->product, 10, 10, 6);

    Inventory::doneTransfer($operation, cancelBackOrder: true);

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(4.0)
        ->and(InventoryHelper::reserved($this->product, $this->stock))->toBe(0.0);
});

it('refuses to validate an operation with no picked quantity', function () {
    InventoryHelper::stockUp($this->product, $this->stock, 10);

    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    InventoryHelper::pick($operation->refresh()->moves->first(), 0);

    Inventory::doneTransfer($operation->refresh());
})->throws(Exception::class);

it('creates a backorder of a backorder when the remainder is again partially picked', function () {
    $operation = partiallyPickedDelivery($this->warehouse, $this->product, 10, 10, 6);

    Inventory::doneTransfer($operation);

    $backorder = InventoryHelper::backorderOf($operation);

    InventoryHelper::pick($backorder->moves->first(), 1);

    Inventory::doneTransfer($backorder->refresh());

    $second = InventoryHelper::backorderOf($backorder);

    expect($second)->not->toBeNull()
        ->and((float) $second->moves->first()->product_uom_qty)->toBe(3.0)
        ->and($second->moves->first()->additional)->toBeFalse();
});

it('creates a backorder on a partially available delivery', function () {
    InventoryHelper::stockUp($this->product, $this->stock, 4);

    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    $move = $operation->refresh()->moves->first();

    expect($move->state)->toBe(MoveState::PARTIALLY_ASSIGNED)
        ->and((float) $move->quantity)->toBe(4.0);

    Inventory::doneTransfer($operation->refresh());

    $backorder = InventoryHelper::backorderOf($operation);

    expect($backorder)->not->toBeNull()
        ->and((float) $backorder->moves->first()->product_uom_qty)->toBe(6.0);

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(0.0);
});
