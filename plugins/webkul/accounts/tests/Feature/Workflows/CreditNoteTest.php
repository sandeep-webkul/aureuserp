<?php

use Webkul\Account\Enums\DisplayType;
use Webkul\Account\Enums\DocumentType;
use Webkul\Account\Enums\MoveState;
use Webkul\Account\Enums\MoveType;
use Webkul\Account\Enums\PaymentState;
use Webkul\Account\Models\TaxPartition;

require_once __DIR__.'/../../../../support/tests/Helpers/TestBootstrapHelper.php';
require_once __DIR__.'/../../Helpers/AccountHelper.php';

beforeEach(function () {
    TestBootstrapHelper::ensurePluginInstalled('accounts');

    Illuminate\Support\Facades\DB::table('plugins')->updateOrInsert(
        ['name' => 'accounts'],
        ['is_installed' => true, 'is_active' => true, 'updated_at' => now()],
    );

    Webkul\PluginManager\Package::$plugins = Webkul\PluginManager\Models\Plugin::all()->keyBy('name');

    Illuminate\Support\Facades\URL::resolveMissingNamedRoutesUsing(fn () => '#');

    AccountHelper::actingAsAdmin();

    $this->income = AccountHelper::account('income');
    $this->partner = AccountHelper::partner();
});

it('computes customer credit note totals from a product line', function () {
    $creditNote = AccountHelper::invoice(MoveType::OUT_REFUND, $this->partner);
    AccountHelper::productLine($creditNote, $this->income, qty: 2, priceUnit: 100);

    AccountHelper::compute($creditNote);

    expect((float) $creditNote->refresh()->amount_untaxed)->toBe(200.0)
        ->and((float) $creditNote->amount_total)->toBe(200.0);
});

it('posts a customer credit note and keeps the entry balanced', function () {
    $creditNote = AccountHelper::invoice(MoveType::OUT_REFUND, $this->partner);
    AccountHelper::productLine($creditNote, $this->income, qty: 2, priceUnit: 100);

    AccountHelper::post($creditNote);

    $lines = $creditNote->refresh()->lines;

    expect($creditNote->state)->toBe(MoveState::POSTED)
        ->and((float) $lines->sum(fn ($l) => (float) $l->debit))->toBe(200.0)
        ->and((float) $lines->sum(fn ($l) => (float) $l->credit))->toBe(200.0);
});

it('reverses a posted invoice into a linked reversing entry', function () {
    $invoice = AccountHelper::invoice(MoveType::OUT_INVOICE, $this->partner);
    AccountHelper::productLine($invoice, $this->income, qty: 2, priceUnit: 100);

    AccountHelper::post($invoice);

    $reversal = AccountHelper::reverse($invoice);

    expect($reversal)->not->toBeNull()
        ->and($reversal->reversed_entry_id)->toBe($invoice->id);
});

it('mirrors the original debit and credit on the reversing entry', function () {
    $invoice = AccountHelper::invoice(MoveType::OUT_INVOICE, $this->partner);
    AccountHelper::productLine($invoice, $this->income, qty: 2, priceUnit: 100);

    AccountHelper::post($invoice);

    $reversal = AccountHelper::reverse($invoice);

    $lines = $reversal->refresh()->lines;

    expect((float) $lines->sum(fn ($l) => (float) $l->debit))->toBe(200.0)
        ->and((float) $lines->sum(fn ($l) => (float) $l->credit))->toBe(200.0);
});

it('uses the refund repartition line for a taxed credit note', function () {
    $tax = AccountHelper::taxWithAccounts(10);

    $creditNote = AccountHelper::invoice(MoveType::OUT_REFUND, $this->partner);
    AccountHelper::productLine($creditNote, $this->income, qty: 2, priceUnit: 100, taxes: [$tax]);

    AccountHelper::post($creditNote);

    $taxLine = $creditNote->refresh()->lines->firstWhere('display_type', DisplayType::TAX);
    $repartition = TaxPartition::find($taxLine->tax_repartition_line_id);

    expect((float) $creditNote->amount_tax)->toBe(20.0)
        ->and($repartition->document_type)->toBe(DocumentType::REFUND);
});

it('reverses and reconciles a posted invoice, marking it reversed', function () {
    $invoice = AccountHelper::invoice(MoveType::OUT_INVOICE, $this->partner);
    AccountHelper::productLine($invoice, $this->income, qty: 2, priceUnit: 100);
    AccountHelper::post($invoice);

    $creditNote = AccountHelper::reverse($invoice);

    AccountHelper::reconcile($invoice, $creditNote);

    expect($invoice->refresh()->payment_state)->toBe(PaymentState::REVERSED)
        ->and((float) abs($invoice->amount_residual))->toBe(0.0);
});
