@props([
    'title',
    'subtitle' => null,
    'breadcrumbs' => [],
    'showBarcode' => null,
    'showCancel' => null,
])

<header class="barcode-topbar">
    <x-filament::icon-button
        color="gray"
        icon="heroicon-m-bars-3"
        label="Open navigation"
        x-on:click="sidebarOpen = true"
        class="icon-button barcode-topbar-menu"
    />

    <div>
        @if ($breadcrumbs !== [])
            <div class="barcode-brand barcode-breadcrumbs">
                @foreach ($breadcrumbs as $breadcrumb)
                    @if (! $loop->first)
                        <span>/</span>
                    @endif

                    @if (! empty($breadcrumb['href']))
                        <a href="{{ $breadcrumb['href'] }}" wire:navigate>{{ $breadcrumb['label'] }}</a>
                    @else
                        <span>{{ $breadcrumb['label'] }}</span>
                    @endif
                @endforeach
            </div>
        @endif

        <h1>{{ $title }}</h1>

        @if (filled($subtitle))
            <p>{{ $subtitle }}</p>
        @endif
    </div>

    @if (filled($showBarcode))
        <div class="barcode-topbar-actions">
            <x-filament::icon-button
                color="gray"
                icon="heroicon-m-qr-code"
                :label="__('barcode::app.operation.scan')"
                x-on:click="toggle($wire)"
                x-bind:class="{ 'is-active': active }"
                class="icon-button barcode-topbar-btn"
            />
        </div>
    @endif

    @if (filled($showCancel))
        <div class="barcode-topbar-actions">
            <x-filament::icon-button
                color="gray"
                icon="heroicon-m-x-mark"
                :label="__('barcode::app.navigation.back')"
                wire:click="discardMoveLineEdit"
                class="icon-button"
            />
        </div>
    @endif
</header>
