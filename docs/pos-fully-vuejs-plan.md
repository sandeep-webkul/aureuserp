# Plan: Convert Aureus POS to a fully 100% Vue.js, offline-first terminal

## Context

The POS plugin (`plugins/webkul/point-of-sale`) is today a **hybrid**: the sale loop runs in a mature Vue app (`resources/js/till`), but the register picker, session opening, order history, and all register-control flows (closing, cash in/out, money details, draft handling) are still **Filament pages + a Livewire component**, each a separate `pos`-panel navigation entry. This forces full server page reloads between POS screens, keeps two UI paradigms in one panel, and leaves several flows non-functional offline.

The goal is that **after the Filament login, one Vue application owns every POS screen** — register picker, opening control, till, orders, closing, cash movements, and all modals — mounted once at a single route (`/pos`), navigating client-side with no page reloads and degrading gracefully offline. **Filament login and all admin-panel back-office functionality stay unchanged.** **No business logic, validation, accounting, inventory, or permission behaviour may be duplicated or changed**: the Vue app drives the existing services exclusively through thin HTTP endpoints that wrap the existing `PointOfSale` facade. Old surfaces stay live until their Vue replacement is built and tested (strangler migration).

### Confirmed decisions
- **Login stays Filament; everything after it is the single Vue app.** The `pos` panel login is unchanged. After login the cashier lands on **one** Filament shell page at `/pos` that mounts the Vue app once. The register picker and opening control move **into** Vue (they were previously Filament). They remain online-only operations because they require the server, but they render as Vue screens, not server pages.
- **Vue owns everything after login** — picker, opening control, till, orders, closing control, cash in/out, and every sale/closing modal — as client-side screens, no page reload between them.
- **Plain `/pos` URL, state-driven.** The route is always `/pos`; there is no session id in the URL. The current register/session lives in `state` plus `localStorage`. A refresh re-resolves the session from the register-list API (auto-resume), matching today's `ownSession`/`liveSessionFor` behaviour.
- **Boot moves to an API fetch.** Because `/pos` has no session at mount time, the boot payload can no longer be injected via `data-boot`. Sequence: load register list → open/resume session → `GET sessions/{session}/boot` → hydrate store → show opening or till. The `data-boot` fast-path is kept only as a fallback.
- **Orders offline = local + recent** — offline shows IndexedDB orders plus a bounded recent set preloaded at boot; full server search when online (matches Odoo's bounded-load model).
- **State-machine navigation, no router** — `state.screen` reactive switch, like the current till and Odoo (`store/router.js` disables the web router). Values extend to `picker | opening | floor | products | payment | receipt | orders | closing | split`.

## Current-state inventory (what exists / what must move)

### Already in Vue (keep, extend) — `resources/js/till`
- `main.js` (mount/lifecycle, reads `#pos-till` + `data-boot`), `App.vue` (screen switch on `state.screen` + ~23 modals), `store/till.js` (~4,500 lines: orders, lines, pricing, validation, keyboard, floor/restaurant, split, tips, ship-later, global discount, lots), `store/records.js` (indexed in-memory relational store), `store/indexed-db.js` (per-config+token DB, debounced writes, reconnect/visibility resilience, eviction), `store/sync-queue.js` (uuid dedup, retry, batch flush, cross-tab via `storage`), `tax/{computer,float,formula}.js` + `parity.mjs` (client tax engine with a PHP parity harness).
- Fully implemented sale features: cart, numpad, payment, receipt/print, refunds (`pending_refund` from boot), tips, split bill, takeaway, ship-later, global discount, lots, floor plan editor.
- Existing screen values: `floor | split | products | payment | receipt`. Existing navigation helpers: `goToProducts`, `goToPayment`, `goToFloor`, `resumeScreenFor`.

### Stays Filament (do NOT migrate)
| Surface | Role |
|---|---|
| **Login** (`pos` panel) | unchanged authentication gate |
| **Entire admin panel** | back-office configuration, resources, widgets — unchanged |

### Must move into the Vue app
| Surface | Files | Server work it performs |
|---|---|---|
| **Registers picker** | `src/Filament/Pos/Pages/Registers.php` (166), `resources/views/filament/pos/pages/registers.blade.php` (66) | list active configs, live-session lookup, open/resume, discard session |
| **Session open + opening control** | `src/Livewire/RegisterControls.php` (opening portion: `confirmOpening`, money-details), `partials/modals/opening-control.blade.php`, `money-details.blade.php` | create session, collect opening float, `confirmSessionOpeningControl` |
| **Orders history** | `src/Filament/Pos/Pages/Orders.php` (350), `pages/orders.blade.php` (352) | paginated server search/filter, select order, invoice, cancel, refundable-lines + pending-refund, receipt |
| **Closing / cash controls** | `RegisterControls.php` (closing + cash portion), `partials/modals/{closing,cash-movement,money-details,draft-orders}.blade.php` | `ClosingControlReport::build`, `closeSessionWithAccounting`, `cashIn`/`cashOut`, cancel-draft-orders, sales-details PDF |
| **Terminal host page** | `src/Filament/Pos/Pages/{Terminal,Home}.php` + `pages/till.blade.php` | currently hosts Vue via per-session `data-boot`; becomes the single session-less `/pos` shell |

### Backend already present (reuse — do NOT duplicate)
- **Facade/manager**: `src/Facades/PointOfSale.php`, `src/PointOfSaleManager.php` expose every needed operation: `openSession`, `liveSessionFor`, `isSessionDiscardable`, `discardSession`, `loginSession`, `confirmSessionOpeningControl`, `requestSessionClosing`, `closeSessionWithAccounting`, `cashIn`/`cashOut`, `syncOrder(s)`, `saveDraftOrder`, `cancelOrder`, plus refund/invoice workflows through their controllers.
- **Services** (unchanged): `OrderProcessor`, `OrderCalculator`, `OrderWorkflow`, `SessionWorkflow`, `SessionCloser`, `SessionPreflight`, `RefundProcessor`, `PickingGenerator`, `SessionPickingGenerator`, `PosInvoicer`, `PosInvoicePayer`, `ClosingEntryBuilder`, `CogsEntryBuilder`, `SessionReconciler`, `OfflinePartnerResolver`, `PriceResolver`, `ClosingControlReport`, `SessionSalesDetailsReport`, `BootLoader`, `CatalogLoader`.
- **Controllers/routes already live** (`src/Http/Controllers/API/V1/`): `SessionController` (current/open/opening-control/closing-control/close/cash-movements), `BootController`, `ClosingControlController`, `OrderHistoryController`, `OrderSyncController`, `OrderCancelController`, `OrderRefundController` (refundable + store), `OrderInvoiceController`, `DraftController`, `DraftCancelController`, `SalesDetailsController`, `ReceiptController`, `FloorPlanController`, `TerminalProductController`, `CatalogController`. Two route groups: web `pos/till` (session/web auth, named `point-of-sale.till.*`) and API `admin/api/v1/point-of-sale` (sanctum).

## Odoo 18 reference (patterns to adapt — behaviour only, no code copied)
- The register dashboard/picker is a **backend** view; `open_ui` creates the session and redirects to `/pos/ui`, a standalone client app. We go further by rendering the picker inside the same client app — but it stays online-only, exactly as Odoo's dashboard requires the server.
- `addons/point_of_sale/static/src/app/models/data_service.js:238` `load_data` → one relational boot envelope per model; our `BootLoader::load` is the equivalent and already returns the right shape. Odoo fetches this via RPC after the session is known — the same boot-via-API model we adopt.
- `data_service.js:101` DB name `config-id_{id}_{token}`, `:49` 200 ms debounced reactive persistence, `:452-575` unsync queue (uuid keyed, `sync_from_ui` never queued, FIFO flush on `online`) — all already mirrored in our `indexed-db.js`/`sync-queue.js`.
- `models/pos_order.js:103` `canBeRemovedFromIndexedDB`, `:113` `isUnsyncedPaid` (string id pre-sync, numeric post-sync) — our eviction + uuid identity match.
- `store/router.js:1` router disabled → confirms the state-machine navigation decision.
- `screens/payment_screen/payment_screen.js` payments collected client-side, validated on sync → confirms offline-capable non-terminal payments; card terminals still need network.

## Target architecture

```
Filament login (unchanged, pos panel)
      │
      ▼
ONE Filament shell page  →  route /pos  (session-less)
      │  renders a minimal blade: <div id="pos-till" wire:ignore> + @vite(main.js)
      ▼
Vue app (resources/js/till)   —   state.screen ∈ { picker, opening, floor, products, payment, receipt, orders, closing, split }
      ├─ Picker    → GET registers · open/resume (POST configs/{config}/session) · discard
      ├─ Opening   → collect float · POST sessions/{session}/opening-control
      ├─ Till      (existing sale loop, unchanged)
      ├─ Orders    → local store (offline) / GET order history (online) · invoice · cancel · refund
      └─ Closing   → GET closing-control report · POST close · cancel-draft-orders · cash in/out · sales-details PDF
          │
          ▼  all calls hit thin controllers → PointOfSale facade → existing services (unchanged)
```

- **Mount once at `/pos`.** The Filament shell page no longer binds a session; it only mounts the Vue app. Session resolution happens inside Vue.
- **Boot via API.** On mount the app fetches the register list; after open/resume it fetches `GET sessions/{session}/boot` and hydrates the store. `data-boot` is kept as an optional fast-path fallback.
- **Resume on refresh.** Last session id persists in `localStorage`; on reload the register list + `liveSessionFor` re-resolve and auto-resume (single-session case skips the picker, mirroring `ownSession`).
- **Top-bar actions** (Cash In/Out, Close Register, Install App, Back office, Logout) become Vue-owned controls in `#pos-till`.
- **No Livewire inside the terminal**: the closing/cash/opening portions of `RegisterControls` and its modal partials are reimplemented as Vue modals. The `wire:ignore` dual-paradigm workaround is removed.

### Picker / opening presentation (Odoo-style, Tailwind only, dark-mode aware — conventions §17/§18)
- **Picker**: full-screen layout; top-left live clock + date, top-right brand logo, center register grid (or single-register card) with Open/Resume/Discard, bottom bar with Back office + Logout.
- **Opening control**: card with opening-cash input and money-details (bill denominations) sub-modal, confirm → `opening-control` endpoint → transition to till.
- Offline: picker/opening require connectivity; show a clear "connect to open a register" state when offline.

## Required APIs (reuse existing; add the small gaps)

All new endpoints are **thin controllers** in `src/Http/Controllers/API/V1/` that validate via a `FormRequest`, call the `PointOfSale` facade, and return JSON. They add **zero** business logic. Authorization continues through `PosAccess`/gates/policies exactly as the Livewire/Filament pages do today.

### Already available — wire Vue to these
- `GET  configs/{config}/session` (current live session) · `POST configs/{config}/session` (open) — picker open/resume.
- `POST sessions/{session}/opening-control` — opening control.
- `GET  sessions/{session}/boot` — boot payload (now fetched via API).
- `POST sessions/{session}/closing-control` · `POST sessions/{session}/close` · `POST sessions/{session}/cash-movements`.
- `GET  sessions/{session}/closing-control-report` · `GET sessions/{session}/orders` · `GET sessions/{session}/sales-details` · `POST sessions/{session}/cancel-draft-orders`.
- `POST orders/sync` · `POST orders/cancel` · drafts · refund (refundable + store) · invoice · receipt · floor plan + terminal-product endpoints.

### To add (the gap found in the audit)
| New endpoint | Wraps (existing) | Replaces |
|---|---|---|
| `GET  registers` (active configs for the user, each with `live_session` + `is_discardable`) | `Registers::getRegisters` + `PointOfSale::liveSessionFor` + `isSessionDiscardable` | the `Registers` Filament picker |
| `POST registers/{config}/discard` (or reuse a session-discard endpoint) | `PointOfSale::discardSession` | `Registers::discardSessionAction` |

Register both in the web `pos/till` group (session/web auth) and, if needed for the sanctum app, in `admin/api/v1/point-of-sale`, mirroring the existing dual registration, with matching `FormRequest`s under `src/Http/Requests/`. Every new endpoint asserts `PosAccess` company scoping.

## Vue component structure (additions to `resources/js/till`)

- **`store/till.js`**: extend `state.screen` with `picker | opening | orders | closing`. Add a **session-resolution layer**: `loadRegisters`, `openRegister(config)`, `resumeRegister(session)`, `discardRegister(session)`, `bootSession(session)` (fetch `GET boot`, then `loadMaster`). Add `confirmOpening`, `loadClosingControl`, `closeRegister`, `recordCashMovement`, `cancelDraftOrders`, `loadOrderHistory`, `invoiceOrder`, `cancelOrder`, `startRefund`. Keep all pricing/tax/validation client logic untouched. Persist the active session id in `localStorage` for refresh-resume.
- **`main.js`**: mount on the session-less `/pos` shell. If `data-boot` is present use it (fast-path); otherwise start in `picker` screen and resolve the session via API. Fold the legacy `point-of-sale.js` PWA-install + receipt-print + service-worker registration into the Vue bundle.
- **New screens** (`components/`): `PickerScreen.vue` (register grid, open/resume/discard, live clock, bottom bar), `OpeningControlModal.vue` + `MoneyDetailsModal.vue` (opening float), `OrdersScreen.vue` (list + detail + refund panel, porting `orders.blade.php`'s Alpine numpad to the existing Vue numpad), `ClosingControlModal.vue`, `CashMovementModal.vue`, `DraftOrdersModal.vue`, `TopBar.vue` (cash/close/install/back-office/logout + status).
- **`App.vue`**: extend the screen switch to render Picker/Opening/Orders/Closing alongside the existing `floor|split|products|payment|receipt`; mount the new modals.
- **Offline matrix**: *offline-capable* — browse boot catalogue, build/modify orders, cash & non-terminal payments, validate, queue, receipt, view local+recent orders, refunds of locally-known orders, cash in/out recorded locally then synced. *Online-required* — list registers, open/close session, opening/closing control report, invoicing, card-terminal auth, full server order search, sales-details PDF.

## Build / bundling
- Consolidate the client into the Vue/Vite build already used for `till/main.js` (root `vite.config.js` with `@vitejs/plugin-vue`). Fold `point-of-sale.js` utilities in; retire the plugin `package.json` `esbuild` step for `point-of-sale.js` once nothing references the old `dist/point-of-sale.js`. Keep the plugin Tailwind build (`resources/css/index.css` → `dist/point-of-sale.css`) per conventions §9. Dark mode: ensure new screens use theme tokens / `dark:` utilities (conventions §18; clears the `darkMode(false)` debt noted in `offline-architecture.md` §10).

## Phased roadmap (each phase independently testable; old surface stays live until its replacement passes)

1. **API gap-fill** — add `GET registers` (+ discard) controller/request/routes; cover with `tests/Feature/API/V1` asserting delegation to the facade and `PosAccess` enforcement. No UI change yet.
2. **Session-less shell + boot-via-API + Picker** — add the `/pos` Filament shell page; `main.js` resolves the session via API; `PickerScreen.vue` lists/open/resume/discard. Old Filament `Registers` stays live in parallel. Mid-session refresh auto-resumes.
3. **Opening control → Vue** — `OpeningControlModal` + `MoneyDetailsModal` calling `opening-control`; retire the opening portion of `RegisterControls`.
4. **Orders → Vue screen** — `OrdersScreen.vue` (online search + offline local/recent), invoice/cancel/refund wired; retire `Orders.php` and its nav entry.
5. **Closing / cash → Vue** — closing-control, cash-movement, money-details, draft-orders modals as Vue; retire the closing+cash portion of `RegisterControls` and its partials.
6. **Top bar + PWA/print consolidation** — finalize the Vue `TopBar`, fold `point-of-sale.js` into the bundle, remove the Filament `userMenuItems` Livewire dispatches.
7. **Cleanup & hardening** — delete dead Blade/Livewire/Filament pages for migrated flows, dark-mode pass on every new screen, extend offline/sync hardening (reconnect, duplicate prevention, conflict `already-settled`, recovery after refresh) across picker/orders/closing.

## Affected files (representative)
- Add: `src/Http/Controllers/API/V1/RegisterListController.php` (+ discard) + matching `src/Http/Requests/*`; route entries in `routes/web.php` (& `routes/api.php` if needed). (Order history/refund/invoice/closing/draft/sales-details controllers already exist.)
- Add Filament shell: a session-less `/pos` page (e.g. `src/Filament/Pos/Pages/Terminal.php` repurposed, or a new `Pos.php`) rendering a minimal blade (`#pos-till` + `@vite`), no per-session binding; register it as the panel home in `app/Providers/Filament/PosPanelProvider.php` / `src/PointOfSalePlugin.php`.
- Change: `resources/views/filament/pos/pages/till.blade.php` (session-less mount, drop `<livewire:...register-controls>`), `resources/js/till/{main.js, App.vue, store/till.js}`, root `vite.config.js`.
- Add: `resources/js/till/components/{PickerScreen, OpeningControlModal, MoneyDetailsModal, OrdersScreen, ClosingControlModal, CashMovementModal, DraftOrdersModal, TopBar}.vue`.
- Remove (last, after parity): `src/Filament/Pos/Pages/{Registers,Home,Orders}.php`, `src/Livewire/RegisterControls.php`, `resources/views/filament/pos/pages/{registers,orders}.blade.php`, the modal partials, `resources/js/point-of-sale.js` (after fold-in).

## Dependencies & risks
- **Risk: boot-via-API first-paint** — slower than `data-boot`; mitigate with a tiny loading state and keep the `data-boot` fast-path as a fallback.
- **Risk: permission/authorization drift** — the new `registers` endpoint and every wired endpoint must replicate `PosAccess`/gate checks the Filament/Livewire pages enforced; assert in API tests.
- **Risk: losing an in-progress sale on screen switch** — state-machine (no router) avoids reloads; the shell no longer embeds Livewire, so no morphdom conflict (retain `wire:ignore` on the mount node while any Livewire chrome remains).
- **Risk: closing-control numbers** — the closing report must come from `ClosingControlReport::build` server-side; the Vue modal only renders it, never recomputes expected amounts.
- **Risk: picker/opening offline** — online-only by nature; the app must detect offline and block open with a clear message rather than failing silently.
- **Risk: refresh-resume ambiguity** — mirror `ownSession` (single open session auto-resumes; 0 or 2+ → show picker).
- **Risk: cross-tab queue** — preserve the existing `sync-queue` `storage`-event merge when multiple terminal tabs are open.
- **Dependency**: the never-run `TaxParityTest` must pass before trusting client totals (offline-architecture.md flags this as the gate).

## Verification (hand commands to the user — never run here)
Per conventions §10/§24, tests/migrations are not run by the agent; hand over:
```bash
composer dump-autoload
php artisan point-of-sale:install --no-interaction
php artisan test --testsuite=PointOfSaleFeature   # incl. TaxParityTest (the client-total gate)
npm run build
```
- **Regression tests to add/extend**: `tests/Feature/API/V1/` for every new/wired endpoint (delegation + permission + validation), including `GET registers`; extend `tests/Feature/Workflows/{Session,Order,OrderRefund}Test.php` for open/opening/close/cash/invoice/refund driven through the controllers; keep admin `Filament` resource tests unchanged.
- **Manual smoke (both light & dark themes)**: login → `/pos` Vue picker → open register + opening control (Vue) → sell two units offline (DevTools offline), queue, reconnect, confirm `WH/POS/…` operation done & stock dropped → refund one unit (`POSRET`) → invoice an order → Orders screen offline shows local/recent, online shows full search → cash in/out (Vue) → close register (Vue) → open the posted balanced journal entry in admin. Confirm **zero** network requests on `addProduct`/numpad, that a mid-sale refresh restores the cart from IndexedDB and auto-resumes the session, and that **no full page reload** occurs between any two POS screens after login.
- **Parity gate**: `TaxParityTest` green before client totals are trusted.

## Guardrails
- No business/accounting/inventory/permission logic is reimplemented in JS or in the new controllers — they delegate to the existing facade/services.
- Only login and the admin panel stay server-rendered; every POS screen after login is Vue.
- Old surfaces removed only after their Vue replacement is built **and** tested (phases end with a retire step).
- No comments in shipped source; Pint + conventions formatting; no git staging/commits; no running tests/migrations/seeders (hand commands to the user).
