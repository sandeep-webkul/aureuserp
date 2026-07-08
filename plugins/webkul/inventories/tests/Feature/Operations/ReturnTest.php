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

function validatedReceipt($warehouse, $product, float $qty)
{
    $operation = InventoryHelper::receipt($warehouse, [[$product, $qty]]);

    Inventory::confirmTransfer($operation);

    Inventory::doneTransfer($operation->refresh());

    return $operation->refresh();
}

function validatedDelivery($warehouse, $product, float $qty)
{
    InventoryHelper::stockUp($product, $warehouse->lotStockLocation, $qty);

    $operation = InventoryHelper::delivery($warehouse, [[$product, $qty]]);

    Inventory::confirmTransfer($operation);

    Inventory::doneTransfer($operation->refresh());

    return $operation->refresh();
}

it('creates a return operation linked to the original and assigns it', function () {
    $receipt = validatedReceipt($this->warehouse, $this->product, 10);

    $move = $receipt->moves->first();

    $return = Inventory::returnTransfer($receipt, [$move->id => 4]);

    expect($return->state)->toBe(OperationState::ASSIGNED)
        ->and($return->return_id)->toBe($receipt->id)
        ->and($return->moves)->toHaveCount(1);
});

it('swaps the source and destination locations on the return', function () {
    $receipt = validatedReceipt($this->warehouse, $this->product, 10);

    $move = $receipt->moves->first();

    $return = Inventory::returnTransfer($receipt, [$move->id => 4]);

    expect($return->source_location_id)->toBe($receipt->destination_location_id)
        ->and($return->destination_location_id)->toBe($receipt->source_location_id);
});

it('carries the returned quantity and links back to the original move', function () {
    $receipt = validatedReceipt($this->warehouse, $this->product, 10);

    $move = $receipt->moves->first();

    $return = Inventory::returnTransfer($receipt, [$move->id => 4]);

    $returnMove = $return->moves->first();

    expect((float) $returnMove->product_uom_qty)->toBe(4.0)
        ->and((float) $returnMove->quantity)->toBe(4.0)
        ->and($returnMove->state)->toBe(MoveState::ASSIGNED)
        ->and($returnMove->origin_returned_move_id)->toBe($move->id)
        ->and($returnMove->is_picked)->toBeFalse();
});

it('never marks a return move as additional', function () {
    $receipt = validatedReceipt($this->warehouse, $this->product, 10);

    $return = Inventory::returnTransfer($receipt, [$receipt->moves->first()->id => 4]);

    expect($return->moves->first()->additional)->toBeFalse();
});

it('removes the returned quantity from stock when the return is validated', function () {
    $receipt = validatedReceipt($this->warehouse, $this->product, 10);

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(10.0);

    $return = Inventory::returnTransfer($receipt, [$receipt->moves->first()->id => 4]);

    Inventory::confirmTransfer($return);

    Inventory::doneTransfer($return->refresh());

    $return->refresh();

    expect($return->state)->toBe(OperationState::DONE)
        ->and($return->moves->first()->state)->toBe(MoveState::DONE);

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(6.0);
});

it('returns the full received quantity', function () {
    $receipt = validatedReceipt($this->warehouse, $this->product, 10);

    $return = Inventory::returnTransfer($receipt, [$receipt->moves->first()->id => 10]);

    Inventory::confirmTransfer($return);

    Inventory::doneTransfer($return->refresh());

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(0.0);
});

it('returns a delivery back into stock', function () {
    $delivery = validatedDelivery($this->warehouse, $this->product, 10);

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(0.0);

    $return = Inventory::returnTransfer($delivery, [$delivery->moves->first()->id => 3]);

    expect($return->source_location_id)->toBe($delivery->destination_location_id)
        ->and($return->destination_location_id)->toBe($delivery->source_location_id);

    Inventory::confirmTransfer($return);

    Inventory::doneTransfer($return->refresh());

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(3.0);
});

it('leaves the original operation untouched by the return', function () {
    $receipt = validatedReceipt($this->warehouse, $this->product, 10);

    Inventory::returnTransfer($receipt, [$receipt->moves->first()->id => 4]);

    $receipt->refresh();

    expect($receipt->state)->toBe(OperationState::DONE)
        ->and($receipt->moves)->toHaveCount(1)
        ->and((float) $receipt->moves->first()->product_uom_qty)->toBe(10.0);
});
