<?php

namespace Webkul\Project\Filament\Pages\Settings;

use Webkul\Project\Filament\Clusters\Settings\Pages\ManageTime as BaseManageTime;
use Webkul\Support\Filament\Concerns\HasSettingsMirror;

class ManageTime extends BaseManageTime
{
    use HasSettingsMirror;

    protected static ?string $slug = 'project/settings/manage-time';

    protected static bool $shouldRegisterNavigation = false;
}
