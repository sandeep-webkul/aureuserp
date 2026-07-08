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

    $this->shelfA = InventoryHelper::sublocation($this->stock, 'Shelf A');
    $this->shelfB = InventoryHelper::sublocation($this->stock, 'Shelf B');
});

it('creates a single move line when the whole demand comes from one location', function () {
    InventoryHelper::stockUp($this->product, $this->shelfA, 10);

    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    $move = $operation->refresh()->moves->first()->refresh();

    expect($move->state)->toBe(MoveState::ASSIGNED)
        ->and($move->lines)->toHaveCount(1)
        ->and(InventoryHelper::lineQuantities($move))->toBe([10.0]);
});

it('creates one move line per source location when stock is split across two locations', function () {
    InventoryHelper::stockUp($this->product, $this->shelfA, 5);
    InventoryHelper::stockUp($this->product, $this->shelfB, 5);

    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    $operation->refresh();
    $move = $operation->moves->first()->refresh();

    expect($operation->state)->toBe(OperationState::ASSIGNED)
        ->and($move->state)->toBe(MoveState::ASSIGNED)
        ->and((float) $move->quantity)->toBe(10.0)
        ->and($move->lines)->toHaveCount(2)
        ->and(InventoryHelper::lineQuantities($move))->toBe([5.0, 5.0]);

    expect(InventoryHelper::reservedUnder($this->product, $this->stock))->toBe(10.0)
        ->and(InventoryHelper::reserved($this->product, $this->shelfA))->toBe(5.0)
        ->and(InventoryHelper::reserved($this->product, $this->shelfB))->toBe(5.0);
});

it('splits a partial draw across two locations and stays fully assigned', function () {
    InventoryHelper::stockUp($this->product, $this->shelfA, 5);
    InventoryHelper::stockUp($this->product, $this->shelfB, 5);

    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 8]]);

    Inventory::confirmTransfer($operation);

    $operation->refresh();
    $move = $operation->moves->first()->refresh();

    expect($operation->state)->toBe(OperationState::ASSIGNED)
        ->and($move->state)->toBe(MoveState::ASSIGNED)
        ->and((float) $move->quantity)->toBe(8.0)
        ->and($move->lines)->toHaveCount(2)
        ->and(InventoryHelper::lineQuantities($move))->toBe([3.0, 5.0]);

    expect(InventoryHelper::reservedUnder($this->product, $this->stock))->toBe(8.0)
        ->and(InventoryHelper::onHandUnder($this->product, $this->stock))->toBe(10.0);
});

it('partially assigns the move when demand exceeds the available quantity', function () {
    InventoryHelper::stockUp($this->product, $this->shelfA, 5);
    InventoryHelper::stockUp($this->product, $this->shelfB, 5);

    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 12]]);

    Inventory::confirmTransfer($operation);

    $operation->refresh();
    $move = $operation->moves->first()->refresh();

    expect($move->state)->toBe(MoveState::PARTIALLY_ASSIGNED)
        ->and($operation->state)->toBe(OperationState::ASSIGNED)
        ->and((float) $move->product_uom_qty)->toBe(12.0)
        ->and((float) $move->quantity)->toBe(10.0)
        ->and($move->lines)->toHaveCount(2)
        ->and(InventoryHelper::lineQuantities($move))->toBe([5.0, 5.0]);

    expect(InventoryHelper::reservedUnder($this->product, $this->stock))->toBe(10.0);
});

it('mirrors the move state onto its move lines when partially assigned', function () {
    InventoryHelper::stockUp($this->product, $this->shelfA, 5);

    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 12]]);

    Inventory::confirmTransfer($operation);

    $move = $operation->refresh()->moves->first()->refresh();

    expect($move->state)->toBe(MoveState::PARTIALLY_ASSIGNED)
        ->and($move->lines->pluck('state')->unique()->all())->toBe([MoveState::PARTIALLY_ASSIGNED]);
});

it('leaves the move confirmed with no move lines when nothing is available', function () {
    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    $operation->refresh();
    $move = $operation->moves->first()->refresh();

    expect($move->state)->toBe(MoveState::CONFIRMED)
        ->and($operation->state)->toBe(OperationState::CONFIRMED)
        ->and((float) $move->quantity)->toBe(0.0)
        ->and($move->lines)->toHaveCount(0);
});

it('gives the first delivery its full demand and only the remainder to the second', function () {
    InventoryHelper::stockUp($this->product, $this->shelfA, 5);
    InventoryHelper::stockUp($this->product, $this->shelfB, 5);

    $first = InventoryHelper::delivery($this->warehouse, [[$this->product, 8]]);

    Inventory::confirmTransfer($first);

    $second = InventoryHelper::delivery($this->warehouse, [[$this->product, 8]]);

    Inventory::confirmTransfer($second);

    $firstMove = $first->refresh()->moves->first()->refresh();
    $secondMove = $second->refresh()->moves->first()->refresh();

    expect($firstMove->state)->toBe(MoveState::ASSIGNED)
        ->and((float) $firstMove->quantity)->toBe(8.0);

    expect($secondMove->state)->toBe(MoveState::PARTIALLY_ASSIGNED)
        ->and((float) $secondMove->quantity)->toBe(2.0)
        ->and($secondMove->lines)->toHaveCount(1);

    expect(InventoryHelper::reservedUnder($this->product, $this->stock))->toBe(10.0)
        ->and(InventoryHelper::onHandUnder($this->product, $this->stock))->toBe(10.0);
});

it('keeps the reserved quantity per location consistent with the move lines', function () {
    InventoryHelper::stockUp($this->product, $this->shelfA, 5);
    InventoryHelper::stockUp($this->product, $this->shelfB, 5);

    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 8]]);

    Inventory::confirmTransfer($operation);

    $move = $operation->refresh()->moves->first()->refresh();

    foreach ($move->lines as $line) {
        expect(InventoryHelper::reserved($this->product, $line->sourceLocation))
            ->toBe((float) $line->qty);
    }
});

it('validates a delivery drawn from two locations and clears both quants', function () {
    InventoryHelper::stockUp($this->product, $this->shelfA, 5);
    InventoryHelper::stockUp($this->product, $this->shelfB, 5);

    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    Inventory::doneTransfer($operation->refresh());

    $operation->refresh();
    $move = $operation->moves->first()->refresh();

    expect($operation->state)->toBe(OperationState::DONE)
        ->and($move->state)->toBe(MoveState::DONE)
        ->and($move->lines)->toHaveCount(2);

    expect(InventoryHelper::onHandUnder($this->product, $this->stock))->toBe(0.0)
        ->and(InventoryHelper::reservedUnder($this->product, $this->stock))->toBe(0.0);
});
