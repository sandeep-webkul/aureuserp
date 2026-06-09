<?php

namespace Webkul\Inventory\Filament\Pages\Settings;

use Webkul\Inventory\Filament\Clusters\Settings\Pages\ManageLogistics as BaseManageLogistics;
use Webkul\Support\Filament\Concerns\HasSettingsMirror;

class ManageLogistics extends BaseManageLogistics
{
    use HasSettingsMirror;

    protected static ?string $slug = 'inventory/settings/manage-logistics';

    protected static bool $shouldRegisterNavigation = false;
}
