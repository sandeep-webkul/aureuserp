<main class="barcode-page">
    <header class="barcode-topbar">
        <div>
            <div class="barcode-brand">{{ __('barcode::app.title') }}</div>
            <h1>{{ __('barcode::app.dashboard.operations') }}</h1>
        </div>
    </header>

    <section class="operation-grid">
        @forelse ($operationTypes as $operationType)
            <a class="operation-card" href="{{ route('barcode.transfers', $operationType) }}" wire:navigate>
                <div class="operation-card-copy">
                    <span>{{ $operationType->name }}</span>

                    @if ($operationType->warehouse?->name)
                        <small>{{ $operationType->warehouse->name }}</small>
                    @endif
                </div>

                <strong>{{ $operationType->waiting_count }}</strong>
            </a>
        @empty
            <div class="empty-state">
                <x-filament::icon icon="heroicon-o-inbox" class="empty-state-icon" />
                <div>{{ __('barcode::app.dashboard.empty') }}</div>
            </div>
        @endforelse
    </section>
</main>
