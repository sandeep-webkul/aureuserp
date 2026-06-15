# AureusERP — Project Review

**Reviewed:** 2026-06-15
**Stack:** Laravel 13 · Filament 5 · PHP 8.3.30 · Livewire 4 · Laravel Sanctum 4
**Scale:** 28 Webkul plugins · ~5,300 PHP files · 343 migrations · 79 PHP test files

This document lists the highest-signal, actionable issues found during review, grouped by
category. Each item has a location, severity, and a concrete fix. Theoretical/style-only
nitpicks are intentionally excluded.

---

## Summary

| Category | High | Medium | Low |
|---|---|---|---|
| Security | 2 | 1 | 2 |
| Architecture & Code Quality | 2 | 3 | 1 |
| Testing & Coverage | 1 | 2 | 1 |
| Tooling & Docs | 0 | 1 | 2 |

**Top priority:** the two HIGH security items (API login throttling, Sanctum token expiration)
are one-line fixes with real risk reduction. The biggest structural risk is duplicated write
logic between fat API controllers and fat Filament resources, combined with thin test coverage.

---

## 1. Security

### 1.1 — HIGH — No brute-force throttling on API login
- **Location:** `plugins/webkul/security/src/Http/Controllers/API/V1/AuthController.php:29` (`login`), route `plugins/webkul/security/routes/api.php:8`
- **Issue:** No `throttle` middleware on the login route and no global API rate limiter (`bootstrap/app.php` defines none). Enables unlimited credential-stuffing / brute-force.
- **Fix:** Add `->middleware('throttle:6,1')` to the login route, or register a named `RateLimiter::for('login', ...)` limiter and apply it.

### 1.2 — HIGH — Sanctum API tokens never expire
- **Location:** `config/sanctum.php:53` → `'expiration' => null`
- **Issue:** Tokens issued by the login endpoint are valid forever; a stolen bearer token cannot age out.
- **Fix:** Set a finite expiration (e.g. `'expiration' => 60 * 24`) and schedule `sanctum:prune-expired`.

### 1.3 — MEDIUM — Arbitrary artisan command execution via plugin install action
- **Location:** `plugins/webkul/plugin-manager/src/Filament/Resources/PluginResource.php:158` (also `InstallCommand.php:441`)
- **Issue:** Runs `exec("...artisan {$record->name}:install")`. Input is `escapeshellarg`-wrapped (no shell injection) and gated by `update_plugin_manager_plugin` permission, but anyone who can edit a Plugin record's `name` can trigger an arbitrary registered artisan command as the web user.
- **Fix:** Validate `$record->name` against an allow-list of known plugin slugs (`^[a-z0-9-]+$` + existence check) before building the command.

### 1.4 — LOW — `Invitation` model is fully mass-assignable
- **Location:** `plugins/webkul/security/src/Models/Invitation.php:16` → `protected $guarded = []`
- **Issue:** Records are created server-side only today (no current exploit), but any future `Invitation::create($request->...)` would be unsafe.
- **Fix:** Replace with an explicit `$fillable` list.

### 1.5 — LOW — `.env` on disk holds real secrets (operational)
- **Location:** project root `.env`
- **Issue:** Contains populated `APP_KEY`, `DB_PASSWORD`, `AWS_SECRET_ACCESS_KEY`. Correctly git-ignored and **not** in history (verified) — not committed. Flagged for operational hardening only.
- **Fix:** Ensure the deployed `.env` is never web-served; scope AWS keys to least privilege.

> **Verified clean:** no SQL injection (all `whereRaw`/`DB::raw` use bound params or literals), no `eval`, no `$request->all()` mass-assignment, CSRF protection on web panels, signed invitation-accept route.

---

## 2. Architecture & Code Quality

### 2.1 — HIGH — Duplicated write paths per entity (controller vs. resource)
- **Location:** e.g. `plugins/webkul/accounts/src/Http/Controllers/API/V1/InvoiceController.php` (26 KB; `syncInvoiceLines()`, `preparePaymentData()`) vs. the corresponding Filament `InvoiceResource`
- **Issue:** Business logic for the same entity is re-implemented in both the API controller and the Filament resource, creating two divergent write paths — a prime source of data-integrity drift.
- **Fix:** Extract a single Service/Action class per entity (e.g. `InvoiceService::syncLines()`) and call it from both the controller and the resource.

### 2.2 — HIGH — Fat models carrying orchestration logic (no service layer)
- **Location:** `plugins/webkul/inventories/src/Models/Warehouse.php` (57 KB: `handleWarehouseCreation`, `createLocations`, `createRoutes`, `syncWarehouseConfiguration`), `manufacturing/src/Models/Order.php` (44 KB), `accounts/src/Models/Move.php` (38 KB)
- **Issue:** `accounts`, `inventories`, `manufacturing`, `purchases` plugins have **no `Services/` directory**, so multi-entity orchestration lives in the model.
- **Fix:** Introduce a `Services/` (or `Actions/`) layer per plugin and move orchestration out of the models.

### 2.3 — MEDIUM — Oversized Filament Resources
- **Location:** `plugins/webkul/employees/src/Filament/Resources/EmployeeResource.php` (1,821 lines / 139 KB), `QuotationResource.php` (120 KB), `accounts/.../InvoiceResource.php` (88 KB)
- **Issue:** Forms, tables, infolists, and actions inlined into single static methods — hard to test or reuse.
- **Fix:** Split form/table/infolist schemas into dedicated schema classes or Filament `Schema`/`Component` objects.

### 2.4 — MEDIUM — Fragile, hand-maintained migration ordering
- **Location:** `plugins/webkul/sales/src/SaleServiceProvider.php:32-57` (and every plugin provider)
- **Issue:** Each provider lists every migration filename manually in load order. A new migration silently won't run if omitted; cross-plugin alter-migrations make ordering fragile.
- **Fix:** Auto-discover migrations from the plugin's migrations directory, or add a CI check that every migration file is referenced.

### 2.5 — MEDIUM — Inconsistent inline validation in FormRequest-based controllers
- **Location:** `plugins/webkul/accounts/src/Http/Controllers/API/V1/InvoiceController.php:402` (`reverse` action uses inline `$request->validate([...])`)
- **Issue:** Most actions use Form Request classes, but some fall back to inline validation — splitting the convention within one controller.
- **Fix:** Move inline rules into dedicated Form Request classes for consistency.

### 2.6 — LOW — Casts convention not adopted
- **Location:** 80 models use legacy `protected $casts` (e.g. `accounts/src/Models/Move.php`); only 1 uses the `casts()` method
- **Issue:** Drift from the project's own prescribed convention.
- **Fix:** Migrate `$casts` arrays to the `casts()` method incrementally.

---

## 3. Testing & Coverage

### 3.1 — HIGH — ~70% of plugins have zero tests
- **Location:** `plugins/webkul/*/tests`
- **Issue:** Only 8 of 27 plugins have tests. Untested include `security`, `manufacturing`, `accounting`, `invoices`, `payments`, `recruitments`, `time-off`, `timesheets`, and 11 more. Estimated **~15–20%** of the app covered by real assertions.
- **Fix:** Prioritize feature tests for the highest-risk write paths (invoice payment/reversal, warehouse provisioning) before refactoring §2.1/§2.2.

### 3.2 — MEDIUM — No unit / domain tests
- **Location:** entire suite (75 of 79 files are `Feature/API/V1` CRUD)
- **Issue:** No coverage of Filament resources, services, model methods, observers, or state transitions except as API side-effects.
- **Fix:** Add unit tests for domain logic once it is extracted into services (§2.2).

### 3.3 — MEDIUM — `phpunit.xml` coverage source is misconfigured
- **Location:** `phpunit.xml` → `<source><include>` points to `app/` only
- **Issue:** The application lives in `plugins/`, so coverage instrumentation is meaningless.
- **Fix:** Add `plugins/` (and relevant `src` paths) to the coverage `<include>`.

### 3.4 — LOW — No convenience test scripts
- **Location:** `composer.json`, `package.json`
- **Issue:** No `composer test` / `npm test` targets; tests run via raw `vendor/bin/pest`.
- **Fix:** Add `"test": "pest"` to composer scripts and an `npm` script for Playwright.

---

## 4. Tooling & Docs

### 4.1 — MEDIUM — `AGENTS.md` is stale
- **Location:** `AGENTS.md`
- **Issue:** Documents Laravel 11 / Filament 4, but the project runs Laravel 13 / Filament 5. Contributors and AI agents following it will reference wrong-version docs and apply outdated guidance.
- **Fix:** Update the stack versions and conventions to match the current `composer.json`.

### 4.2 — LOW — No static analysis in CI
- **Location:** `.github/workflows/`
- **Issue:** No PHPStan/Larastan and no Pint check in CI; style/type drift goes uncaught.
- **Fix:** Add a Larastan + `pint --test` job to the CI matrix.

### 4.3 — LOW — No CI version matrix or coverage gate
- **Location:** `.github/workflows/pest_tests.yml`
- **Issue:** Single PHP version, single OS, no coverage reporting/gate.
- **Fix:** Add a PHP version matrix and a coverage threshold once §3.3 is fixed.

---

## Recommended order of action

1. **Quick security wins:** §1.1 login throttle + §1.2 Sanctum expiration (minutes; real risk reduction).
2. **Docs/config truth:** §4.1 `AGENTS.md` versions + §3.3 `phpunit.xml` source path.
3. **Guardrails before refactor:** §3.1 feature tests around accounting/invoices write paths.
4. **Structural debt:** §2.1 / §2.2 extract a service layer, then de-duplicate write paths.
5. **CI hardening:** §4.2 Larastan + Pint.
