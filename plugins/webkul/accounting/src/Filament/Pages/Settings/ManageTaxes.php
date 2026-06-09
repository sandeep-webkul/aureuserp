<?php

namespace Webkul\Accounting\Filament\Pages\Settings;

use Webkul\Accounting\Filament\Clusters\Settings\Pages\ManageTaxes as BaseManageTaxes;
use Webkul\Support\Filament\Concerns\HasSettingsMirror;

class ManageTaxes extends BaseManageTaxes
{
    use HasSettingsMirror;

    protected static ?string $slug = 'accounting/settings/manage-taxes';

    protected static bool $shouldRegisterNavigation = false;
}
