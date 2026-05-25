<main class="barcode-page" x-data="barcodeScanner('search', 'openOperation')">
    <header class="barcode-topbar">
        <x-filament::icon-button
            color="gray"
            icon="heroicon-m-chevron-left"
            :label="__('barcode::app.navigation.back')"
            :href="route('barcode.dashboard')"
            tag="a"
            wire:navigate
            class="icon-button"
        />
        <div>
            <div class="barcode-brand barcode-breadcrumbs">
                <a href="{{ route('barcode.dashboard') }}" wire:navigate>{{ __('barcode::app.title') }}</a>
                <span>/</span>
                <span>{{ __('barcode::app.dashboard.operations') }}</span>
            </div>
            <h1>{{ $operationType->name }}</h1>
        </div>
        <x-filament::icon-button
            color="gray"
            icon="heroicon-m-qr-code"
            :label="__('barcode::app.operation-search.placeholder')"
            x-on:click="toggle($wire)"
            x-bind:class="{ 'is-active': active }"
            class="icon-button barcode-topbar-btn"
        />
    </header>

    <div id="barcode-reader" class="barcode-reader" x-show="active" x-cloak></div>

    <div class="search-row">
        <form class="scan-form" wire:submit="openOperation">
            <x-filament::input.wrapper class="scan-field">
                <x-slot name="suffix">
                    <x-filament::icon-button
                        color="primary"
                        icon="heroicon-m-arrow-right"
                        :label="__('barcode::app.operation-search.open')"
                        type="submit"
                        size="sm"
                        class="scan-submit-button"
                    />
                </x-slot>

                <x-filament::input
                    type="search"
                    wire:model.live.debounce.250ms="search"
                    :placeholder="__('barcode::app.navigation.search')"
                />
            </x-filament::input.wrapper>
        </form>
    </div>

    @if ($operationNotice)
        <x-filament::callout icon="heroicon-o-information-circle" :color="$operationNoticeColor" class="notice">
            <x-slot name="heading">
                {{ __('barcode::app.operation-search.open') }}
            </x-slot>

            <x-slot name="description">
                {{ $operationNotice }}
            </x-slot>
        </x-filament::callout>
    @endif

    @if ($transfers->isEmpty())
        <section class="transfer-empty-state">
            <x-filament::empty-state
                icon="heroicon-o-inbox"
                :heading="__('barcode::app.transfers.empty')"
            >
                <x-slot name="description">
                    {{ __('barcode::app.operation-search.placeholder') }}
                </x-slot>
            </x-filament::empty-state>
        </section>
    @else
        <section class="transfer-grid">
            @foreach ($transfers as $transfer)
            <a class="transfer-card" href="{{ route('barcode.operation', [$operationType, $transfer, 'scan' => $search]) }}" wire:navigate>
                <div class="transfer-main">
                    <strong>{{ $transfer->name }}</strong>
                    <span class="transfer-partner">{{ $transfer->partner?->name ?? $transfer->origin }}</span>
                </div>
                <div class="transfer-meta">
                    <span class="state-badge">{{ $transfer->state?->value }}</span>
                    <time>{{ $transfer->scheduled_at?->format('M d') }}</time>
                </div>
            </a>
            @endforeach
        </section>
    @endif
</main>
