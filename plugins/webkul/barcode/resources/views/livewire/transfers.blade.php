<main class="barcode-page" x-data="barcodeScanner('search', 'openOperation')">
    <header class="barcode-topbar">
        <a class="icon-button" href="{{ route('barcode.dashboard') }}" wire:navigate aria-label="{{ __('barcode::app.navigation.back') }}">‹</a>
        <div>
            <div class="barcode-brand">{{ __('barcode::app.title') }} / {{ __('barcode::app.dashboard.operations') }}</div>
            <h1>{{ $operationType->name }}</h1>
        </div>
        <button type="button" class="icon-button barcode-topbar-btn" x-on:click="toggle($wire)" :class="{ 'is-active': active }" aria-label="{{ __('barcode::app.operation-search.placeholder') }}">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M3 4a1 1 0 0 1 1-1h1a1 1 0 0 1 0 2H4a1 1 0 0 1-1-1Zm5 0a1 1 0 0 1 1-1h1a1 1 0 0 1 0 2H9a1 1 0 0 1-1-1Zm5 0a1 1 0 0 1 1-1h1a1 1 0 0 1 0 2h-1a1 1 0 0 1-1-1Zm5 0a1 1 0 0 1 1-1h1a1 1 0 0 1 0 2h-1a1 1 0 0 1-1-1ZM3 9a1 1 0 0 1 1-1h1a1 1 0 0 1 0 2H4a1 1 0 0 1-1-1Zm5 0a1 1 0 0 1 1-1h4a1 1 0 0 1 0 2H9a1 1 0 0 1-1-1Zm7 0a1 1 0 0 1 1-1h1a1 1 0 0 1 0 2h-1a1 1 0 0 1-1-1ZM3 14a1 1 0 0 1 1-1h1a1 1 0 0 1 0 2H4a1 1 0 0 1-1-1Zm5 0a1 1 0 0 1 1-1h1a1 1 0 0 1 0 2H9a1 1 0 0 1-1-1Zm5 0a1 1 0 0 1 1-1h4a1 1 0 0 1 0 2h-4a1 1 0 0 1-1-1ZM3 19a1 1 0 0 1 1-1h4a1 1 0 0 1 0 2H4a1 1 0 0 1-1-1Zm7 0a1 1 0 0 1 1-1h1a1 1 0 0 1 0 2h-1a1 1 0 0 1-1-1Zm5 0a1 1 0 0 1 1-1h1a1 1 0 0 1 0 2h-1a1 1 0 0 1-1-1Z" clip-rule="evenodd"/>
            </svg>
        </button>
    </header>

    <div id="barcode-reader" class="barcode-reader" x-show="active" x-cloak></div>

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
