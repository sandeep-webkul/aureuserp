<main class="barcode-page" x-data="barcodeScanner('search', 'openOperation')">
    <header class="barcode-topbar">
        <a class="icon-button" href="{{ route('barcode.dashboard') }}" wire:navigate aria-label="{{ __('barcode::app.navigation.back') }}">‹</a>
        <div>
            <div class="barcode-brand">{{ __('barcode::app.title') }} / {{ __('barcode::app.dashboard.operations') }}</div>
            <h1>{{ $operationType->name }}</h1>
        </div>
    </header>

    <section class="scan-band">
        <button type="button" class="scan-toggle" x-on:click="toggle($wire)">
            <span>⌗</span>
            {{ __('barcode::app.operation-search.placeholder') }}
        </button>
        <div id="barcode-reader" class="barcode-reader" x-show="active" x-cloak></div>
    </section>

    <div class="search-row">
        <form class="scan-form" wire:submit="openOperation">
            <input type="search" wire:model.live.debounce.250ms="search" placeholder="{{ __('barcode::app.navigation.search') }}">
            <button type="submit">{{ __('barcode::app.operation-search.open') }}</button>
        </form>
    </div>

    @if ($operationNotice)
        <div class="notice">{{ $operationNotice }}</div>
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
            <div class="empty-state">{{ __('barcode::app.transfers.empty') }}</div>
        @endforelse
    </section>
</main>
