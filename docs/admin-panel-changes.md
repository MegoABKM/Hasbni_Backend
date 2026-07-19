# Admin Panel Changes

Change date: 2026-07-19

## Scope

This pass focused on repairing production-readiness issues found during the initial audit while preserving existing Laravel, Filament, API, and asset architecture. The project was not rebuilt from scratch and no supported feature was intentionally removed.

## Modified Files

- `.env.example`
- `composer.json`
- `composer.lock`
- `routes/web.php`
- `database/seeders/SaaSDatabaseSeeder.php`
- `app/Http/Controllers/AuthController.php`
- `app/Http/Controllers/CashController.php`
- `app/Http/Controllers/InventoryController.php`
- `app/Http/Controllers/PartnershipController.php`
- `app/Http/Controllers/ProductController.php`
- `app/Http/Controllers/ProfileController.php`
- `app/Http/Controllers/SaleController.php`
- `app/Http/Controllers/SyncController.php`
- `app/Http/Controllers/WebhookController.php`
- `app/Http/Middleware/EnsureManagerAccess.php`
- `app/Models/PartnerGood.php`
- `app/Providers/Filament/AdminPanelProvider.php`
- `app/Filament/Pages/EmailCampaigns.php`
- `app/Filament/Pages/SystemBackups.php`
- `app/Filament/Pages/SystemLogs.php`
- `app/Filament/Resources/FaqResource.php`
- `app/Filament/Resources/InstructionResource.php`
- `app/Filament/Resources/PaymentResource.php`
- `app/Filament/Resources/SubscriptionResource.php`
- `app/Filament/Resources/SupportTicketResource.php`
- `app/Filament/Resources/AnnouncementResource.php`
- `app/Filament/Resources/UserResource/RelationManagers/PaymentsRelationManager.php`
- `app/Http/Controllers/SaaSController.php`
- `database/migrations/2026_07_19_000001_ensure_announcements_table_exists.php`
- `resources/views/download.blade.php`
- `resources/views/filament/pages/email-campaigns.blade.php`
- `resources/views/filament/pages/global-settings.blade.php`
- `resources/views/filament/pages/push-notifications.blade.php`
- `resources/views/filament/pages/system-backups.blade.php`
- `resources/views/filament/pages/system-logs.blade.php`
- Deleted from the working tree: `hasbnikey`, `hasbnikey.pub`, `public/download/hasbni.pfx`, `app/bhasbni4.rar`

## Major Changes

### Build and Runtime Metadata

- Aligned `composer.json` with the installed dependency set by requiring PHP `^8.3`.
- Added explicit `ext-intl` requirement because Filament requires it and the app cannot be production-ready without it.
- Updated Composer lock metadata with `composer update --lock --ignore-platform-reqs --no-scripts`.

### Routing and Secret Exposure

- Protected `/clear-cache` behind authenticated `super_admin` access instead of leaving it public.
- Protected `/get-cert` behind authenticated `super_admin` access and removed the certificate from the working tree.
- Removed public download-page exposure of the `.pfx` certificate.
- Deleted committed private/signing/binary artifacts from the working tree. These still require git-history purging and key/certificate rotation before production use.

### Authentication and Authorization

- Added request validation to login.
- Blocked banned accounts before successful login response and token use.
- Removed OTP values from mail-failure logs.
- Added expiry checks to registration verification and reset-password flows.
- Changed manager middleware from fail-open to fail-closed when no manager password is configured.

### Tenant Isolation and API Safety

- Scoped sale product mutations to the authenticated user's products.
- Scoped sale updates, exchanges, and return inventory adjustments to authenticated ownership.
- Scoped partnership goods, record items, update, and delete operations through the authenticated user's partner relationships.
- Added the missing `PartnerGood::partner()` relationship.
- Whitelisted product sort columns before passing them to `orderBy()`.
- Added validation for malformed `since` sync parameters.
- Added nested payload validation to cash and inventory sync endpoints.
- Added profile and manager-password validation before state changes.

### Payments and Webhooks

- Added Stripe webhook signature verification with `STRIPE_WEBHOOK_SECRET`.
- Fail closed for missing Stripe webhook secret in production.
- Added missing `PromoCode` import in the webhook controller.
- Replaced missing Stripe SDK refund calls with direct authenticated Stripe API requests via Laravel HTTP client, preserving existing refund behavior without introducing a new dependency.

### Admin Operations

- Hardened backup download/delete filename handling against path traversal.
- Improved system log reading for small files.
- Added redaction for secrets, bearer tokens, passwords, API keys, and six-digit codes in the admin log viewer.
- Moved default seeded admin password to `INITIAL_ADMIN_PASSWORD` outside local development.

### Filament UX and UI Polish

- Reworked custom Filament page views for backups, logs, global settings, push notifications, and email campaigns with clearer responsive layouts.
- Added explicit confirmation to custom destructive backup, push, and email actions.
- Cleaned visible mojibake/corrupted labels in support tickets, FAQ, instructions, and subscriptions resources.
- Repaired and polished the announcement admin resource with Filament v5-compatible form/table APIs, safer delete confirmation, useful columns, and default sorting.
- Added a navigation icon for email campaigns.
- Standardized dashboard registration around executive KPI widgets and backend-required placeholder widgets already present in the project.

### Announcements

- Added a non-destructive repair migration to ensure the `announcements` table and required columns exist.
- Updated `/api/announcements/active` to return a safe empty response when announcements are unavailable instead of exposing a raw database failure.
- Preserved the existing API contract of `success` plus `data`.

## Bugs Fixed

- Public unauthenticated cache clear route.
- Public certificate download route.
- Missing `PromoCode` class import in webhook handling.
- Stripe refund class-not-found risk.
- Cross-tenant product mutation risk in sales.
- Cross-tenant partnership good and record mutation risk.
- Manager-only access allowed when manager password was unset.
- Unvalidated login payloads.
- OTP leakage in logs.
- Expired registration/reset OTPs accepted by some flows.
- Unsafe arbitrary product sort column.
- Backup path traversal risk.
- Raw sensitive log display in admin.
- Malformed sync date causing frontend-visible 500 errors.
- Malformed nested API payloads causing notices or crashes.
- Announcement admin/API failures caused by old Filament API mismatches or missing announcement schema.

## Security Improvements

- Removed sensitive files from the working tree.
- Added production webhook signature verification.
- Reduced raw exception and sensitive token exposure in logs/responses.
- Strengthened tenant scoping on sensitive business mutations.
- Changed privileged manager middleware to fail closed.
- Moved default production admin credentials to environment configuration.

## UI Improvements

- Replaced several custom gray/dark hardcoded admin views with theme-aware Filament sections.
- Improved responsive spacing and overflow behavior on custom settings/log/backup pages.
- Cleaned unreadable labels on multiple admin resources.
- Preserved supported functionality while making destructive actions more explicit.

## Tests Added

No automated tests were added in this pass because the local Laravel runtime cannot boot under PHP 8.2.12 while the installed dependency set requires PHP 8.3+. The required test targets are documented in `docs/testing-report.md` and `docs/remaining-risks.md`.

## Commands Executed

- `composer update --lock --ignore-platform-reqs --no-scripts`: lock metadata updated; command timed out after writing lock metadata, with no package installation needed.
- `php -l` sweep over `app`, `routes`, `config`, and `database`: passed for 188 files.
- `composer validate --no-check-publish`: passed.
- `composer check-platform-reqs`: failed because local PHP is `8.2.12`, `openspout/openspout` requires PHP `>= 8.3`, and `ext-intl` is missing.
- `php artisan route:list`: failed before Laravel boot due Composer platform check requiring PHP `>= 8.3`.
- `php artisan test`: failed before Laravel boot due Composer platform check requiring PHP `>= 8.3`.
- `npm run build`: passed. Vite built `public/build/manifest.json`, `assets/app-B9jm1EEq.css`, and `assets/app-Dd1ranYp.js`.
- `npm.cmd run lint`: failed because no `lint` script is configured.
- `npm.cmd test`: failed because no `test` script is configured.

## Remaining Limitations

- Laravel runtime, route listing, and PHP tests require a PHP 8.3+ CLI with `ext-intl`.
- Deleted secret files must be committed, git history must be purged, and any exposed keys/certificates must be rotated before production.
- Full browser console verification could not be completed because Laravel cannot boot in the current PHP runtime.
- Several backend-dependent dashboard KPIs are intentionally documented as requiring backend support instead of displaying fake production metrics.
