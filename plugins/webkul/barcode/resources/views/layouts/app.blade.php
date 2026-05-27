<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ __('filament-panels::layout.direction') ?? 'ltr' }}"
    @class([
        'fi',
        'dark' => filament()->hasDarkMode() && filament()->hasDarkModeForced(),
    ])
>
    @php
        $nativeBridgeEnabled = \Webkul\Barcode\Support\NativeApp::usesNativeNavigation();
        $nativeRuntimeEnabled = \Webkul\Barcode\Support\NativeApp::bridgeEnabled();
    @endphp
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? __('barcode::app.title') }}</title>

        <style>
            [x-cloak=''],
            [x-cloak='x-cloak'],
            [x-cloak='1'] {
                display: none !important;
            }

            [x-cloak='inline-flex'] {
                display: inline-flex !important;
            }
        </style>

        @filamentStyles

        {{ filament()->getTheme()->getHtml() }}
        {{ filament()->getFontPreloadHtml() }}
        {{ filament()->getMonoFontPreloadHtml() }}
        {{ filament()->getSerifFontPreloadHtml() }}
        {{ filament()->getFontHtml() }}
        {{ filament()->getMonoFontHtml() }}
        {{ filament()->getSerifFontHtml() }}

        <style>
            :root {
                --font-family: '{!! filament()->getFontFamily() !!}';
                --mono-font-family: '{!! filament()->getMonoFontFamily() !!}';
                --serif-font-family: '{!! filament()->getSerifFontFamily() !!}';
                --default-theme-mode: {{ filament()->getDefaultThemeMode()->value }};
            }

            html.fi {
                --livewire-progress-bar-color: var(--primary-500);
            }
        </style>

        @livewireStyles
    </head>
    <body
        @class([
            'fi-body',
            'fi-panel-admin',
            'barcode-app',
            'barcode-app--native' => $nativeBridgeEnabled,
        ])
        style="{{ $nativeBridgeEnabled ? '--barcode-footer-offset: 96px;' : '' }}"
        x-data="{ sidebarOpen: false }"
    >
        @if ($nativeBridgeEnabled)
            @include('barcode::components.sidebar.native')

            @if (filled(\Webkul\Barcode\Support\NativeApp::headerTitle()))
                @include('barcode::components.header.native', [
                    'title' => \Webkul\Barcode\Support\NativeApp::headerTitle(),
                    'subtitle' => \Webkul\Barcode\Support\NativeApp::headerSubtitle(),
                    'showBarcode' => \Webkul\Barcode\Support\NativeApp::shouldShowScanAction(),
                    'barcodeUrl' => \Webkul\Barcode\Support\NativeApp::scanActionUrl(),
                ])
            @endif

            <script id="barcode-native-ui" type="application/json">@json(\Native\Mobile\Edge\Edge::all())</script>
        @endif

        <div class="barcode-shell">
            @unless ($nativeBridgeEnabled)
                <div
                    class="barcode-sidebar-overlay"
                    x-show="sidebarOpen"
                    x-transition.opacity
                    x-on:click="sidebarOpen = false"
                    x-cloak
                ></div>

                <aside
                    class="barcode-sidebar-mobile"
                    x-show="sidebarOpen"
                    x-transition:enter="barcode-sidebar-slide-enter"
                    x-transition:enter-start="barcode-sidebar-slide-enter-start"
                    x-transition:enter-end="barcode-sidebar-slide-enter-end"
                    x-transition:leave="barcode-sidebar-slide-leave"
                    x-transition:leave-start="barcode-sidebar-slide-leave-start"
                    x-transition:leave-end="barcode-sidebar-slide-leave-end"
                    x-on:keydown.escape.window="sidebarOpen = false"
                    x-cloak
                >
                    @include('barcode::components.sidebar.web')
                </aside>
            @endunless

            <div class="barcode-content">
                {{ $slot }}
            </div>
        </div>

        @livewireScripts
        @filamentScripts(withCore: true)
        <script src="{{ route('barcode.asset', ['file' => 'html5-qrcode.min.js']) }}" defer></script>
        <script>
            window.BarcodeNative = {
                enabled: @js($nativeRuntimeEnabled),

                async call(method, params = {}) {
                    if (! this.enabled) {
                        return null;
                    }

                    try {
                        const response = await fetch('/_native/api/call', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                            },
                            body: JSON.stringify({ method, params }),
                        });

                        if (! response.ok) {
                            return null;
                        }

                        const payload = await response.json();

                        return payload.status === 'success' ? payload.data : null;
                    } catch (error) {
                        return null;
                    }
                },

                async vibrate() {
                    return this.call('Device.Vibrate');
                },

                async toast(message, duration = 'short') {
                    if (! message) {
                        return null;
                    }

                    return this.call('Dialog.Toast', { message, duration });
                },
            };
        </script>
    </body>
</html>
