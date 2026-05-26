<?php

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\SetUpPanel;
use Illuminate\Support\Facades\Route;
use Webkul\Barcode\Livewire\Dashboard;
use Webkul\Barcode\Livewire\Operation;
use Webkul\Barcode\Livewire\Transfers;

Route::middleware(['web'])->group(function (): void {
    Route::get('barcode', function () {
        if (auth()->check()) {
            return redirect()->route('barcode.dashboard');
        }

        return redirect()->route('filament.admin.auth.login');
    });
});

Route::middleware(['web', SetUpPanel::class.':admin', Authenticate::class])->group(function (): void {
    Route::prefix('admin/barcode')->name('barcode.')->group(function (): void {
        Route::get('/assets/{file}', function (string $file) {
            abort_unless(in_array($file, ['barcode.css', 'barcode.js', 'html5-qrcode.min.js'], true), 404);

            return response()->file(__DIR__.'/../resources/dist/'.$file);
        })->name('asset');

        Route::get('/', Dashboard::class)->name('dashboard');
        Route::get('/operations/{operationType}', Transfers::class)->name('transfers');
        Route::get('/operations/{operationType}/transfers/{operation}', Operation::class)->name('operation');
    });
});
