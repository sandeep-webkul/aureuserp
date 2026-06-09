<?php

namespace Webkul\Inventory\Filament\Pages\Settings;

use Webkul\Inventory\Filament\Clusters\Settings\Pages\ManageTraceability as BaseManageTraceability;
use Webkul\Support\Filament\Concerns\HasSettingsMirror;

class ManageTraceability extends BaseManageTraceability
{
    use HasSettingsMirror;

    protected static ?string $slug = 'inventory/settings/manage-traceability';

    protected static bool $shouldRegisterNavigation = false;
}
