<?php

namespace Webkul\Barcode;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Webkul\PluginManager\Package;

class BarcodePlugin implements Plugin
{
    public function getId(): string
    {
        return 'barcode';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        if (! Package::isPluginInstalled($this->getId())) {
            return;
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
