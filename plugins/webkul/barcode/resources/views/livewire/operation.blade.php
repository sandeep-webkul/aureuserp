@php
    $editingMove = $editingMoveId ? $operation->moves->firstWhere('id', $editingMoveId) : null;
@endphp

<main class="barcode-page operation-screen {{ $editingMove ? 'is-editing-move' : '' }}" x-data="barcodeScanner('barcode', 'scan')">
    <header class="barcode-topbar">
        @if ($editingMove)
            <button type="button" class="icon-button" wire:click="discardMoveEdit" aria-label="{{ __('barcode::app.navigation.back') }}">‹</button>
        @else
            <a class="icon-button" href="{{ route('barcode.transfers', $operationType) }}" wire:navigate aria-label="{{ __('barcode::app.navigation.back') }}">‹</a>
        @endif
        <div>
            <div class="barcode-brand">{{ $operationType->name }}</div>
            <h1>{{ $operation->name }}</h1>
            <p>{{ $operation->partner?->name ?? $operation->origin }}</p>
        </div>
        <button type="button" class="icon-button barcode-topbar-btn" x-on:click="toggle($wire)" :class="{ 'is-active': active }" aria-label="{{ __('barcode::app.operation.scan') }}">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M3 4a1 1 0 0 1 1-1h1a1 1 0 0 1 0 2H4a1 1 0 0 1-1-1Zm5 0a1 1 0 0 1 1-1h1a1 1 0 0 1 0 2H9a1 1 0 0 1-1-1Zm5 0a1 1 0 0 1 1-1h1a1 1 0 0 1 0 2h-1a1 1 0 0 1-1-1Zm5 0a1 1 0 0 1 1-1h1a1 1 0 0 1 0 2h-1a1 1 0 0 1-1-1ZM3 9a1 1 0 0 1 1-1h1a1 1 0 0 1 0 2H4a1 1 0 0 1-1-1Zm5 0a1 1 0 0 1 1-1h4a1 1 0 0 1 0 2H9a1 1 0 0 1-1-1Zm7 0a1 1 0 0 1 1-1h1a1 1 0 0 1 0 2h-1a1 1 0 0 1-1-1ZM3 14a1 1 0 0 1 1-1h1a1 1 0 0 1 0 2H4a1 1 0 0 1-1-1Zm5 0a1 1 0 0 1 1-1h1a1 1 0 0 1 0 2H9a1 1 0 0 1-1-1Zm5 0a1 1 0 0 1 1-1h4a1 1 0 0 1 0 2h-4a1 1 0 0 1-1-1ZM3 19a1 1 0 0 1 1-1h4a1 1 0 0 1 0 2H4a1 1 0 0 1-1-1Zm7 0a1 1 0 0 1 1-1h1a1 1 0 0 1 0 2h-1a1 1 0 0 1-1-1Zm5 0a1 1 0 0 1 1-1h1a1 1 0 0 1 0 2h-1a1 1 0 0 1-1-1Z" clip-rule="evenodd"/>
            </svg>
        </button>
    </header>

    @if ($editingMove)
        @php
            $productImages = $editingMove->product?->images ?? [];
            $productImage = is_array($productImages) ? ($productImages[0] ?? null) : null;
            $productImageUrl = is_string($productImage) && $productImage !== '' ? (str_starts_with($productImage, 'http') || str_starts_with($productImage, '/') ? $productImage : asset('storage/'.$productImage)) : null;
            $tracking = $editingMove->product?->tracking?->value;
        @endphp

        <section class="move-editor">
            <div class="editor-product">
                <div class="editor-product-info">
                    <strong>⌁ {{ $editingMove->product?->reference ?? $editingMove->name }}</strong>
                    <span>{{ $editingMove->product?->name }}</span>
                    @if ($editingMove->product?->barcode)
                        <span>[{{ $editingMove->product->barcode }}]</span>
                    @endif
                    <span>{{ __('barcode::app.operation.source') }}: {{ $editingMove->sourceLocation?->full_name ?? $editingMove->sourceLocation?->name }}</span>
                </div>

                <div class="product-thumb product-thumb-large">
                    @if ($productImageUrl)
                        <img src="{{ $productImageUrl }}" alt="">
                    @else
                        <span>{{ mb_substr((string) $editingMove->product?->name, 0, 1) }}</span>
                    @endif
                </div>
            </div>

            <form class="editor-form" wire:submit="confirmMoveEdit">
                <div class="editor-quantity-row">
                    <input
                        type="number"
                        min="0"
                        max="{{ (float) $editingMove->product_uom_qty }}"
                        step="0.01"
                        wire:model="countedQuantities.{{ $editingMove->id }}"
                    >
                    <div class="editor-uom">{{ $editingMove->uom?->name }}</div>
                </div>

                <div class="editor-controls">
                    <button type="button" wire:click="setMoveQuantity({{ $editingMove->id }}, 0)">0</button>
                    <button type="button" wire:click="adjustMoveQuantity({{ $editingMove->id }}, -1)">-1</button>
                    <button type="button" wire:click="adjustMoveQuantity({{ $editingMove->id }}, 1)">+1</button>
                    <button type="submit" class="confirm-inline">✓</button>
                </div>

                @if ($tracking && $tracking !== 'qty')
                    <label class="lot-field">
                        <span>{{ $tracking === 'serial' ? 'Serial Number' : 'Lot Number' }}</span>
                        <input type="text" wire:model="editingLotName">
                    </label>
                @endif
            </form>

            <div class="stock-heading">
                <span></span>
                <strong>Quantity in Stock</strong>
                <span></span>
                <p>Select where else to pick the product from</p>
            </div>

            <div class="stock-options">
                @forelse ($sourceLocationOptions as $option)
                    <button type="button" class="stock-card">
                        <strong>{{ $option['location'] }}</strong>
                        <span>Available: {{ number_format((float) $option['available'], 2) }} / {{ number_format((float) $option['quantity'], 2) }} {{ $option['uom'] }}</span>
                    </button>
                @empty
                    <div class="empty-state">No stock locations found.</div>
                @endforelse
            </div>
        </section>

        <footer class="action-bar editor-action-bar">
            <button type="button" class="action-button" wire:click="discardMoveEdit">Discard</button>
            <button type="button" class="action-button danger" wire:click="confirmMoveEdit">Confirm</button>
        </footer>
    @else
        <div id="barcode-reader" class="barcode-reader" x-show="active" x-cloak></div>

        <form class="scan-form" wire:submit="scan">
            <input type="text" wire:model="barcode" placeholder="{{ __('barcode::app.operation.manual-scan') }}">
            <button type="submit">↵</button>
        </form>

        <div class="search-row">
            <input type="search" wire:model.live.debounce.250ms="moveSearch" placeholder="{{ __('barcode::app.operation.search') }}">
        </div>

        @if ($notice)
            <div class="notice">{{ $notice }}</div>
        @endif

        <section class="moves-list">
            <div class="section-title">{{ __('barcode::app.operation.moves') }}</div>

            @forelse ($moves as $move)
                @php
                    $productImages = $move->product?->images ?? [];
                    $productImage = is_array($productImages) ? ($productImages[0] ?? null) : null;
                    $productImageUrl = is_string($productImage) && $productImage !== '' ? (str_starts_with($productImage, 'http') || str_starts_with($productImage, '/') ? $productImage : asset('storage/'.$productImage)) : null;
                    $countedQuantity = (float) ($countedQuantities[$move->id] ?? 0);
                    $demandQuantity = (float) $move->product_uom_qty;
                    $countState = $countedQuantity >= $demandQuantity && $demandQuantity > 0 ? 'is-complete' : ($countedQuantity > 0 ? 'is-partial' : '');
                @endphp

                <article
                    id="move-{{ $move->id }}"
                    class="move-row {{ $selectedMoveId === $move->id ? 'is-selected' : '' }} {{ $countState }}"
                    wire:key="move-{{ $move->id }}"
                >
                    <div class="move-open">
                        <div class="move-main">
                            <strong>{{ $move->product?->reference ?? $move->name }}</strong>
                            <span>{{ $move->product?->name }}</span>
                            <span>{{ __('barcode::app.operation.source') }}: {{ $move->sourceLocation?->full_name ?? $move->sourceLocation?->name }}</span>
                            @if ($move->product?->barcode)
                                <span>[{{ $move->product->barcode }}]</span>
                            @endif
                            <div class="move-quantity">
                                <strong>{{ number_format($countedQuantity, 0) }} / {{ number_format($demandQuantity, 0) }}</strong>
                                <span>{{ $move->uom?->name }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="move-controls">
                        <div class="move-tools">
                            <div class="product-thumb">
                                @if ($productImageUrl)
                                    <img src="{{ $productImageUrl }}" alt="">
                                @else
                                    <span>{{ mb_substr((string) $move->product?->name, 0, 1) }}</span>
                                @endif
                            </div>
                            <button type="button" class="edit-button" wire:click="editMove({{ $move->id }})" aria-label="Edit {{ $move->product?->name }}">✎</button>
                        </div>

                        <div class="step-actions">
                            @if ($countedQuantity <= 0)
                                <button type="button" class="step-button" wire:click="setMoveQuantity({{ $move->id }}, {{ $demandQuantity }})">+{{ number_format($demandQuantity, 0) }}</button>
                            @elseif ($countedQuantity >= $demandQuantity)
                                <button type="button" class="step-button" wire:click="adjustMoveQuantity({{ $move->id }}, -1)">-1</button>
                            @else
                                <button type="button" class="step-button" wire:click="adjustMoveQuantity({{ $move->id }}, 1)">+1</button>
                                <button type="button" class="step-button" wire:click="adjustMoveQuantity({{ $move->id }}, -1)">-1</button>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="empty-state">{{ __('barcode::app.operation.empty-moves') }}</div>
            @endforelse
        </section>

        <footer class="action-bar">
            @foreach ($actions as $action)
                <button type="button" class="action-button {{ $action['variant'] }}" wire:click="executeAction('{{ $action['key'] }}')">
                    {{ $action['label'] }}
                </button>
            @endforeach
        </footer>
    @endif
</main>
