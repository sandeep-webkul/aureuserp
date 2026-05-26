<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\Barcode\BarcodeServiceProvider as BarcodePluginServiceProvider;

class NativeServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void {}

    /**
     * @return array<int, class-string<ServiceProvider>>
     */
    public function plugins(): array
    {
        return [
            BarcodePluginServiceProvider::class,
        ];
    }
}
