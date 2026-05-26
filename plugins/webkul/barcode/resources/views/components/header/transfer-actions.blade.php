<x-filament::icon-button
    color="gray"
    icon="heroicon-m-qr-code"
    :label="__('barcode::app.operation-search.placeholder')"
    x-on:click="toggle($wire)"
    x-bind:class="{ 'is-active': active }"
    class="icon-button barcode-topbar-btn"
/>
