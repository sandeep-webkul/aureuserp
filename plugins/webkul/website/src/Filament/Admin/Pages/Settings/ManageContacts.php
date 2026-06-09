<?php

namespace Webkul\Website\Filament\Admin\Pages\Settings;

use Webkul\Support\Filament\Concerns\HasSettingsMirror;
use Webkul\Website\Filament\Admin\Clusters\Settings\Pages\ManageContacts as BaseManageContacts;

class ManageContacts extends BaseManageContacts
{
    use HasSettingsMirror;

    protected static ?string $slug = 'website/settings/manage-contacts';

    protected static bool $shouldRegisterNavigation = false;
}
