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
    $this->stock = $this->warehouse->lotStockLocation;

    $this->productA = InventoryHelper::product();
    $this->productB = InventoryHelper::product();
});

it('creates one move per product on a receipt', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [
        [$this->productA, 10],
        [$this->productB, 4],
    ]);

    expect($operation->moves)->toHaveCount(2);

    $byProduct = $operation->moves->keyBy('product_id');

    expect((float) $byProduct[$this->productA->id]->product_uom_qty)->toBe(10.0)
        ->and((float) $byProduct[$this->productB->id]->product_uom_qty)->toBe(4.0);
});

it('validates a multi product receipt and stocks each product independently', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [
        [$this->productA, 10],
        [$this->productB, 4],
    ]);

    Inventory::confirmTransfer($operation);

    Inventory::doneTransfer($operation->refresh());

    $operation->refresh();

    expect($operation->state)->toBe(OperationState::DONE)
        ->and($operation->moves->pluck('state')->unique()->all())->toBe([MoveState::DONE]);

    expect(InventoryHelper::onHand($this->productA, $this->stock))->toBe(10.0)
        ->and(InventoryHelper::onHand($this->productB, $this->stock))->toBe(4.0);
});

it('merges two moves for the same product into one on confirm', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [
        [$this->productA, 4],
        [$this->productA, 6],
    ]);

    expect($operation->moves)->toHaveCount(2);

    Inventory::confirmTransfer($operation);

    $operation->refresh();

    expect($operation->moves)->toHaveCount(1)
        ->and((float) $operation->moves->first()->product_uom_qty)->toBe(10.0);
});

it('does not merge moves for different products', function () {
    $operation = InventoryHelper::receipt($this->warehouse, [
        [$this->productA, 4],
        [$this->productB, 6],
    ]);

    Inventory::confirmTransfer($operation);

    expect($operation->refresh()->moves)->toHaveCount(2);
});

it('assigns each delivery move independently against its own stock', function () {
    InventoryHelper::stockUp($this->productA, $this->stock, 10);

    $operation = InventoryHelper::delivery($this->warehouse, [
        [$this->productA, 10],
        [$this->productB, 5],
    ]);

    Inventory::confirmTransfer($operation);

    $operation->refresh();

    $byProduct = $operation->moves->keyBy('product_id');

    expect($byProduct[$this->productA->id]->state)->toBe(MoveState::ASSIGNED)
        ->and((float) $byProduct[$this->productA->id]->quantity)->toBe(10.0)
        ->and($byProduct[$this->productA->id]->lines)->toHaveCount(1);

    expect($byProduct[$this->productB->id]->state)->toBe(MoveState::CONFIRMED)
        ->and((float) $byProduct[$this->productB->id]->quantity)->toBe(0.0)
        ->and($byProduct[$this->productB->id]->lines)->toHaveCount(0);
});

it('reports the operation as assigned when only some moves are available', function () {
    InventoryHelper::stockUp($this->productA, $this->stock, 10);

    $operation = InventoryHelper::delivery($this->warehouse, [
        [$this->productA, 10],
        [$this->productB, 5],
    ]);

    Inventory::confirmTransfer($operation);

    expect($operation->refresh()->state)->toBe(OperationState::ASSIGNED);
});

it('reports the operation as confirmed when no move is available', function () {
    $operation = InventoryHelper::delivery($this->warehouse, [
        [$this->productA, 10],
        [$this->productB, 5],
    ]);

    Inventory::confirmTransfer($operation);

    expect($operation->refresh()->state)->toBe(OperationState::CONFIRMED);
});

it('keeps per product reservations separate', function () {
    InventoryHelper::stockUp($this->productA, $this->stock, 10);
    InventoryHelper::stockUp($this->productB, $this->stock, 3);

    $operation = InventoryHelper::delivery($this->warehouse, [
        [$this->productA, 10],
        [$this->productB, 5],
    ]);

    Inventory::confirmTransfer($operation);

    expect(InventoryHelper::reserved($this->productA, $this->stock))->toBe(10.0)
        ->and(InventoryHelper::reserved($this->productB, $this->stock))->toBe(3.0);

    $byProduct = $operation->refresh()->moves->keyBy('product_id');

    expect($byProduct[$this->productB->id]->state)->toBe(MoveState::PARTIALLY_ASSIGNED);
});
