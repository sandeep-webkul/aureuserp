<?php

namespace Webkul\Inventory\Filament\Pages\Settings;

use Webkul\Inventory\Filament\Clusters\Settings\Pages\ManageWarehouses as BaseManageWarehouses;
use Webkul\Support\Filament\Concerns\HasSettingsMirror;

class ManageWarehouses extends BaseManageWarehouses
{
    use HasSettingsMirror;

    protected static ?string $slug = 'inventory/settings/manage-warehouses';

    protected static bool $shouldRegisterNavigation = false;
}
