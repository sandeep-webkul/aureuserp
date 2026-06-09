<?php

namespace Webkul\Invoice\Filament\Pages\Settings;

use Webkul\Invoice\Filament\Clusters\Settings\Pages\Products as BaseProducts;
use Webkul\Support\Filament\Concerns\HasSettingsMirror;

class Products extends BaseProducts
{
    use HasSettingsMirror;

    protected static ?string $slug = 'invoice/settings/products';

    protected static bool $shouldRegisterNavigation = false;
}
