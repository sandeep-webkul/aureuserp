@props([
    'title',
    'subtitle' => null,
])

@if (filled($subtitle))
    <native:top-bar
        title="{{ $title }}"
        subtitle="{{ $subtitle }}"
        show-navigation-icon="true"
    ></native:top-bar>
@else
    <native:top-bar
        title="{{ $title }}"
        show-navigation-icon="true"
    ></native:top-bar>
@endif
