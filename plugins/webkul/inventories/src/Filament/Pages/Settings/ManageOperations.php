<?php

namespace Webkul\Inventory\Filament\Pages\Settings;

use Webkul\Inventory\Filament\Clusters\Settings\Pages\ManageOperations as BaseManageOperations;
use Webkul\Support\Filament\Concerns\HasSettingsMirror;

class ManageOperations extends BaseManageOperations
{
    use HasSettingsMirror;

    protected static ?string $slug = 'inventory/settings/manage-operations';

    protected static bool $shouldRegisterNavigation = false;
}
