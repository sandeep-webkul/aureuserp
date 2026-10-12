<?php

namespace Webkul\PointOfSale\Filament\Pos\Pages;

use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Webkul\PointOfSale\Facades\PointOfSale;
use Webkul\PointOfSale\Filament\Admin\Pages\Registers as AdminRegisters;
use Webkul\PointOfSale\Models\Config;
use Webkul\PointOfSale\Models\Session;
use Webkul\PointOfSale\Services\BootLoader;
use Webkul\PointOfSale\Services\SessionWorkflow;
use Webkul\PointOfSale\Support\PosAccess;

abstract class Terminal extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $slug = 'terminal/{session}';

    protected string $view = 'point-of-sale::filament.pos.pages.till';

    protected static bool $shouldRegisterNavigation = false;

    public Session $session;

    public Config $config;

    public function mount(Session $session): void
    {
        abort_unless(PosAccess::reachesActiveSession($session), 403);

        $this->session = $session;

        $this->config = $session->config;

        if (! $session->isLive()) {
            $live = app(SessionWorkflow::class)->liveSessionFor($this->config);

            $this->redirect($live && PosAccess::reachesSession($live)
                ? static::getUrl(['session' => $live->getKey()])
                : $this->backUrl());

            return;
        }

        $this->session = PointOfSale::loginSession($session);
    }

    public function backUrl(): string
    {
        return Registers::getUrl();
    }

    public function bootPayload(): array
    {
        return array_merge(app(BootLoader::class)->load($this->config, $this->session), [
            'pending_refund' => session()->pull(static::pendingRefundKey($this->session)),
        ]);
    }

    public static function pendingRefundKey(Session $session): string
    {
        return 'point-of-sale.pending-refund.'.$session->getKey();
    }

    public function syncEndpoint(): string
    {
        return route('point-of-sale.till.orders.sync');
    }

    public function backOfficeUrl(): string
    {
        return AdminRegisters::getUrl(panel: 'admin');
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
}
