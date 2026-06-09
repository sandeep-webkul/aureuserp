<?php

namespace Webkul\Inventory\Filament\Pages\Settings;

use Webkul\Inventory\Filament\Clusters\Settings\Pages\ManageProducts as BaseManageProducts;
use Webkul\Support\Filament\Concerns\HasSettingsMirror;

class ManageProducts extends BaseManageProducts
{
    use HasSettingsMirror;

    protected static ?string $slug = 'inventory/settings/manage-products';

    protected static bool $shouldRegisterNavigation = false;
}
