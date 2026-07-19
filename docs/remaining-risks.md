# Remaining Risks

Report date: 2026-07-19

## Critical

### PHP Runtime Is Not Production-Aligned Locally

The current machine runs PHP `8.2.12`, but installed dependencies require PHP `>= 8.3` and `ext-intl`. Laravel cannot boot until the runtime is upgraded. This blocks route listing, feature tests, and browser smoke testing.

Required action: install PHP 8.3+ with `ext-intl`, rerun Composer install/checks, then rerun route, test, and browser verification.

### Exposed Secrets Require Rotation and History Purge

The private key, public key, `.pfx` certificate, and archive were deleted from the working tree, but they are still tracked until the deletion is committed and may still exist in git history.

Required action: commit the deletions, rotate any exposed signing keys/certificates, purge sensitive files from git history, and invalidate any distributed trust material as appropriate.

## High

### Backend Tenant Isolation Needs Feature Tests

Sales and partnership mutations were scoped to authenticated ownership, but cross-tenant feature tests could not be run because Laravel cannot boot locally.

Required action: add and run multi-tenant tests under PHP 8.3+.

### Payment Provider Verification Is Partially Backend-Dependent

Stripe webhook signature verification was added. MyFatoorah callback trust, replay protection, and deterministic plan mapping remain backend/payment-flow concerns that need provider-specific verification and staging tests.

Required action: verify MyFatoorah callback authenticity server-side, store pending payment intents, and test replay/forged callbacks.

### Default Admin Bootstrapping Needs Deployment Policy

The seeder now requires `INITIAL_ADMIN_PASSWORD` outside local development, but production account creation and rotation policy still need stakeholder approval.

Required action: define production bootstrap process, rotate default admin credentials, and audit existing admin accounts.

## Medium

### Dashboard KPIs Still Need Backend Support for Some Executive Metrics

The dashboard avoids fake metrics and uses backend-required placeholder widgets where data does not exist. Some executive KPIs such as CAC, ROAS, CSAT, inventory accuracy, and similar domain metrics still require backend data contracts before they can be displayed.

Required action: define backend events/tables/API contracts for unsupported KPIs before adding values.

### Browser Console and Responsive QA Are Blocked

Vite builds successfully, but the Laravel app could not be run locally due the PHP platform mismatch. Console errors, route smoke tests, and responsive browser checks remain pending.

Required action: after PHP upgrade, run the app and verify admin routes at mobile, tablet, desktop, and wide desktop widths.

### Custom Filament Pages Need End-to-End UI Confirmation

Several custom views were polished and destructive confirmations were added, but Livewire/Filament behavior must be checked in a bootable browser session.

Required action: test backups, logs, settings, push notifications, email campaigns, and destructive confirmations manually and with feature tests where practical.

### Log Viewer Redaction Is Defensive, Not Complete DLP

The log viewer now redacts common secrets and codes, but redaction cannot guarantee all PII or proprietary data is hidden.

Required action: limit log viewer access to trusted admins, add audit logging for log access/clear actions, and avoid logging sensitive data at source.

## Low

### Frontend Lint and Test Scripts Are Missing

`package.json` only defines `build` and `dev`. `npm.cmd run lint` and `npm.cmd test` fail because scripts are not configured.

Required action: add ESLint or the chosen frontend quality tool only if the project has enough custom JavaScript to justify it.

### Existing Placeholder Tests Do Not Cover Critical Flows

Laravel tests are currently placeholders and could not run under the local PHP version.

Required action: replace placeholder tests with targeted tests listed in `docs/testing-report.md`.

### Some Legacy Mojibake May Remain

The most visible custom pages and several resources were cleaned, but a full localization pass was not completed across every label/comment in the repository.

Required action: centralize labels in translation files and run a final Arabic/English copy review.
