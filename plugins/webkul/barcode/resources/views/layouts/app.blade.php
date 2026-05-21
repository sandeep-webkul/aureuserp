<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? __('barcode::app.title') }}</title>
        @livewireStyles
        <style>
            {!! file_get_contents(base_path('plugins/webkul/barcode/resources/dist/barcode.css')) !!}
        </style>
    </head>
    <body class="barcode-app">
        {{ $slot }}

        @livewireScripts
        <script src="https://unpkg.com/html5-qrcode" defer></script>
        <script>
            {!! file_get_contents(base_path('plugins/webkul/barcode/resources/dist/barcode.js')) !!}
        </script>
    </body>
</html>
