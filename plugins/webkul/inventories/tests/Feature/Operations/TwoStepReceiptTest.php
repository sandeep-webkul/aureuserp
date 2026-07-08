<?php

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

    $this->warehouse = InventoryHelper::warehouse(ReceptionStep::TWO_STEPS);
    $this->product = InventoryHelper::product();
    $this->stock = $this->warehouse->lotStockLocation;
    $this->input = $this->warehouse->inputStockLocation;
});

function storageOperation($warehouse): ?Operation
{
    return Operation::query()
        ->where('operation_type_id', $warehouse->store_type_id)
        ->first();
}

it('routes a two step receipt from the supplier into the input location', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [[$this->product, 10]]);

    expect($operation->sourceLocation->type->value)->toBe('supplier')
        ->and($operation->destination_location_id)->toBe($this->input->id);
});

it('creates no storage operation before the receipt is validated', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    expect(storageOperation($this->warehouse))->toBeNull();
});

it('lands the received quantity in input and not in stock', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    Inventory::doneTransfer($operation->refresh());

    expect($operation->refresh()->state)->toBe(OperationState::DONE)
        ->and(InventoryHelper::onHand($this->product, $this->input))->toBe(10.0)
        ->and(InventoryHelper::onHand($this->product, $this->stock))->toBe(0.0);
});

it('pushes a storage operation from input to stock when the receipt is validated', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    Inventory::doneTransfer($operation->refresh());

    $storage = storageOperation($this->warehouse);

    expect($storage)->not->toBeNull()
        ->and($storage->moves)->toHaveCount(1);

    $move = $storage->moves->first();

    expect($move->source_location_id)->toBe($this->input->id)
        ->and($move->destination_location_id)->toBe($this->stock->id)
        ->and($move->procure_method)->toBe(ProcureMethod::MAKE_TO_ORDER)
        ->and((float) $move->product_uom_qty)->toBe(10.0);
});

it('links the storage move back to the receipt move as its origin', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    Inventory::doneTransfer($operation->refresh());

    $receiptMove = $operation->refresh()->moves->first();
    $storageMove = storageOperation($this->warehouse)->moves->first();

    expect($receiptMove->moveDestinations->pluck('id')->all())->toContain($storageMove->id)
        ->and($storageMove->moveOrigins->pluck('id')->all())->toContain($receiptMove->id);
});

it('reserves the storage move against the input location', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    Inventory::doneTransfer($operation->refresh());

    $move = storageOperation($this->warehouse)->moves->first();

    expect($move->state)->toBe(MoveState::ASSIGNED)
        ->and((float) $move->quantity)->toBe(10.0)
        ->and($move->lines)->toHaveCount(1);

    expect(InventoryHelper::reserved($this->product, $this->input))->toBe(10.0);
});

it('moves the quantity from input into stock when the storage operation is validated', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    Inventory::doneTransfer($operation->refresh());

    $storage = storageOperation($this->warehouse);

    Inventory::doneTransfer($storage->refresh());

    expect($storage->refresh()->state)->toBe(OperationState::DONE)
        ->and($storage->moves->first()->state)->toBe(MoveState::DONE);

    expect(InventoryHelper::onHand($this->product, $this->input))->toBe(0.0)
        ->and(InventoryHelper::onHand($this->product, $this->stock))->toBe(10.0)
        ->and(InventoryHelper::reserved($this->product, $this->input))->toBe(0.0);
});

it('pushes only the received quantity when the receipt is partially validated', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [[$this->product, 10]]);

    Inventory::confirmTransfer($operation);

    InventoryHelper::pick($operation->refresh()->moves->first(), 4);

    Inventory::doneTransfer($operation->refresh());

    $storage = storageOperation($this->warehouse);

    expect((float) $storage->moves->first()->product_uom_qty)->toBe(4.0)
        ->and(InventoryHelper::onHand($this->product, $this->input))->toBe(4.0);
});
