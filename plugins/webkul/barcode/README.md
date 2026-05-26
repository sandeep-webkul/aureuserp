# Barcode Plugin

Barcode is a mobile-first operations app for AureusERP. It currently covers inventory operations and is structured so the same shell can later include manufacturing orders and inventory adjustments.

This plugin is distributed by copy/paste into the host application's `plugins` directory. Because of that, a few app-level changes must still be applied manually outside the plugin.

## What this plugin includes

- Barcode dashboard at `/admin/barcode`
- Operation type listing
- Transfer listing
- Operation detail and count flow
- Web scanner integration using bundled `html5-qrcode`
- NativePHP-aware layout, sidebar, and header behavior
- Dedicated barcode login at `/admin/barcode/login`

## Prerequisites

The host app must already have:

- `inventories` plugin installed
- `manufacturing` plugin installed
- Filament admin panel configured

If you want to package the app for iOS / Android, the host app must also have:

- `nativephp/mobile`

## Installation

### 1. Copy the plugin

Copy this folder into the host application:

```text
plugins/webkul/barcode
```

### 2. Make sure plugin composer files are merged

This plugin depends on the root project loading plugin `composer.json` files through Composer Merge Plugin. The host app should already have this in the root `composer.json`:

```json
"extra": {
    "merge-plugin": {
        "include": [
            "plugins/*/*/composer.json"
        ]
    }
}
```

### 3. Refresh Composer autoload and package discovery

Run:

```bash
composer dump-autoload
php artisan package:discover --ansi
```

### 4. Install the plugin

Run:

```bash
php artisan barcode:install --no-interaction
```

This install command installs plugin dependencies declared by the barcode package:

- `inventories`
- `manufacturing`

### 5. Publish / rebuild Filament assets

Run:

```bash
php artisan filament:assets --no-interaction
```

### 6. Verify the routes

After installation, these routes should exist:

- `/barcode`
- `/admin/barcode/login`
- `/admin/barcode`
- `/admin/barcode/operations/{operationType}`
- `/admin/barcode/operations/{operationType}/transfers/{operation}`

## Web login flow

The plugin defines its own login entrypoint:

- `/admin/barcode/login`

Unauthenticated access to `/admin/barcode` should redirect there, not to the generic Filament admin login page.

After login, the user is redirected to:

- `/admin/barcode`

This behavior is implemented inside the plugin by:

- `src/Livewire/Auth/Login.php`
- `src/Http/Middleware/Authenticate.php`
- `src/Http/Responses/LoginResponse.php`
- `routes/web.php`

## Scanner notes

The scanner stays open-source. This plugin does **not** use the paid NativePHP scanner API.

It uses:

- bundled `html5-qrcode`
- served locally from `plugins/webkul/barcode/resources/dist/html5-qrcode.min.js`

That is important for app builds, because relying on a CDN inside the webview is not stable enough.

## NativePHP support

The plugin supports:

- web browser
- iPhone / iPad app shell
- Android app shell

The plugin itself contains:

- `nativephp.json`
- NativePHP-aware navigation/header decisions in `src/Support/NativeApp.php`

However, the host app still needs a few manual changes outside the plugin.

## Manual host app changes outside the plugin

These changes are required because a distributable plugin cannot safely rewrite root app files.

### 1. Root route bootstrap in `routes/web.php`

Jump / NativePHP may open `/` first, not `/admin/barcode`. Because of that, the host app root route must forward native requests into the barcode app.

Required host-app logic:

```php
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
```

This change belongs in the host app:

- `routes/web.php`

### 2. Login alias in `routes/web.php`

Keep a normal web login alias for the browser/admin panel:

```php
Route::redirect('/login', '/admin/login')->name('login');
```

### 3. NativePHP start URL in `.env`

The host app must point NativePHP to the barcode route:

```dotenv
NATIVEPHP_START_URL=/admin/barcode
```

Also keep these values in `.env.example`:

```dotenv
NATIVEPHP_APP_ID=com.example.aureuserp.barcode
NATIVEPHP_APP_VERSION=DEBUG
NATIVEPHP_APP_VERSION_CODE=1
NATIVEPHP_START_URL=/admin/barcode
```

### 4. NativePHP config in `config/nativephp.php`

The host app should delegate barcode-specific values to the plugin:

```php
use Webkul\Barcode\Support\NativeApp;

'start_url' => env('NATIVEPHP_START_URL', NativeApp::startUrl()),
'permissions' => NativeApp::iosPermissions(),
```

This keeps camera permission text and the app start path aligned with the plugin.

### 5. HTTPS forcing in `app/Providers/AppServiceProvider.php`

If the host app forces `https` for every production request, Jump can break because it proxies the app through local HTTP.

Required host-app adjustment:

```php
public function boot(): void
{
    $isJumpRuntime = filled(getenv('JUMP_BRIDGE_PORT'));

    if (app()->environment('production') && ! $isJumpRuntime) {
        URL::forceScheme('https');
    }
}
```

Without this, app/webview startup can fail with:

- black screen
- login page without CSS
- broken redirects inside Jump

### 6. iOS webview camera permission patch

The host app currently needs a manual NativePHP iOS template change so `html5-qrcode` can access the camera inside the app shell.

Patched file:

- `vendor/nativephp/mobile/resources/xcode/NativePHP/ContentView.swift`

Required behavior:

- `Coordinator` must implement `WKUIDelegate`
- `webView(_:requestMediaCapturePermissionFor:initiatedByFrame:type:decisionHandler:)` must grant permission
- the created `WKWebView` must set `uiDelegate`

This is a vendor patch. If `nativephp/mobile` is updated, or the iOS template is regenerated, re-apply it.

### 7. Android camera permission verification

The plugin declares Android camera requirements in:

- `plugins/webkul/barcode/nativephp.json`

That manifest includes:

- `android.permission.CAMERA`
- `android.hardware.camera.any`

You should verify that the final Android build actually merges those into:

- `nativephp/android/app/src/main/AndroidManifest.xml`

If your build process does not merge plugin manifest entries, add them manually in the host app manifest.

### 8. Bootstrap providers

The host app must load the barcode service provider.

For this repository, that currently exists in:

- `bootstrap/providers.php`

```php
use Webkul\Barcode\BarcodeServiceProvider;

return [
    // ...
    BarcodeServiceProvider::class,
];
```

If the consuming app relies only on Composer package discovery and plugin composer merge, keep that path consistent. If it uses explicit provider registration, add the barcode provider manually.

## Files inside the plugin that drive this setup

- `src/BarcodeServiceProvider.php`
- `src/BarcodePlugin.php`
- `src/Support/NativeApp.php`
- `src/Support/Navigation.php`
- `src/Livewire/Auth/Login.php`
- `src/Http/Middleware/Authenticate.php`
- `src/Http/Responses/LoginResponse.php`
- `routes/web.php`
- `nativephp.json`

## Post-install checklist

Use this checklist after copying the plugin:

1. `composer dump-autoload`
2. `php artisan package:discover --ansi`
3. `php artisan barcode:install --no-interaction`
4. `php artisan filament:assets --no-interaction`
5. verify `/admin/barcode/login`
6. verify `/admin/barcode`
7. verify `/barcode` redirects correctly
8. if testing NativePHP / Jump, fully restart the runtime after changing:
   - `NATIVEPHP_START_URL`
   - `routes/web.php`
   - `config/nativephp.php`

## Notes for future modules

The current navigation already reserves space for:

- Inventory Operations
- Manufacturing Orders
- Inventory Adjustments

Only inventory operations are active right now. The sidebar/header shell is already structured so those additional modules can be added without replacing the navigation system again.
