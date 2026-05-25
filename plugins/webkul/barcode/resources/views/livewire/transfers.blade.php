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
            <div class="barcode-brand">{{ __('barcode::app.title') }} / {{ __('barcode::app.dashboard.operations') }}</div>
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
                <x-filament::input
                    type="search"
                    wire:model.live.debounce.250ms="search"
                    :placeholder="__('barcode::app.navigation.search')"
                />
            </x-filament::input.wrapper>
            <x-filament::button type="submit" color="primary">
                {{ __('barcode::app.operation-search.open') }}
            </x-filament::button>
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

    <section class="transfer-grid">
        @forelse ($transfers as $transfer)
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
        @empty
            <div class="empty-state">
                <x-filament::icon icon="heroicon-o-inbox" class="empty-state-icon" />
                <div>{{ __('barcode::app.transfers.empty') }}</div>
            </div>
        @endforelse
    </section>
</main>
