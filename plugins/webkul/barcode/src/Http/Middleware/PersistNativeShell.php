<?php

namespace Webkul\Barcode\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Webkul\Barcode\Support\NativeApp;

class PersistNativeShell
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->boolean('nativephp')) {
            Cookie::queue(cookie(
                name: NativeApp::REMOTE_SHELL_FLAG,
                value: '1',
                minutes: 60 * 24 * 30,
                secure: $request->isSecure(),
                sameSite: 'lax',
            ));
        }

        return $response;
    }
}
