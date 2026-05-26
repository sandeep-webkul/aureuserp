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
