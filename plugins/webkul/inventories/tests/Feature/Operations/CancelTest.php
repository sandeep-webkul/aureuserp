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

it('cancels a draft delivery', function () {
    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 10]]);

    Inventory::cancelTransfer($operation);

    $operation->refresh();

    expect($operation->state)->toBe(OperationState::CANCELED)
        ->and($operation->moves->first()->state)->toBe(MoveState::CANCELED);
});

it('cancels a confirmed delivery and releases its reservation', function () {
    InventoryHelper::stockUp($this->product, $this->stock, 10);

    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    expect(InventoryHelper::reserved($this->product, $this->stock))->toBe(10.0);

    Inventory::cancelTransfer($operation->refresh());

    $operation->refresh();

    expect($operation->state)->toBe(OperationState::CANCELED)
        ->and($operation->moves->first()->state)->toBe(MoveState::CANCELED);

    expect(InventoryHelper::reserved($this->product, $this->stock))->toBe(0.0)
        ->and(InventoryHelper::onHand($this->product, $this->stock))->toBe(10.0);
});

it('deletes the move lines of a cancelled move', function () {
    InventoryHelper::stockUp($this->product, $this->stock, 10);

    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    expect($operation->refresh()->moves->first()->lines)->toHaveCount(1);

    Inventory::cancelTransfer($operation->refresh());

    expect($operation->refresh()->moves->first()->lines)->toHaveCount(0);
});

it('clears the picked flag when cancelling', function () {
    InventoryHelper::stockUp($this->product, $this->stock, 10);

    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    Inventory::cancelTransfer($operation->refresh());

    expect($operation->refresh()->moves->first()->is_picked)->toBeFalse();
});

it('cancels every move of a multi product operation', function () {
    $other = InventoryHelper::product();

    $operation = InventoryHelper::delivery($this->warehouse, [
        [$this->product, 10],
        [$other, 5],
    ]);

    Inventory::confirmTransfer($operation);

    Inventory::cancelTransfer($operation->refresh());

    $operation->refresh();

    expect($operation->state)->toBe(OperationState::CANCELED)
        ->and($operation->moves->pluck('state')->unique()->all())->toBe([MoveState::CANCELED]);
});

it('refuses to cancel an operation that is already done', function () {
    InventoryHelper::stockUp($this->product, $this->stock, 10);

    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    Inventory::doneTransfer($operation->refresh());

    Inventory::cancelTransfer($operation->refresh());
})->throws(Exception::class);

it('leaves a cancelled operation cancelled when cancelled again', function () {
    $operation = InventoryHelper::delivery($this->warehouse, [[$this->product, 10]]);

    Inventory::cancelTransfer($operation);

    Inventory::cancelTransfer($operation->refresh());

    expect($operation->refresh()->state)->toBe(OperationState::CANCELED);
});
