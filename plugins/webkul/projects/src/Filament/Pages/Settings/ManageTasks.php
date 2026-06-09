<?php

namespace Webkul\Project\Filament\Pages\Settings;

use Webkul\Project\Filament\Clusters\Settings\Pages\ManageTasks as BaseManageTasks;
use Webkul\Support\Filament\Concerns\HasSettingsMirror;

class ManageTasks extends BaseManageTasks
{
    use HasSettingsMirror;

    protected static ?string $slug = 'project/settings/manage-tasks';

    protected static bool $shouldRegisterNavigation = false;
}
