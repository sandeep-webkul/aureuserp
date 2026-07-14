<?php

use Webkul\Account\Enums\AccountType;
use Webkul\Account\Enums\DisplayType;
use Webkul\Account\Enums\MoveType;

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

it('remaps the receivable account through the fiscal position on post', function () {
    $mappedReceivable = AccountHelper::account('receivable');

    $fiscalPosition = AccountHelper::fiscalPositionRemappingAccount(
        $this->partner->propertyAccountReceivable,
        $mappedReceivable,
    );

    $invoice = AccountHelper::invoice(MoveType::OUT_INVOICE, $this->partner, null, [
        'fiscal_position_id' => $fiscalPosition->id,
    ]);
    AccountHelper::productLine($invoice, $this->income, qty: 2, priceUnit: 100);

    AccountHelper::post($invoice);

    $termLine = $invoice->refresh()->lines->firstWhere('display_type', DisplayType::PAYMENT_TERM);

    expect($termLine->account_id)->toBe($mappedReceivable->id)
        ->and($termLine->account->account_type)->toBe(AccountType::ASSET_RECEIVABLE);
});

it('leaves the receivable account unchanged without a matching fiscal mapping', function () {
    $otherSource = AccountHelper::account('receivable');
    $mappedReceivable = AccountHelper::account('receivable');

    $fiscalPosition = AccountHelper::fiscalPositionRemappingAccount($otherSource, $mappedReceivable);

    $invoice = AccountHelper::invoice(MoveType::OUT_INVOICE, $this->partner, null, [
        'fiscal_position_id' => $fiscalPosition->id,
    ]);
    AccountHelper::productLine($invoice, $this->income, qty: 2, priceUnit: 100);

    AccountHelper::post($invoice);

    $termLine = $invoice->refresh()->lines->firstWhere('display_type', DisplayType::PAYMENT_TERM);

    expect($termLine->account_id)->toBe($this->partner->property_account_receivable_id);
});

