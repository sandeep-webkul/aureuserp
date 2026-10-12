<?php

namespace Webkul\PointOfSale\Filament\Pos\Pages;

use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Webkul\PointOfSale\Filament\Admin\Pages\Registers;

class Pos extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $slug = 'app';

    protected string $view = 'point-of-sale::filament.pos.pages.pos';

    protected static bool $shouldRegisterNavigation = false;

    public function getTitle(): string
    {
        return __('point-of-sale::filament/pos/pages/terminal.picker.title');
    }

    public function syncEndpoint(): string
    {
        return route('point-of-sale.till.orders.sync');
    }

    public function registersEndpoint(): string
    {
        return route('point-of-sale.till.registers');
    }

    public function openEndpointBase(): string
    {
        return route('point-of-sale.till.configs.session.open', ['config' => '__config__']);
    }

    public function discardEndpointBase(): string
    {
        return route('point-of-sale.till.registers.discard', ['config' => '__config__']);
    }

    public function bootEndpointBase(): string
    {
        return route('point-of-sale.till.sessions.boot', ['session' => '__session__']);
    }

    public function terminalBase(): string
    {
        return Register::getUrl(['session' => '__session__'], panel: 'pos');
    }

    public function backOfficeUrl(): string
    {
        return Registers::getUrl(panel: 'admin');
    }

    public function logoutUrl(): string
    {
        return route('filament.pos.auth.logout');
    }

    public function brandLogo(): string
    {
        return asset('images/logo.svg');
    }

    public function userName(): string
    {
        return Auth::user()?->name ?? '';
    }

    public function userAvatar(): ?string
    {
        $user = Auth::user();

        return $user ? Filament::getUserAvatarUrl($user) : null;
    }

    public function strings(): array
    {
        $lines = trans('point-of-sale::filament/pos/pages/terminal');

        return is_array($lines) ? $lines : [];
    }
}
