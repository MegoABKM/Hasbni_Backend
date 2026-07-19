# Testing Report

Report date: 2026-07-19

## Environment

- OS shell: Windows PowerShell
- Project: Laravel 12, Filament 5, Vite/Tailwind
- Current CLI PHP: `8.2.12`
- Required PHP after dependency alignment: `^8.3`
- Missing PHP extension: `ext-intl`

## Verification Results

| Command | Result | Notes |
| --- | --- | --- |
| `php -l` sweep over `app`, `routes`, `config`, `database` | Passed | Initial pass covered 188 files; latest pass after the announcement fix covered 189 files. |
| `composer validate --no-check-publish` | Passed | Composer metadata is valid. |
| `composer check-platform-reqs` | Failed | `openspout/openspout` requires PHP `>= 8.3`; local PHP is `8.2.12`; `ext-intl` is missing. |
| `php artisan route:list` | Failed | Composer platform check aborts before Laravel boots. |
| `php artisan test` | Failed | Composer platform check aborts before Laravel boots. |
| `npm run build` | Passed | Vite production build completed successfully. |
| `npm.cmd run lint` | Failed | No `lint` script exists in `package.json`. |
| `npm.cmd test` | Failed | No `test` script exists in `package.json`. |

## Build Output Summary

`npm run build` completed successfully with Vite:

- `public/build/manifest.json`
- `public/build/assets/app-B9jm1EEq.css`
- `public/build/assets/app-Dd1ranYp.js`

## Test Coverage Status

Automated feature coverage remains insufficient. The repository currently has placeholder Laravel tests and no configured frontend lint/test scripts. Meaningful tests should be added after the PHP runtime is upgraded so Laravel can boot.

Recommended first test additions:

- Auth login validation, banned-account denial, logout token clearing.
- Registration and reset OTP expiry.
- Manager middleware fail-closed behavior.
- Sale product tenant isolation.
- Partnership good and record tenant isolation.
- Invalid Stripe webhook signature rejection.
- Backup filename traversal rejection.
- Product sorting whitelist fallback.
- Malformed nested cash/inventory payloads returning 422.
- Invalid sync `since` returning 422.

## Runtime Verification Not Completed

The following checks could not be completed locally because `php artisan` cannot boot under PHP 8.2.12 with the current dependency tree:

- Route-list verification.
- Browser route verification.
- Admin login/logout smoke test.
- Filament dashboard smoke test.
- Full console-error inspection.
- PHP feature tests.

## Required Environment Fix

Install and activate PHP 8.3 or newer with `ext-intl`, then run:

```bash
composer install
composer check-platform-reqs
php artisan route:list
php artisan test
npm run build
```

Add frontend lint/test scripts only after deciding the desired JavaScript linting and test stack for this Laravel/Vite surface.
