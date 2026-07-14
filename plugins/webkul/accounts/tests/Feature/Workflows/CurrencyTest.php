<?php

use Webkul\Account\Enums\DisplayType;
use Webkul\Account\Enums\MoveType;
use Webkul\Account\Models\Move;

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

    $this->foreign = AccountHelper::otherCurrency();
    $this->foreign->rates()->delete();
    AccountHelper::setCurrencyRate($this->foreign, 2.0);
});

it('totals a foreign-currency invoice in its own currency', function () {
    $invoice = AccountHelper::invoice(MoveType::OUT_INVOICE, $this->partner, null, ['currency_id' => $this->foreign->id]);
    AccountHelper::productLine($invoice, $this->income, qty: 2, priceUnit: 100);

    AccountHelper::compute($invoice);

    expect((float) $invoice->refresh()->amount_total)->toBe(200.0);
});

it('records the foreign amount separately from the company-currency balance', function () {
    $invoice = AccountHelper::invoice(MoveType::OUT_INVOICE, $this->partner, null, ['currency_id' => $this->foreign->id]);
    AccountHelper::productLine($invoice, $this->income, qty: 2, priceUnit: 100);

    AccountHelper::post($invoice);

    $productLine = $invoice->refresh()->lines->firstWhere('display_type', DisplayType::PRODUCT);

    expect((float) abs($productLine->amount_currency))->toBe(200.0)
        ->and((float) abs($productLine->balance))->toBe(100.0);
});

it('keeps a foreign-currency invoice balanced in company currency', function () {
    $invoice = AccountHelper::invoice(MoveType::OUT_INVOICE, $this->partner, null, ['currency_id' => $this->foreign->id]);
    AccountHelper::productLine($invoice, $this->income, qty: 2, priceUnit: 100);

    AccountHelper::post($invoice);

    $lines = $invoice->refresh()->lines;

    expect((float) $lines->sum(fn ($l) => (float) $l->debit))
        ->toBe((float) $lines->sum(fn ($l) => (float) $l->credit));
});

