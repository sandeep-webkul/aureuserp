<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Webkul\Barcode\Support\NativeApp;

Route::get('/', function () {
    if (NativeApp::requestIsNative()) {
        if (Auth::check()) {
            return redirect()->route('barcode.dashboard');
        }

        return redirect()->route('barcode.login');
    }

    return redirect()->route('filament.admin.auth.login');
});

Route::redirect('/login', '/admin/login')
    ->name('login');
