<?php

namespace Webkul\Barcode\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Native\Mobile\Edge\Edge;
use Symfony\Component\HttpFoundation\Response;
use Webkul\Barcode\Support\NativeApp;

class RenderHostedNativeUi
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! NativeApp::requestIsNative()) {
            Edge::reset();

            return $response;
        }

        $components = Edge::all();

        if ($components !== []) {
            $response->headers->set('x-native-ui', json_encode($components));
        }

        Edge::reset();

        return $response;
    }
}
