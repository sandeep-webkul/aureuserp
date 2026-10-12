# SDD ledger — plan: docs/superpowers/plans/2026-10-09-pos-fully-vuejs.md
Executor: inline (native). Ruling: no commits/test-runs per project CLAUDE.md §23/§24; hand commands to user.
Task 1: complete (code only; not run per CLAUDE.md). Ruling: no company scoping on GET registers — mirrors existing Registers::getRegisters, avoids permission-behaviour change.
Task 2: pending (shell page).

Audit rulings (plan refined against real code):
- Till constructor REQUIRES full boot → picker is a standalone mini-app; opening-control becomes a Till screen. Store untouched.
- Store ALREADY has loadOrderHistory/fetchRefundableLines/prepareRefund/invoiceServerOrder/cancelServerOrder/loadClosingControl/recordCashMovement/cancelDraftOrders/closeRegister/salesDetailsUrl (till.js 1890-1980). No component consumes them yet → build UI only, reuse methods.
- OrdersModal = local parked drafts (not server history). Server Orders history screen is genuinely new UI.
- web pos/till group lacks opening-control route → add it + opening_control_endpoint in BootLoader.
- registers_url in BootLoader stays (Registers page kept this phase, strangler).
Task 2: shell page + routes + bootloader endpoint.

Implemented (working tree, NOT built/tested — user runs npm run build + pest):
- PHP: RegisterListController+RegisterResource, routes (registers, discard, configs/session, opening-control) in web+api, BootLoader opening_control_endpoint, Pos shell page + pos.blade.php, PosPanelProvider homeUrl + CustomEvent user-menu items, lang picker/history blocks. All php -l + pint clean.
- JS: picker/picker.js, PickerApp.vue, main.js orchestration (picker->till), till.js state+methods (opening/cash/closing/history) + menu CustomEvent listeners, OpeningScreen/CashMovementModal/ClosingModal/OrdersHistoryModal.vue, App.vue wiring. plain JS node --check clean; .vue NOT compiled (no build run per CLAUDE.md).
PENDING user: npm run build to compile .vue + smoke; php artisan test --testsuite=PointOfSaleFeature.
NOTE: refund-from-history intentionally not wired (local refund path unchanged). Orders history = online only (offline-local view deferred).

Final review: self-review (no subagent build/runtime available; .vue not compilable without npm run build which CLAUDE.md forbids the agent from running).
Rulings:
- Reused Filament user menu + browser CustomEvents instead of a new TopBar.vue (equivalent function, lower risk). Cost if wrong: cosmetic/location of triggers.
- Opening/closing use a direct cash amount input instead of a bill-denomination MoneyDetailsModal. Cost: cashier types a total rather than counting denominations.
- Orders history screen is online-only; offline users keep the existing local parked-orders modal. Cost: no server order search offline (matches Odoo bounded model).
- Refund-from-history not wired; local pending_refund refund path unchanged. Cost: refunds initiated from history list unavailable until built.
- Legacy Registers/Home/Terminal/RegisterControls NOT deleted (strangler; pending user verification). Cost: dead code remains until Phase 7 cleanup after smoke.
Deferred minors: PWA fold-in (point-of-sale.js still a plugin asset); bare /pos root may 404 (login lands on /pos/app via homeUrl).

Follow-up (SPA loader on Home/Orders nav): user was on legacy /pos/{session}/home|orders Filament pages (server nav = SPA loader spinner).
- Hid Home/Orders/Registers from Filament nav (shouldRegisterNavigation -> false).
- Added Vue in-app nav bar PosNav.vue (Home=screen switch instant, Orders=openHistory modal instant, Registers=link to /pos/app) mounted in App.vue below StatusBar.
- nav lang block added. php -l + pint clean. Needs npm run build.

Reverted the Home/Orders/Registers nav-hide + PosNav.vue + nav lang (user request). Back to pre-nav-fix state.

Full Vue topbar (user: replace Filament topbar entirely incl. profile icon, same UI):
- Panel ->topbar(false) (native Filament disable; no CSS hack).
- AppTopbar.vue: brand logo + Registers/Home/Orders nav + profile avatar dropdown (Cash In/Out, Install app, Toggle theme, Back office, Close register, Logout). Hosts #pos-status-slot so StatusBar (offline/pending) teleports into the Vue topbar (it previously lived in the Filament topbar render hook).
- App.vue root now fixed inset-0 (full screen) + <AppTopbar/>. PickerApp also fixed inset-0 + brand logo header.
- Pos page: brandLogo/userName/userAvatar (Filament::getUserAvatarUrl); pos.blade.php passes data-brand-logo/user-name/user-avatar. nav + menu.install/theme/logout lang added.
- Verified: all 7 .vue compile via @vue/compiler-sfc; php -l + pint clean.
- Old Filament userMenuItems/CustomEvent listeners left (harmless, don't render without topbar).

Orders back to full-page table (user: not a modal, exact old UI) + profile button fix:
- OrdersHistoryModal.vue deleted; new OrdersScreen.vue = full-page table (Date/Receipt/Order/Customer/Cashier/Tracking/[Table]/Total/Status) + toolbar (Back/search/status/pagination) + right detail panel (lines/taxes/total + Invoice/Print/Cancel). Rendered as a screen (v-else-if state.historyOpen) in App.vue, not a modal.
- OrderHistoryResource: added 'cashier'; OrderHistoryController eager-loads 'user'.
- history.columns + back/select-order/walk-in/taxes lang added.
- AppTopbar avatar: dropped external ui-avatars <img> (404'd on localhost -> broken button); now a clean primary initials circle.
- Verified: App/AppTopbar/OrdersScreen compile via @vue/compiler-sfc; php -l + pint clean.
- Refund numpad in orders detail NOT rebuilt (old page had it); invoice/print/cancel present.

Fixes round: refund + avatar + menu hardening.
- Refund rebuilt in OrdersScreen: selectHistoryOrder loads refundable lines; per-line qty inputs capped at refundable; Refund btn -> prepareRefund -> openPendingRefund(pending) (refactored to accept a pending arg) -> active refund order in cart, screen=products. Store: refundLines/refundError state + selectHistoryOrder/setRefundQty/refundTotalQty/canRefundSelected/submitRefund. OrderHistoryResource cashier + controller user eager-load.
- Avatar: initials circle (external ui-avatars img removed). Header relative z-30 + avatar @click.stop so dropdown links reliably clickable above content.
- All compile (@vue/compiler-sfc) + php lint/pint clean.
OPEN: cannot run browser here; remaining "many UI issues" need a screenshot + console errors to fix precisely. Offline: sale loop untouched (works offline once booted); orders/refund/closing are online by design (were server pages before).

URL change to /pos/{session} + refund numpad:
- New Register page (slug {session}, routes() override with ->whereNumber('session')) renders terminal.blade.php (server data-boot + topbar brand/user/backoffice/logout attrs). Verified route match: pos/5->Register, pos/login/app/registers unaffected.
- Picker (Pos /pos/app) now NAVIGATES to /pos/{session} on open/resume/auto-resume (picker.js boot()=location.href=terminalBase; no in-place boot). data-terminal-base added; main.js drops onBooted.
- In-session nav (Home/Orders/Closing/Cash) stays no-reload Vue; only session open/close navigates (like Odoo dashboard->/pos/ui). Refresh on /pos/{session} reloads that session via server boot.
- Refund: now numpad-based (click refundable line -> highlight -> numpad qty capped -> "To refund" -> Refund btn -> pending -> cart). Matches old refund-numpad UX.
- All .vue compile; php -l/pint clean; route generation of terminalBase verified.

Offline Orders fix (user: offline order click not updating, wrong count, failed-request spam):
- refreshHistory(): offline (or on fetch failure) -> localOrderHistory() from state.orders (no server fetch -> kills the orders?status=active spam). Online unchanged.
- localOrderHistory()/historyRowFromLocal(): map local orders (lines>0) to the history table shape via orderTotals/lineTotals/master lookups; filter by search+status.
- selectHistoryOrder(): offline skips fetchRefundableLines (click selects + shows details, no failed request; refund is online-only like before).
- AppTopbar badge: orderCount now counts non-empty orders (was counting the empty active order -> wrong count).
- Verified: till.js node-check + OrdersScreen/AppTopbar SFC compile clean.

Restore original modals (user: closing/cash/money-details/opening/notes bilkul pehle jaise) + refund numpad styling:
- Re-added <livewire:point-of-sale-register-controls :session> to terminal.blade.php. Brings back ORIGINAL modals (opening-control auto-opens via needsOpeningControl, closing with copy-expected/moves/counted/difference, cash-movement, money-details denomination +/-, draft-orders). Vue mount keeps wire:ignore -> coexistence ok.
- AppTopbar Cash/Close -> window.Livewire.dispatch('pos-open-cash-movement'|'pos-close-register') (RegisterControls #[On] listeners). RegisterControls close redirect -> Pos (/pos/app) instead of Registers.
- Removed Vue OpeningScreen/CashMovementModal/ClosingModal (deleted files + App.vue imports/mounts); till.start() drops opening-screen branch; startMenuListeners only 'pos-open-orders'.
- Refund numpad in OrdersScreen now matches till Numpad style (key/keyBase/warning/info/danger classes, 4-col grid + Qty/%/Price mode column, 0/./backspace).
- All compile: till.js node-check, App/AppTopbar/OrdersScreen SFC, php -l + pint clean.
Profile dropdown: nowrap + icons + w-64 + ring (prev turn) — awaiting user verify post-build.
