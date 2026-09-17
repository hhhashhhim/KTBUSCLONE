# Backend upload, then local app testing

## Upload source

Use `/Users/malik/Desktop/Codes/KTBUSCLONE-mobile-backend` on branch
`mobile-backend` of `hhhashhhim/KTBUSCLONE`. The Flutter checkout at `KTBUSGIT`
is on `mobile-frontend` and is not the backend upload source.

Build a deployment ZIP from a committed, clean `mobile-backend` checkout:

```bash
php artisan test
python3 scripts/build_mobile_backend.py /tmp/ktbus-backend-builds
```

The command requires Git, Python 3, PHP and Composer. It exports the current
commit, installs the locked production Composer dependencies with optimized
autoloading, checks PHP extension requirements, and writes a ZIP and SHA-256
checksum. The ZIP includes `vendor/`, the existing compiled public assets, and
`BUILD_REVISION` identifying its source commit. No JavaScript source is changed
by this backend packaging step. Use PHP and extensions compatible with the target
server; the current dependency lock requires PHP 8.2 or later.

The build excludes local `.env` files, Git metadata, tests, runtime caches, logs,
and `public/uploads/`. The `.env.example` template is included. Existing server
uploads and persistent storage must be preserved or linked into a new release.
Extract the ZIP into the deployment directory and point the web server document
root at Laravel's `public/` directory. Ensure `storage/` and `bootstrap/cache/`
are writable by the PHP process. Configure the private server environment and
perform the deployment steps below. Building does not deploy or migrate a server.

Push source and build tooling to `origin/mobile-backend`. Keep generated ZIPs
and `vendor/` out of Git; distribute the ZIP separately as a deployment artifact.

## Server environment

Preserve the server's existing `APP_KEY`, database connection, and ERP settings.
Do not replace a working production `.env` with the local payment overlay.
Configure these values in the server's private environment:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://mobile-api.kainattravels.com
MOBILE_PAYMENT_ENVIRONMENT=live
MOBILE_ONLINE_PAYMENTS_ENABLED=true
MOBILE_PAYMENT_PREVIEW_ONLY=false
MOBILE_PAYMENT_METHODS=jazzcash,bank_alfalah
MOBILE_JAZZCASH_CHECKOUT_URL=https://onlinepayments.jazzcash.com.pk/payment-orchestrator/CustomerPortal/transactionmanagement/merchantform
MOBILE_JAZZCASH_STATUS_URL=https://onlinepayments.jazzcash.com.pk/payment-orchestrator/api/v2/rest/payments/status/inquiry
MOBILE_ALFALAH_BASE_URL=https://payments.bankalfalah.com
```

Transfer the existing merchant credentials privately into the corresponding
`MOBILE_JAZZCASH_*` and `MOBILE_ALFALAH_*` variables listed in `.env.example`.
Retain the WhatsApp OTP settings described in `MOBILE_API.md`. Set
`MOBILE_COMPANY_ID`, `MOBILE_TERMINAL_ID`, and `MOBILE_BOOKING_USER_ID` to valid
records in the same company. A company row in `mobile_app_configs` can override
`payment_methods`; it must include the desired online methods.

## Install and migrate

Install locked dependencies with `composer install --no-dev --optimize-autoloader`.
Review `php artisan migrate:status` and back up the target database before applying
pending migrations. The existing ERP schema must already be installed. These
mobile migrations are applied in date order; Laravel skips ones already recorded:

```bash
php artisan config:clear
php artisan migrate --force \
  --path=database/migrations/2026_07_18_000001_create_passenger_accounts_table.php \
  --path=database/migrations/2026_07_18_000002_create_mobile_app_configs_table.php \
  --path=database/migrations/2026_07_18_000003_create_mobile_booking_quotes_table.php \
  --path=database/migrations/2026_07_18_000004_add_passenger_account_id_to_invoices.php \
  --path=database/migrations/2026_07_18_000005_create_saved_passengers_table.php \
  --path=database/migrations/2026_07_22_000006_create_mobile_fleet_media_table.php \
  --path=database/migrations/2026_07_22_000007_enable_mobile_wallet_feature.php \
  --path=database/migrations/2026_09_12_000008_create_mobile_payments_table.php \
  --path=database/migrations/2026_09_12_000009_add_environment_to_mobile_payments.php \
  --path=database/migrations/2026_09_15_000010_create_mobile_account_deletions_table.php \
  --path=database/migrations/2026_09_17_000011_fix_mobile_payment_expiry_column.php
php artisan route:clear
php artisan config:cache
```

Restart long-running PHP workers if used. Configure HTTPS/proxy handling so signed
payment URLs and provider return URLs retain the public HTTPS origin. Register
`/api/mobile/v1/payments/{payment-uuid}/return` with both providers (GET and POST).
The providers must accept the dynamic UUID path.

Ensure the existing Laravel scheduler runs every minute. If no scheduler is
already installed for this deployment, add a crontab entry using its actual path:

```cron
* * * * * cd /absolute/path/to/backend && php artisan schedule:run >> /absolute/path/to/backend/storage/logs/scheduler.log 2>&1
```

Use a shared lock-capable cache when running multiple app instances. Confirm
`mobile:reconcile-payments` runs; monitor reconciliation warnings and payments
marked `review_required`. The scheduler also runs existing ERP jobs, so do not
install a duplicate scheduler for the same application.

## Verify backend and run the app locally

```bash
curl --fail --show-error https://mobile-api.kainattravels.com/api/mobile/v1/app/config
```

Require a JSON success response, `payment_preview: false`,
`payment_environment: live`, and `features.online_payments: true`. If methods are
absent, check the company override, ledger migrations, merchant settings and
HTTPS `APP_URL`. Do not treat an HTML page or HTTP 404 as a working mobile API.

On your local computer, from `KTBUSGIT/kt_mobile_app`:

```bash
flutter devices
flutter run --debug --dart-define-from-file=dart_defines.production.json
```

This uses a debug-signed app with production API settings. It does not need the
Play upload key. Check OTP/login, schedules, seats, quote and payment options.
Live checkout uses real merchant accounts: completing checkout can charge real
money. Provider acceptance, actual callbacks and payment settlement must be
verified separately; automated tests use fake gateway responses.

After local testing, follow `kt_mobile_app/docs/ANDROID_RELEASE.md` on
`mobile-frontend` and supply the existing Play upload key for the signed AAB.

## Immediate payment expiry repair

If a new booking immediately expires, inspect the `expires_at` column in
`mobile_payments`. `ON UPDATE CURRENT_TIMESTAMP` is incorrect for this business
deadline. After backing up the database, apply only the corrective migration:

```bash
php artisan migrate --force --path=database/migrations/2026_09_17_000011_fix_mobile_payment_expiry_column.php
```

Verify that the column is `datetime`, non-null, with no automatic default or
`on update` attribute. Existing stored deadlines and statuses are preserved;
previously expired bookings stay expired. Test a new booking after the migration.
This is a forward-only repair; migration rollback deliberately does not restore
the faulty timestamp behavior. No Composer install or Flutter rebuild is needed.

The normal test suite covers repeated payment refreshes before checkout. To also
reproduce the legacy database behavior and test both upgrade and fresh-install
schemas, run the opt-in MySQL regression against a local test database:

```bash
MOBILE_MYSQL_TEST_DATABASE=your_local_test_database php artisan test --filter=MobilePaymentExpirySchemaTest
```

The regression creates only a connection-local temporary `mobile_payments` table;
it does not change permanent tables. Optional `MOBILE_MYSQL_TEST_HOST`,
`MOBILE_MYSQL_TEST_PORT`, `MOBILE_MYSQL_TEST_USERNAME`, and
`MOBILE_MYSQL_TEST_PASSWORD` select the test connection. It never uses the
application's `.env` database credentials as a fallback.
