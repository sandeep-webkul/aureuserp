<?php

namespace Webkul\Accounting\Filament\Pages\Settings;

use Webkul\Accounting\Filament\Clusters\Settings\Pages\ManageCustomerInvoice as BaseManageCustomerInvoice;
use Webkul\Support\Filament\Concerns\HasSettingsMirror;

class ManageCustomerInvoice extends BaseManageCustomerInvoice
{
    use HasSettingsMirror;

    protected static ?string $slug = 'accounting/settings/manage-customer-invoice';

    protected static bool $shouldRegisterNavigation = false;
}
