<?php

namespace Webkul\Purchase\Filament\Admin\Pages\Settings;

use Webkul\Purchase\Filament\Admin\Clusters\Settings\Pages\ManageOrders as BaseManageOrders;
use Webkul\Support\Filament\Concerns\HasSettingsMirror;

class ManageOrders extends BaseManageOrders
{
    use HasSettingsMirror;

    protected static ?string $slug = 'purchase/settings/manage-orders';

    protected static bool $shouldRegisterNavigation = false;
}
