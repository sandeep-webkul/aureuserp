@props([
    'title',
    'subtitle' => null,
])

@if (filled($subtitle))
    <native:top-bar
        title="{{ $title }}"
        subtitle="{{ $subtitle }}"
        show-navigation-icon="true"
    >
        <native:top-bar-action
            id="home"
            label="Home"
            icon="home"
            url="barcode"
        />
    </native:top-bar>
@else
    <native:top-bar
        title="{{ $title }}"
        show-navigation-icon="true"
    >
        <native:top-bar-action
            id="home"
            label="Home"
            icon="home"
            url="barcode"
        />
    </native:top-bar>
@endif
