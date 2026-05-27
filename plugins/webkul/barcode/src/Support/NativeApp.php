<?php

namespace Webkul\Barcode\Support;

class NativeApp
{
    public const REMOTE_SHELL_FLAG = 'barcode_native_shell';

    public static function startUrl(): string
    {
        return '/admin/barcode?nativephp=1';
    }

    public static function navigationUrl(string $route, array $parameters = []): string
    {
        if (self::usesHostedRemoteShell()) {
            return route($route, $parameters);
        }

        if (self::requestIsNative()) {
            return route($route, $parameters, false);
        }

        return route($route, $parameters);
    }

    public static function requestIsJump(): bool
    {
        $jumpHttpPort = (int) (getenv('JUMP_HTTP_PORT') ?: 3000);
        $userAgent = request()?->userAgent() ?? '';
        $isMobileUserAgent = str_contains($userAgent, 'iPhone')
            || str_contains($userAgent, 'iPad')
            || str_contains($userAgent, 'Android');

        return (int) request()?->getPort() === $jumpHttpPort
            && $isMobileUserAgent;
    }

    public static function requestIsNative(): bool
    {
        return request()?->server('NATIVEPHP_RUNNING') === 'true'
            || request()?->boolean('nativephp') === true
            || request()?->cookie(self::REMOTE_SHELL_FLAG) === '1'
            || self::requestIsJump();
    }

    public static function bridgeEnabled(): bool
    {
        return self::requestIsNative();
    }

    public static function usesNativeNavigation(): bool
    {
        return self::requestIsNative();
    }

    public static function headerTitle(): ?string
    {
        $routeName = request()?->route()?->getName();

        return match ($routeName) {
            'barcode.dashboard'   => __('barcode::app.title'),
            'barcode.adjustments' => __('barcode::app.adjustments.title'),
            'barcode.transfers'   => request()?->route('operationType')?->name,
            'barcode.operation'   => request()?->route('operation')?->name,
            default               => null,
        };
    }

    public static function headerSubtitle(): ?string
    {
        $routeName = request()?->route()?->getName();

        return match ($routeName) {
            'barcode.dashboard'   => __('barcode::app.dashboard.operations'),
            'barcode.adjustments' => __('barcode::app.adjustments.subtitle'),
            'barcode.transfers'   => __('barcode::app.dashboard.operations'),
            'barcode.operation'   => request()?->route('operationType')?->name,
            default               => null,
        };
    }

    public static function shouldShowScanAction(): bool
    {
        return in_array(request()?->route()?->getName(), [
            'barcode.adjustments',
            'barcode.transfers',
            'barcode.operation',
        ], true);
    }

    public static function scanActionUrl(): ?string
    {
        if (! self::shouldShowScanAction()) {
            return null;
        }

        if (self::usesHostedRemoteShell()) {
            return request()?->fullUrl().'#scan-barcode';
        }

        return request()?->getRequestUri().'#scan-barcode';
    }

    public static function usesHostedRemoteShell(): bool
    {
        $startUrl = (string) config('nativephp.start_url', '');

        return str_starts_with($startUrl, 'http://') || str_starts_with($startUrl, 'https://');
    }

    /**
     * @return array<string, string>
     */
    public static function iosPermissions(): array
    {
        return [
            'NSCameraUsageDescription' => 'Barcode uses your camera to scan products, lots, packages, and inventory operations.',
        ];
    }
}
