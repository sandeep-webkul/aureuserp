@php
    $items = \Webkul\Barcode\Support\Navigation::items();
@endphp

<aside class="barcode-sidebar">
    <div class="barcode-sidebar__header">
        <div class="barcode-sidebar__eyebrow">{{ __('barcode::app.title') }}</div>
        <strong class="barcode-sidebar__title">Navigation</strong>
    </div>

    <nav class="barcode-sidebar__nav" aria-label="Barcode navigation">
        @foreach ($items as $item)
            @if ($item['disabled'])
                <button type="button" class="barcode-sidebar__item is-disabled" disabled aria-disabled="true">
                    <x-filament::icon :icon="$item['icon']" class="barcode-sidebar__icon" />
                    <span>{{ $item['label'] }}</span>
                    <small>Coming soon</small>
                </button>
            @else
                <a
                    href="{{ $item['href'] }}"
                    wire:navigate
                    @class([
                        'barcode-sidebar__item',
                        'is-active' => $item['active'],
                    ])
                >
                    <x-filament::icon :icon="$item['icon']" class="barcode-sidebar__icon" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endif
        @endforeach
    </nav>
</aside>
