<?php

use Webkul\Inventory\Enums\MoveState;
use Webkul\Inventory\Enums\OperationState;
use Webkul\Inventory\Facades\Inventory;
use Webkul\Inventory\Models\Operation;

require_once __DIR__.'/../../../../support/tests/Helpers/TestBootstrapHelper.php';
require_once __DIR__.'/../../Helpers/InventoryHelper.php';

beforeEach(function () {
    TestBootstrapHelper::ensurePluginInstalled('inventories');

    InventoryHelper::actingAsAdmin();

    $this->warehouse = InventoryHelper::warehouse();
    $this->product = InventoryHelper::product();
    $this->stock = $this->warehouse->lotStockLocation;
});

function validatedOneStepReceipt($warehouse, $product, float $demand, ?float $picked = null): Operation
{
    $operation = InventoryHelper::receipt($warehouse, [[$product, $demand]]);

    Inventory::confirmTransfer($operation);

    if ($picked !== null) {
        InventoryHelper::pick($operation->refresh()->moves->first(), $picked);
    }

    Inventory::doneTransfer($operation->refresh());

    return $operation->refresh();
}

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

it('routes a one step receipt from the supplier location straight into stock', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [[$this->product, 10]]);

    expect($operation->operation_type_id)->toBe($this->warehouse->in_type_id)
        ->and($operation->sourceLocation->type->value)->toBe('supplier')
        ->and($operation->destination_location_id)->toBe($this->stock->id);
});

it('assigns a receipt on confirm because the supplier location bypasses reservation', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    $operation->refresh();
    $move = $operation->moves->first();

    expect($operation->state)->toBe(OperationState::ASSIGNED)
        ->and($move->state)->toBe(MoveState::ASSIGNED)
        ->and((float) $move->quantity)->toBe(10.0)
        ->and($move->lines)->toHaveCount(1)
        ->and((float) $move->lines->first()->qty)->toBe(10.0);
});

it('reserves nothing in stock while the receipt is only confirmed', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(0.0)
        ->and(InventoryHelper::reserved($this->product, $this->stock))->toBe(0.0);
});

it('unreserves a receipt move back to confirmed without touching any quant', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    Inventory::unreserveMoves($operation->refresh()->moves);

    $move = $operation->refresh()->moves->first();

    expect($move->state)->toBe(MoveState::CONFIRMED)
        ->and($move->lines)->toHaveCount(0);

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(0.0);
});

it('moves the quantity into stock when the receipt is validated', function () {
    $operation = validatedOneStepReceipt($this->warehouse, $this->product, 10);

    $move = $operation->moves->first();

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
    $operation = validatedOneStepReceipt($this->warehouse, $this->product, 10);

    expect(InventoryHelper::backorderOf($operation))->toBeNull();
});

it('backorders the unreceived remainder', function () {
    $operation = validatedOneStepReceipt($this->warehouse, $this->product, 10, 4);

    $backorder = InventoryHelper::backorderOf($operation);

    expect($backorder)->not->toBeNull()
        ->and($backorder->back_order_id)->toBe($operation->id)
        ->and($backorder->operation_type_id)->toBe($this->warehouse->in_type_id)
        ->and((float) $backorder->moves->first()->product_uom_qty)->toBe(6.0)
        ->and($backorder->moves->first()->additional)->toBeFalse();

    $doneMove = $operation->moves->first();

    expect($doneMove->state)->toBe(MoveState::DONE)
        ->and((float) $doneMove->product_uom_qty)->toBe(4.0)
        ->and((float) $doneMove->quantity)->toBe(4.0);

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(4.0);
});

it('creates a backorder of a backorder when the remainder is again partially received', function () {
    $operation = validatedOneStepReceipt($this->warehouse, $this->product, 10, 4);

    $backorder = InventoryHelper::backorderOf($operation);

    InventoryHelper::pick($backorder->moves->first(), 2);

    Inventory::doneTransfer($backorder->refresh());

    $second = InventoryHelper::backorderOf($backorder->refresh());

    expect($second)->not->toBeNull()
        ->and((float) $second->moves->first()->product_uom_qty)->toBe(4.0);

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(6.0);
});

it('creates no backorder when validating with cancelBackOrder', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    InventoryHelper::pick($operation->refresh()->moves->first(), 4);

    Inventory::doneTransfer($operation->refresh(), cancelBackOrder: true);

    $operation->refresh();
    $move = $operation->moves->first();

    expect($operation->state)->toBe(OperationState::DONE)
        ->and(InventoryHelper::backorderOf($operation))->toBeNull()
        ->and((float) $move->product_uom_qty)->toBe(10.0)
        ->and((float) $move->quantity)->toBe(4.0);

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(4.0);
});

it('cancels a draft receipt', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [[$this->product, 10]]);

    Inventory::cancelTransfer($operation);

    $operation->refresh();

    expect($operation->state)->toBe(OperationState::CANCELED)
        ->and($operation->moves->first()->state)->toBe(MoveState::CANCELED);
});

it('cancels a confirmed receipt and stocks nothing', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    Inventory::cancelTransfer($operation->refresh());

    $operation->refresh();
    $move = $operation->moves->first();

    expect($operation->state)->toBe(OperationState::CANCELED)
        ->and($move->state)->toBe(MoveState::CANCELED)
        ->and($move->lines)->toHaveCount(0)
        ->and($move->is_picked)->toBeFalse();

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(0.0);
});

it('refuses to cancel a receipt that is already done', function () {
    $operation = validatedOneStepReceipt($this->warehouse, $this->product, 10);

    Inventory::cancelTransfer($operation->refresh());
})->throws(Exception::class);

it('leaves a cancelled receipt cancelled when cancelled again', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [[$this->product, 10]]);

    Inventory::cancelTransfer($operation);

    Inventory::cancelTransfer($operation->refresh());

    expect($operation->refresh()->state)->toBe(OperationState::CANCELED);
});

it('returns a receipt back to the supplier and removes the quantity from stock', function () {
    $operation = validatedOneStepReceipt($this->warehouse, $this->product, 10);

    $return = Inventory::returnTransfer($operation, [$operation->moves->first()->id => 4]);

    expect($return->return_id)->toBe($operation->id)
        ->and($return->state)->toBe(OperationState::ASSIGNED)
        ->and($return->source_location_id)->toBe($this->stock->id)
        ->and($return->destinationLocation->type->value)->toBe('supplier');

    $returnMove = $return->moves->first();

    expect((float) $returnMove->product_uom_qty)->toBe(4.0)
        ->and((float) $returnMove->quantity)->toBe(4.0)
        ->and($returnMove->origin_returned_move_id)->toBe($operation->moves->first()->id)
        ->and($returnMove->additional)->toBeFalse();

    Inventory::doneTransfer($return->refresh());

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(6.0);
});

it('returns the full received quantity', function () {
    $operation = validatedOneStepReceipt($this->warehouse, $this->product, 10);

    $return = Inventory::returnTransfer($operation, [$operation->moves->first()->id => 10]);

    Inventory::doneTransfer($return->refresh());

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(0.0);
});

it('leaves the original receipt untouched by the return', function () {
    $operation = validatedOneStepReceipt($this->warehouse, $this->product, 10);

    Inventory::returnTransfer($operation, [$operation->moves->first()->id => 4]);

    $operation->refresh();

    expect($operation->state)->toBe(OperationState::DONE)
        ->and($operation->moves)->toHaveCount(1)
        ->and((float) $operation->moves->first()->product_uom_qty)->toBe(10.0);
});

it('receives more than the demanded quantity without a backorder', function () {
    $operation = validatedOneStepReceipt($this->warehouse, $this->product, 10, 12);

    expect($operation->state)->toBe(OperationState::DONE)
        ->and((float) $operation->moves->first()->quantity)->toBe(12.0)
        ->and(InventoryHelper::backorderOf($operation))->toBeNull();

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(12.0);
});

it('receives an exact decimal quantity', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [[$this->product, 2.5]]);

    $move = $operation->moves->first();

    expect((float) $move->product_uom_qty)->toBe(2.5)
        ->and((float) $move->product_qty)->toBe(2.5);

    Inventory::confirmTransfer($operation);

    Inventory::doneTransfer($operation->refresh());

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(2.5);
});

it('backorders the decimal remainder of a partially received receipt', function () {
    $operation = validatedOneStepReceipt($this->warehouse, $this->product, 2.5, 1.25);

    $backorder = InventoryHelper::backorderOf($operation);

    expect($backorder)->not->toBeNull()
        ->and((float) $backorder->moves->first()->product_uom_qty)->toBe(1.25);

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(1.25);
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

it('rounds a partial dozen into whole product units', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [
        [$this->product, 0.5, InventoryHelper::dozensUom()],
    ]);

    $move = $operation->moves->first();

    expect((float) $move->product_uom_qty)->toBe(0.5)
        ->and((float) $move->product_qty)->toBe(6.0);

    Inventory::confirmTransfer($operation);

    Inventory::doneTransfer($operation->refresh());

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(6.0);
});

it('creates one move per product on a multi product receipt', function () {
    $other = InventoryHelper::product();

    $operation = InventoryHelper::receipt($this->warehouse, [
        [$this->product, 10],
        [$other, 4],
    ]);

    expect($operation->moves)->toHaveCount(2);

    Inventory::confirmTransfer($operation);

    Inventory::doneTransfer($operation->refresh());

    $operation->refresh();

    expect($operation->state)->toBe(OperationState::DONE)
        ->and($operation->moves->pluck('state')->unique()->all())->toBe([MoveState::DONE]);

    expect(InventoryHelper::onHand($this->product, $this->stock))->toBe(10.0)
        ->and(InventoryHelper::onHand($other, $this->stock))->toBe(4.0);
});

it('merges two moves for the same product into one on confirm', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [
        [$this->product, 4],
        [$this->product, 6],
    ]);

    expect($operation->moves)->toHaveCount(2);

    Inventory::confirmTransfer($operation);

    $operation->refresh();

    expect($operation->moves)->toHaveCount(1)
        ->and((float) $operation->moves->first()->product_uom_qty)->toBe(10.0);
});

it('does not merge moves for different products', function () {
    $other = InventoryHelper::product();

    $operation = InventoryHelper::receipt($this->warehouse, [
        [$this->product, 4],
        [$other, 6],
    ]);

    Inventory::confirmTransfer($operation);

    expect($operation->refresh()->moves)->toHaveCount(2);
});
