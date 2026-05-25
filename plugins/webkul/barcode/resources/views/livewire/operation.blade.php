@php
    $editingMoveLine = $editingMoveLineId ? $operation->moveLines->firstWhere('id', $editingMoveLineId) : null;
    $allMoveLinesCounted = $moveLines->isNotEmpty()
        && $moveLines->every(fn ($moveLine) => (float) ($countedMoveLineQuantities[$moveLine->id] ?? 0) >= (float) $moveLine->qty);
    $hasAnyCountedMoveLine = $moveLines->contains(fn ($moveLine) => (float) ($countedMoveLineQuantities[$moveLine->id] ?? 0) > 0);
@endphp

<main class="barcode-page operation-screen {{ $editingMoveLine ? 'is-editing-move' : '' }}" x-data="barcodeScanner('barcode', 'scan')">
    <header class="barcode-topbar">
        @if ($editingMoveLine)
            <button type="button" class="icon-button" wire:click="discardMoveLineEdit" aria-label="{{ __('barcode::app.navigation.back') }}">‹</button>
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

        @unless ($editingMoveLine)
            <div class="topbar-menu" x-on:click.outside="closeActionMenu()">
                <button type="button" class="icon-button topbar-menu-btn" x-on:click="toggleActionMenu()" :class="{ 'is-active': actionMenuOpen }" aria-label="Actions">
                    ⋮
                </button>

                <div class="topbar-dropdown" x-show="actionMenuOpen" x-cloak>
                    <div class="topbar-dropdown-list">
                        @foreach ($actions as $action)
                            @if ($action['key'] === 'cancel')
                                <button type="button" class="topbar-dropdown-item danger" x-on:click="requestAction('{{ $action['key'] }}', '{{ addslashes($action['label']) }}')">
                                    <span class="topbar-dropdown-label">{{ $action['label'] }}</span>
                                </button>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        @endunless
    </header>

    @if ($editingMoveLine)
        @php
            $productImages = $editingMoveLine->product?->images ?? [];
            $productImage = is_array($productImages) ? ($productImages[0] ?? null) : null;
            $productImageUrl = is_string($productImage) && $productImage !== '' ? (str_starts_with($productImage, 'http') || str_starts_with($productImage, '/') ? $productImage : asset('storage/'.$productImage)) : null;
            $tracking = $editingMoveLine->product?->tracking?->value;
        @endphp

        <section class="move-editor">
            <div class="editor-product">
                <div class="editor-product-info">
                    <strong>⌁ {{ $editingMoveLine->product?->reference ?? $editingMoveLine->reference }}</strong>

                    <span>{{ $editingMoveLine->product?->name }}</span>

                    @if ($editingMoveLine->product?->barcode)
                        <span>[{{ $editingMoveLine->product->barcode }}]</span>
                    @endif
                    
                    <span>{{ __('barcode::app.operation.source') }}: {{ $editingMoveLine->sourceLocation?->full_name ?? $editingMoveLine->sourceLocation?->name }}</span>
                </div>

                <div class="product-thumb product-thumb-large">
                    @if ($productImageUrl)
                        <img src="{{ $productImageUrl }}" alt="">
                    @else
                        <span>{{ mb_substr((string) $editingMoveLine->product?->name, 0, 1) }}</span>
                    @endif
                </div>
            </div>

            <form class="editor-form" wire:submit="confirmMoveLineEdit">
                <div class="editor-quantity-row">
                    <input
                        type="number"
                        min="0"
                        max="{{ (float) $editingMoveLine->qty }}"
                        step="0.01"
                        wire:model="countedMoveLineQuantities.{{ $editingMoveLine->id }}"
                    >
                    <div class="editor-uom">{{ $editingMoveLine->uom?->name }}</div>
                </div>

                <div class="editor-controls">
                    <button type="button" wire:click="setMoveLineQuantity({{ $editingMoveLine->id }}, 0)">0</button>
                    <button type="button" wire:click="adjustMoveLineQuantity({{ $editingMoveLine->id }}, -1)">-1</button>
                    <button type="button" wire:click="adjustMoveLineQuantity({{ $editingMoveLine->id }}, 1)">+1</button>
                    <button type="submit" class="confirm-inline">✓</button>
                </div>

                @if ($tracking && $tracking !== 'qty')
                    <label class="lot-field">
                        <span>{{ $tracking === 'serial' ? 'Serial Number' : 'Lot Number' }}</span>
                        <input type="text" wire:model="editingMoveLineLotName">
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
                @forelse ($moveLineSourceLocationOptions as $option)
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
            <button type="button" class="action-button" wire:click="discardMoveLineEdit">Discard</button>
            <button type="button" class="action-button danger" wire:click="confirmMoveLineEdit">Confirm</button>
        </footer>
    @else
        <div id="barcode-reader" class="barcode-reader" x-show="active" x-cloak></div>

        <form class="scan-form" wire:submit="scan">
            <input type="search" wire:model.live.debounce.250ms="barcode" placeholder="{{ __('barcode::app.operation.manual-scan') }}" autocomplete="off">
            <button type="submit">↵</button>
        </form>

        @if ($notice)
            <div class="notice">{{ $notice }}</div>
        @endif

        <section class="moves-list">
            <div class="section-title">{{ __('barcode::app.operation.moves') }}</div>

            @forelse ($moveLines as $moveLine)
                @php
                    $productImages = $moveLine->product?->images ?? [];
                    $productImage = is_array($productImages) ? ($productImages[0] ?? null) : null;
                    $productImageUrl = is_string($productImage) && $productImage !== '' ? (str_starts_with($productImage, 'http') || str_starts_with($productImage, '/') ? $productImage : asset('storage/'.$productImage)) : null;
                    $countedQuantity = (float) ($countedMoveLineQuantities[$moveLine->id] ?? 0);
                    $demandQuantity = (float) $moveLine->qty;
                    $countState = $countedQuantity >= $demandQuantity && $demandQuantity > 0 ? 'is-complete' : ($countedQuantity > 0 ? 'is-partial' : '');
                @endphp

                <article
                    id="line-{{ $moveLine->id }}"
                    class="move-row {{ $selectedMoveLineId === $moveLine->id ? 'is-selected' : '' }} {{ $countState }}"
                    wire:key="line-{{ $moveLine->id }}"
                >
                    <div class="move-open">
                        <div class="move-main">
                            <strong>{{ $moveLine->product?->reference ?? $moveLine->reference }}</strong>
                            <span>{{ $moveLine->product?->name }}</span>
                            <span>{{ __('barcode::app.operation.source') }}: {{ $moveLine->sourceLocation?->full_name ?? $moveLine->sourceLocation?->name }}</span>
                            @if ($moveLine->product?->barcode)
                                <span>[{{ $moveLine->product->barcode }}]</span>
                            @endif
                            <div class="move-quantity move-quantity--{{ $countState !== '' ? str_replace('is-', '', $countState) : 'idle' }}">
                                <strong>{{ number_format($countedQuantity, 0) }} / {{ number_format($demandQuantity, 0) }}</strong>
                                <span>{{ $moveLine->uom?->name }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="move-controls">
                        <div class="move-tools">
                            <div class="product-thumb">
                                @if ($productImageUrl)
                                    <img src="{{ $productImageUrl }}" alt="">
                                @else
                                    <span>{{ mb_substr((string) $moveLine->product?->name, 0, 1) }}</span>
                                @endif
                            </div>
                            <button type="button" class="edit-button" wire:click="editMoveLine({{ $moveLine->id }})" aria-label="Edit {{ $moveLine->product?->name }}">✎</button>
                        </div>

                        <div class="step-actions">
                            @if ($countedQuantity <= 0)
                                <button type="button" class="step-button" wire:click="setMoveLineQuantity({{ $moveLine->id }}, {{ $demandQuantity }})">+{{ number_format($demandQuantity, 0) }}</button>
                            @elseif ($countedQuantity >= $demandQuantity)
                                <button type="button" class="step-button" wire:click="adjustMoveLineQuantity({{ $moveLine->id }}, -1)">-1</button>
                            @else
                                <button type="button" class="step-button" wire:click="adjustMoveLineQuantity({{ $moveLine->id }}, 1)">+1</button>
                                <button type="button" class="step-button" wire:click="adjustMoveLineQuantity({{ $moveLine->id }}, -1)">-1</button>
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
                @if ($action['key'] === 'cancel')
                    @continue
                @endif

                @if ($action['key'] === 'validate' || $action['key'] === 'done')
                    <button
                        type="button"
                        class="action-button {{ $allMoveLinesCounted ? 'primary' : '' }}"
                        x-on:click="requestValidate('{{ addslashes($action['label']) }}', {{ Js::from($backorderMoveLines) }}, {{ $hasAnyCountedMoveLine ? 'true' : 'false' }}, {{ $shouldAskBackorder ? 'true' : 'false' }})"
                    >
                        {{ $action['label'] }}
                    </button>
                @else
                    <button type="button" class="action-button {{ $action['variant'] }}" x-on:click="requestAction('{{ $action['key'] }}', '{{ addslashes($action['label']) }}')">
                        {{ $action['label'] }}
                    </button>
                @endif
            @endforeach
        </footer>

        <div class="confirm-backdrop"
            x-show="confirmPending"
            x-cloak
            x-transition:enter="confirm-backdrop-enter"
            x-transition:enter-start="confirm-backdrop-enter-start"
            x-transition:enter-end="confirm-backdrop-enter-end"
            x-transition:leave="confirm-backdrop-enter"
            x-transition:leave-start="confirm-backdrop-enter-end"
            x-transition:leave-end="confirm-backdrop-enter-start"
        >
            <div class="confirm-dialog"
                x-show="confirmPending"
                x-transition:enter="confirm-dialog-enter"
                x-transition:enter-start="confirm-dialog-enter-start"
                x-transition:enter-end="confirm-dialog-enter-end"
                x-transition:leave="confirm-dialog-enter"
                x-transition:leave-start="confirm-dialog-enter-end"
                x-transition:leave-end="confirm-dialog-enter-start"
            >
                {{-- Backorder warning --}}
                <template x-if="confirmMode === 'backorder'">
                    <div>
                        <h3 class="confirm-dialog-title">{{ __('barcode::app.actions.backorder-title') }}</h3>
                        <p class="confirm-dialog-subtitle">{{ __('barcode::app.actions.backorder-prompt') }}</p>

                        <table class="backorder-table">
                            <thead>
                                <tr>
                                    <th>{{ __('barcode::app.actions.backorder-col-product') }}</th>
                                    <th>{{ __('barcode::app.actions.backorder-col-done-todo') }}</th>
                                    <th>{{ __('barcode::app.actions.backorder-col-backorder') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="row in backorderMoveLines" :key="row.id">
                                    <tr>
                                        <td x-text="row.name"></td>
                                        <td class="backorder-qty" x-text="row.counted + ' / ' + row.required + ' ' + row.uom"></td>
                                        <td x-text="row.backorder + ' ' + row.uom"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>

                        <div class="confirm-buttons">
                            <button type="button" class="action-button" x-on:click="cancelAction()">{{ __('barcode::app.actions.stay-on-transfer') }}</button>
                            <button type="button" class="action-button danger" x-on:click="$wire.executeAction(confirmPending, true); cancelAction()">No Backorder</button>
                            <button type="button" class="action-button primary" x-on:click="$wire.executeAction(confirmPending, false); cancelAction()">{{ __('barcode::app.actions.validate') }}</button>
                        </div>
                    </div>
                </template>

                {{-- Simple confirmation --}}
                <template x-if="confirmMode === 'simple'">
                    <div>
                        <p>{{ __('barcode::app.actions.confirm-prompt') }} <strong x-text="confirmLabel"></strong>?</p>
                        <div class="confirm-buttons">
                            <button type="button" class="action-button" x-on:click="cancelAction()">{{ __('barcode::app.actions.cancel') }}</button>
                            <button type="button" class="action-button primary" x-on:click="$wire.executeAction(confirmPending); cancelAction()">{{ __('barcode::app.actions.confirm') }}</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    @endif
</main>
