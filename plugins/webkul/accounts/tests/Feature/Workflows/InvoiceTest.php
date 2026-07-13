<?php

use Webkul\Account\Enums\AccountType;
use Webkul\Account\Enums\DisplayType;
use Webkul\Account\Enums\MoveState;
use Webkul\Account\Enums\MoveType;
use Webkul\Account\Enums\PaymentState;

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

it('computes invoice totals from a product line with no tax', function () {
    $invoice = AccountHelper::invoice(MoveType::OUT_INVOICE, $this->partner);
    AccountHelper::productLine($invoice, $this->income, qty: 2, priceUnit: 100);

    AccountHelper::compute($invoice);

    $invoice->refresh();

    expect((float) $invoice->amount_untaxed)->toBe(200.0)
        ->and((float) $invoice->amount_tax)->toBe(0.0)
        ->and((float) $invoice->amount_total)->toBe(200.0);
});

it('posts a customer invoice and marks it posted', function () {
    $invoice = AccountHelper::invoice(MoveType::OUT_INVOICE, $this->partner);
    AccountHelper::productLine($invoice, $this->income, qty: 2, priceUnit: 100);

    AccountHelper::post($invoice);

    expect($invoice->refresh()->state)->toBe(MoveState::POSTED);
});

it('creates a receivable payment-term line when a customer invoice is posted', function () {
    $invoice = AccountHelper::invoice(MoveType::OUT_INVOICE, $this->partner);
    AccountHelper::productLine($invoice, $this->income, qty: 2, priceUnit: 100);

    AccountHelper::post($invoice);

    $termLine = $invoice->refresh()->lines
        ->firstWhere('display_type', DisplayType::PAYMENT_TERM);

    expect($termLine)->not->toBeNull()
        ->and($termLine->account->account_type)->toBe(AccountType::ASSET_RECEIVABLE)
        ->and((float) abs($termLine->balance))->toBe(200.0);
});

it('balances the journal entry so total debit equals total credit', function () {
    $invoice = AccountHelper::invoice(MoveType::OUT_INVOICE, $this->partner);
    AccountHelper::productLine($invoice, $this->income, qty: 2, priceUnit: 100);

    AccountHelper::post($invoice);

    $lines = $invoice->refresh()->lines;

    $debit = (float) $lines->sum(fn ($l) => (float) $l->debit);
    $credit = (float) $lines->sum(fn ($l) => (float) $l->credit);

    expect($debit)->toBe(200.0)
        ->and($credit)->toBe(200.0);
});

it('leaves the posted invoice unpaid with the full amount residual', function () {
    $invoice = AccountHelper::invoice(MoveType::OUT_INVOICE, $this->partner);
    AccountHelper::productLine($invoice, $this->income, qty: 2, priceUnit: 100);

    AccountHelper::post($invoice);

    $invoice->refresh();

    expect($invoice->payment_state)->toBe(PaymentState::NOT_PAID)
        ->and((float) abs($invoice->amount_residual))->toBe(200.0);
});

it('applies a discount to the invoice subtotal', function () {
    $invoice = AccountHelper::invoice(MoveType::OUT_INVOICE, $this->partner);
    AccountHelper::productLine($invoice, $this->income, qty: 2, priceUnit: 100, discount: 10);

    AccountHelper::compute($invoice);

    expect((float) $invoice->refresh()->amount_untaxed)->toBe(180.0)
        ->and((float) $invoice->amount_total)->toBe(180.0);
});

it('aggregates multiple product lines into the invoice total', function () {
    $invoice = AccountHelper::invoice(MoveType::OUT_INVOICE, $this->partner);
    AccountHelper::productLine($invoice, $this->income, qty: 2, priceUnit: 100);
    AccountHelper::productLine($invoice, $this->income, qty: 1, priceUnit: 50);

    AccountHelper::compute($invoice);

    expect((float) $invoice->refresh()->amount_untaxed)->toBe(250.0)
        ->and((float) $invoice->amount_total)->toBe(250.0);
});

it('adds a tax total to a taxed invoice', function () {
    $tax = AccountHelper::taxWithAccounts(10);

    $invoice = AccountHelper::invoice(MoveType::OUT_INVOICE, $this->partner);
    AccountHelper::productLine($invoice, $this->income, qty: 2, priceUnit: 100, taxes: [$tax]);

    AccountHelper::compute($invoice);

    expect((float) $invoice->refresh()->amount_untaxed)->toBe(200.0)
        ->and((float) $invoice->amount_tax)->toBe(20.0)
        ->and((float) $invoice->amount_total)->toBe(220.0);
});

it('creates a tax line and keeps the entry balanced when a taxed invoice is posted', function () {
    $tax = AccountHelper::taxWithAccounts(10);

    $invoice = AccountHelper::invoice(MoveType::OUT_INVOICE, $this->partner);
    AccountHelper::productLine($invoice, $this->income, qty: 2, priceUnit: 100, taxes: [$tax]);

    AccountHelper::post($invoice);

    $lines = $invoice->refresh()->lines;
    $taxLine = $lines->firstWhere('display_type', DisplayType::TAX);

    $debit = (float) $lines->sum(fn ($l) => (float) $l->debit);
    $credit = (float) $lines->sum(fn ($l) => (float) $l->credit);

    expect($taxLine)->not->toBeNull()
        ->and((float) abs($taxLine->balance))->toBe(20.0)
        ->and($debit)->toBe(220.0)
        ->and($credit)->toBe(220.0);
});

/*
|--------------------------------------------------------------------------
| Settings: payment terms, tax groups, cash rounding, currency
|--------------------------------------------------------------------------
*/

it('splits the receivable into two installment lines with distinct due dates for a payment term', function () {
    $term = AccountHelper::paymentTerm([[50, 0], [50, 30]]);

    $invoice = AccountHelper::invoice(MoveType::OUT_INVOICE, $this->partner, null, [
        'invoice_payment_term_id' => $term->id,
    ]);
    AccountHelper::productLine($invoice, $this->income, qty: 2, priceUnit: 100);

    AccountHelper::post($invoice);

    $lines = $invoice->refresh()->lines;
    $termLines = $lines->where('display_type', DisplayType::PAYMENT_TERM)->values();

    expect($termLines)->toHaveCount(2)
        ->and($termLines->pluck('date_maturity')->map(fn ($d) => (string) $d)->unique())->toHaveCount(2)
        ->and((float) $lines->sum(fn ($l) => (float) $l->debit))->toBe(200.0)
        ->and((float) $lines->sum(fn ($l) => (float) $l->credit))->toBe(200.0);
});

it('creates a separate tax line per tax group', function () {
    $groupA = AccountHelper::taxWithAccounts(10);
    $groupB = AccountHelper::taxWithAccounts(5);

    $invoice = AccountHelper::invoice(MoveType::OUT_INVOICE, $this->partner);
    AccountHelper::productLine($invoice, $this->income, qty: 2, priceUnit: 100, taxes: [$groupA, $groupB]);

    AccountHelper::post($invoice);

    $taxLines = $invoice->refresh()->lines->where('display_type', DisplayType::TAX)->values();

    expect($taxLines)->toHaveCount(2)
        ->and($taxLines->pluck('tax_group_id')->unique())->toHaveCount(2)
        ->and((float) $invoice->amount_tax)->toBe(30.0);
});

it('adds a rounding line and rounds the total to the cash-rounding precision', function () {
    $rounding = AccountHelper::cashRounding(0.05);

    $invoice = AccountHelper::invoice(MoveType::OUT_INVOICE, $this->partner, null, [
        'invoice_cash_rounding_id' => $rounding->id,
    ]);
    AccountHelper::productLine($invoice, $this->income, qty: 1, priceUnit: 100.02);

    AccountHelper::compute($invoice);
    AccountHelper::post($invoice);

    $roundingLine = $invoice->refresh()->lines->firstWhere('display_type', DisplayType::ROUNDING);

    expect($roundingLine)->not->toBeNull()
        ->and((float) $invoice->amount_total)->toBe(100.0);
});

it('records the foreign amount and company balance separately on a foreign-currency invoice', function () {
    $currency = AccountHelper::otherCurrency();

    $invoice = AccountHelper::invoice(MoveType::OUT_INVOICE, $this->partner, null, [
        'currency_id'          => $currency->id,
        'invoice_currency_rate' => 2.0,
    ]);
    AccountHelper::productLine($invoice, $this->income, qty: 2, priceUnit: 100);

    AccountHelper::post($invoice);

    $lines = $invoice->refresh()->lines;

    expect((float) $invoice->amount_total)->toBe(200.0)
        ->and((float) $lines->sum(fn ($l) => (float) $l->debit))->toBe((float) $lines->sum(fn ($l) => (float) $l->credit));
});

/*
|--------------------------------------------------------------------------
| Payment + reconciliation
|--------------------------------------------------------------------------
*/

it('marks a fully paid invoice as paid with zero residual', function () {
    $invoice = AccountHelper::invoice(MoveType::OUT_INVOICE, $this->partner);
    AccountHelper::productLine($invoice, $this->income, qty: 2, priceUnit: 100);
    AccountHelper::post($invoice);

    AccountHelper::pay($invoice);

    expect($invoice->refresh()->payment_state)->toBe(PaymentState::PAID)
        ->and((float) abs($invoice->amount_residual))->toBe(0.0);
});

it('marks a partially paid invoice as partial with a remaining residual', function () {
    $invoice = AccountHelper::invoice(MoveType::OUT_INVOICE, $this->partner);
    AccountHelper::productLine($invoice, $this->income, qty: 2, priceUnit: 100);
    AccountHelper::post($invoice);

    AccountHelper::pay($invoice, amount: 120);

    expect($invoice->refresh()->payment_state)->toBe(PaymentState::PARTIAL)
        ->and((float) abs($invoice->amount_residual))->toBe(80.0);
});

it('marks the invoice paid after two partial payments settle the balance', function () {
    $invoice = AccountHelper::invoice(MoveType::OUT_INVOICE, $this->partner);
    AccountHelper::productLine($invoice, $this->income, qty: 2, priceUnit: 100);
    AccountHelper::post($invoice);

    AccountHelper::pay($invoice, amount: 120);
    AccountHelper::pay($invoice->refresh(), amount: 80);

    expect($invoice->refresh()->payment_state)->toBe(PaymentState::PAID)
        ->and((float) abs($invoice->amount_residual))->toBe(0.0);
});

it('reconciles the payment against the receivable line', function () {
    $invoice = AccountHelper::invoice(MoveType::OUT_INVOICE, $this->partner);
    AccountHelper::productLine($invoice, $this->income, qty: 2, priceUnit: 100);
    AccountHelper::post($invoice);

    AccountHelper::pay($invoice);

    expect((bool) $invoice->refresh()->paymentTermLines->first()->reconciled)->toBeTrue();
});
