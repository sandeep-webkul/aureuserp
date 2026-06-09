<?php

namespace Webkul\Accounting\Filament\Pages\Settings;

use Webkul\Accounting\Filament\Clusters\Settings\Pages\ManageDefaultAccounts as BaseManageDefaultAccounts;
use Webkul\Support\Filament\Concerns\HasSettingsMirror;

class ManageDefaultAccounts extends BaseManageDefaultAccounts
{
    use HasSettingsMirror;

    protected static ?string $slug = 'accounting/settings/manage-default-accounts';

    protected static bool $shouldRegisterNavigation = false;
}
