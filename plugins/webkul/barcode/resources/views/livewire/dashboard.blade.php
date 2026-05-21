<main class="barcode-page">
    <header class="barcode-topbar">
        <div>
            <div class="barcode-brand">{{ __('barcode::app.title') }}</div>
            <h1>{{ __('barcode::app.dashboard.operations') }}</h1>
        </div>
    </header>

    <form class="scan-form" wire:submit="openOperation">
        <input type="text" wire:model="operationBarcode" autofocus placeholder="{{ __('barcode::app.operation-search.placeholder') }}">
        <button type="submit">{{ __('barcode::app.operation-search.open') }}</button>
    </form>

    @if ($operationNotice)
        <div class="notice">{{ $operationNotice }}</div>
    @endif

    @if ($matchingOperations?->isNotEmpty())
        <section class="transfer-grid">
            @foreach ($matchingOperations as $transfer)
                <a class="transfer-card" href="{{ route('barcode.operation', [$transfer->operationType, $transfer, 'scan' => $operationBarcode]) }}" wire:navigate>
                    <div class="transfer-main">
                        <strong>{{ $transfer->name }}</strong>
                        <span class="transfer-partner">{{ $transfer->operationType?->name }} · {{ $transfer->partner?->name ?? $transfer->origin }}</span>
                    </div>
                    <div class="transfer-meta">
                        <span class="state-badge">{{ $transfer->state?->value }}</span>
                        <time>{{ $transfer->scheduled_at?->format('M d') }}</time>
                    </div>
                </a>
            @endforeach
        </section>
    @else
        <section class="operation-grid">
        @forelse ($operationTypes as $operationType)
            <a class="operation-card" href="{{ route('barcode.transfers', $operationType) }}" wire:navigate>
                <span>{{ $operationType->name }}</span>
                <strong>{{ $operationType->waiting_count }}</strong>
            </a>
        @empty
            <div class="empty-state">{{ __('barcode::app.dashboard.empty') }}</div>
        @endforelse
        </section>
    @endif
</main>
