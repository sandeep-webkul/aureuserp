<?php

namespace Webkul\PluginManager;

use Illuminate\Support\ServiceProvider;
use Webkul\Support\Package;

class PluginManagerServiceProvider extends ServiceProvider
{
    public static string $name = 'plugins';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasViews()
            ->hasTranslations()
            ->hasRoute('web')
            ->hasSeeder('Webkul\\PluginManager\\Database\\Seeders\\PluginSeeder');
    }
}
