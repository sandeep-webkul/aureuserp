<?php

namespace Webkul\Accounting\Filament\Pages\Settings;

use Webkul\Accounting\Filament\Clusters\Settings\Pages\ManageProducts as BaseManageProducts;
use Webkul\Support\Filament\Concerns\HasSettingsMirror;

class ManageProducts extends BaseManageProducts
{
    use HasSettingsMirror;

    protected static ?string $slug = 'accounting/settings/manage-products';

    protected static bool $shouldRegisterNavigation = false;
}
