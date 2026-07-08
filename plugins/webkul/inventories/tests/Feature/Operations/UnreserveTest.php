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

function confirmedDelivery($warehouse, $product, float $stock, float $demand)
{
    InventoryHelper::stockUp($product, $warehouse->lotStockLocation, $stock);

    $operation = InventoryHelper::delivery($warehouse, [[$product, $demand]]);

    Inventory::confirmTransfer($operation);

    return $operation->refresh();
}

it('releases the reservation and drops the move back to confirmed', function () {
    $operation = confirmedDelivery($this->warehouse, $this->product, 10, 10);

    Inventory::unreserveMoves($operation->moves);

    $move = $operation->refresh()->moves->first();

    expect($move->state)->toBe(MoveState::CONFIRMED)
        ->and((float) $move->quantity)->toBe(0.0)
        ->and($move->lines)->toHaveCount(0);

    expect(InventoryHelper::reserved($this->product, $this->stock))->toBe(0.0)
        ->and(InventoryHelper::onHand($this->product, $this->stock))->toBe(10.0);
});

it('re-reserves the full demand when availability is checked again', function () {
    $operation = confirmedDelivery($this->warehouse, $this->product, 10, 10);

    Inventory::unreserveMoves($operation->moves);

    Inventory::assignTransfer($operation->refresh());

    $operation->refresh();
    $move = $operation->moves->first();

    expect($operation->state)->toBe(OperationState::ASSIGNED)
        ->and($move->state)->toBe(MoveState::ASSIGNED)
        ->and((float) $move->quantity)->toBe(10.0)
        ->and($move->lines)->toHaveCount(1);

    expect(InventoryHelper::reserved($this->product, $this->stock))->toBe(10.0);
});

it('re-reserves only what is still available after stock shrinks', function () {
    $operation = confirmedDelivery($this->warehouse, $this->product, 10, 10);

    Inventory::unreserveMoves($operation->moves);

    InventoryHelper::quantOf($this->product, $this->stock)->update(['quantity' => 4]);

    Inventory::assignTransfer($operation->refresh());

    $move = $operation->refresh()->moves->first();

    expect($move->state)->toBe(MoveState::PARTIALLY_ASSIGNED)
        ->and((float) $move->quantity)->toBe(4.0);

    expect(InventoryHelper::reserved($this->product, $this->stock))->toBe(4.0);
});

it('does not double reserve when availability is checked twice', function () {
    $operation = confirmedDelivery($this->warehouse, $this->product, 10, 10);

    Inventory::assignTransfer($operation->refresh());

    $move = $operation->refresh()->moves->first();

    expect($move->lines)->toHaveCount(1)
        ->and((float) $move->quantity)->toBe(10.0);

    expect(InventoryHelper::reserved($this->product, $this->stock))->toBe(10.0);
});

it('tops up a partially assigned move when stock arrives', function () {
    $operation = confirmedDelivery($this->warehouse, $this->product, 4, 10);

    expect($operation->moves->first()->state)->toBe(MoveState::PARTIALLY_ASSIGNED);

    InventoryHelper::quantOf($this->product, $this->stock)->update(['quantity' => 10]);

    Inventory::assignTransfer($operation->refresh());

    $move = $operation->refresh()->moves->first();

    expect($move->state)->toBe(MoveState::ASSIGNED)
        ->and((float) $move->quantity)->toBe(10.0);

    expect(InventoryHelper::reserved($this->product, $this->stock))->toBe(10.0);
});

it('frees the stock for another delivery once unreserved', function () {
    $first = confirmedDelivery($this->warehouse, $this->product, 10, 10);

    Inventory::unreserveMoves($first->moves);

    $second = InventoryHelper::delivery($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($second);

    expect($second->refresh()->moves->first()->state)->toBe(MoveState::ASSIGNED)
        ->and(InventoryHelper::reserved($this->product, $this->stock))->toBe(10.0);
});

it('silently skips a validated move because validation marks it picked', function () {
    $operation = confirmedDelivery($this->warehouse, $this->product, 10, 10);

    Inventory::doneTransfer($operation);

    $move = $operation->refresh()->moves->first();

    expect($move->state)->toBe(MoveState::DONE)
        ->and($move->is_picked)->toBeTrue();

    Inventory::unreserveMoves($operation->moves);

    expect($operation->refresh()->moves->first()->state)->toBe(MoveState::DONE)
        ->and(InventoryHelper::onHand($this->product, $this->stock))->toBe(0.0);
});

it('refuses to unreserve a done move that was never picked', function () {
    $operation = confirmedDelivery($this->warehouse, $this->product, 10, 10);

    $operation->moves->first()
        ->forceFill(['state' => MoveState::DONE, 'is_picked' => false])
        ->saveQuietly();

    Inventory::unreserveMoves($operation->refresh()->moves);
})->throws(Exception::class);
