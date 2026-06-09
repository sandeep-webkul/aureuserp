<?php

namespace Webkul\Manufacturing\Filament\Pages\Settings;

use Webkul\Manufacturing\Filament\Clusters\Settings\Pages\ManageOperations as BaseManageOperations;
use Webkul\Support\Filament\Concerns\HasSettingsMirror;

class ManageOperations extends BaseManageOperations
{
    use HasSettingsMirror;

    protected static ?string $slug = 'manufacturing/settings/manage-operations';

    protected static bool $shouldRegisterNavigation = false;
}
