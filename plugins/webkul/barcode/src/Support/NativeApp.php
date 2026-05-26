<?php

namespace Webkul\Barcode\Support;

class NativeApp
{
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
            'barcode.dashboard' => __('barcode::app.title'),
            'barcode.transfers' => request()?->route('operationType')?->name,
            'barcode.operation' => request()?->route('operation')?->name,
            default             => null,
        };
    }

    public static function headerSubtitle(): ?string
    {
        $routeName = request()?->route()?->getName();

        return match ($routeName) {
            'barcode.dashboard' => __('barcode::app.dashboard.operations'),
            'barcode.transfers' => __('barcode::app.dashboard.operations'),
            'barcode.operation' => request()?->route('operationType')?->name,
            default             => null,
        };
    }

    public static function shouldShowScanAction(): bool
    {
        return in_array(request()?->route()?->getName(), [
            'barcode.transfers',
            'barcode.operation',
        ], true);
    }

    public static function scanActionUrl(): ?string
    {
        if (! self::shouldShowScanAction()) {
            return null;
        }

        return request()?->getRequestUri().'#scan-barcode';
    }

    public static function startUrl(): string
    {
        return '/barcode';
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
