<?php

namespace Webkul\Purchase\Filament\Admin\Pages\Settings;

use Webkul\Purchase\Filament\Admin\Clusters\Settings\Pages\ManageProducts as BaseManageProducts;
use Webkul\Support\Filament\Concerns\HasSettingsMirror;

class ManageProducts extends BaseManageProducts
{
    use HasSettingsMirror;

    protected static ?string $slug = 'purchase/settings/manage-products';

    protected static bool $shouldRegisterNavigation = false;
}
