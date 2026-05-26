<?php

namespace App\Providers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Webkul\Security\Models\User;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Authenticatable::class, User::class);
    }

    public function boot(): void
    {
        $isJumpRuntime = filled(getenv('JUMP_BRIDGE_PORT'));

        if (app()->environment('production') && ! $isJumpRuntime) {
            URL::forceScheme('https');
        }
    }
}
