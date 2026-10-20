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
MOBILE_PAYMENT_METHODS=bank_alfalah
MOBILE_JAZZCASH_CHECKOUT_URL=https://onlinepayments.jazzcash.com.pk/payment-orchestrator/CustomerPortal/transactionmanagement/merchantform
MOBILE_JAZZCASH_STATUS_URL=https://onlinepayments.jazzcash.com.pk/payment-orchestrator/api/v2/rest/payments/status/inquiry
MOBILE_ALFALAH_BASE_URL=https://payments.bankalfalah.com
```

Transfer the existing merchant credentials privately into the corresponding
`MOBILE_JAZZCASH_*` and `MOBILE_ALFALAH_*` variables listed in `.env.example`.
Retain the WhatsApp OTP settings described in `MOBILE_API.md`. Set
`MOBILE_COMPANY_ID`, `MOBILE_TERMINAL_ID`, and `MOBILE_BOOKING_USER_ID` to valid
records in the same company. A company row in `mobile_app_configs` can override
`payment_methods`; it must include `bank_alfalah` for card checkout and may include
`counter` for branch reservations. JazzCash is excluded from new mobile bookings
even if a previous environment/company list still contains it. Retain JazzCash
credentials/callbacks for existing payments until reconciliation is complete.

The 19 October 2026 changes also add mobile-only authentication rate limits and
official support phone/email defaults. Deploy `app/Providers/RouteServiceProvider.php`,
`routes/api/mobile.php`, `app/Services/Mobile/MobileBookingService.php` and
`config/mobile.php` together; clear route/config caches and rebuild config cache.
These changes require no new migration. Use a persistent shared cache for the
authentication attempt budgets. Review `MOBILE_API.md` for the 429 contract.

Configure approved `MOBILE_ANDROID_STORE_URL`, `MOBILE_IOS_STORE_URL` and optional
`MOBILE_SUPPORT_WHATSAPP`. The public site's legacy Play package differs from the
Flutter project; confirm the correct store identity and upload key before release.
Keep mandatory updates disabled until the corresponding store URLs work.

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
  --path=database/migrations/2026_09_17_000011_fix_mobile_payment_expiry_column.php \
  --path=database/migrations/2026_09_18_000012_align_mobile_reservations_with_erp_status.php
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


## cPanel update for the 18 September reservation and fare release

The existing API checkout is `/home/wwwkaina/kt-mobile-api`, on `mobile-backend`
of `hhhashhhim/KTBUSCLONE`. The separate `kt-mobile-api-source` directory is not
the checkout updated by these commands. Keep the server's `.env`, `APP_KEY`,
merchant credentials, uploads, and persistent storage.

This release contains fare calculation alignment, merchant-configurable JazzCash
routing fields (empty by default), and ERP-compatible reservation statuses. It
does not add a failed-payment retry endpoint. Composer dependencies are unchanged.

Check `git status --short` and compare any modified source files with
`origin/mobile-backend` before pulling. Do not reset or discard server edits.
Previously observed generated changes in `bootstrap/cache/packages.php` and
`bootstrap/cache/services.php` and untracked `.env` backups are not source changes
for this release and must not be staged or committed.

Back up the database before the data migration. Put this application into Laravel
maintenance mode before pulling. Its registered scheduled tasks do not opt into
maintenance-mode execution, so subsequent `schedule:run` invocations skip them.
Allow in-flight booking requests and scheduler/queue jobs to finish first; pause
any jobs started directly outside Laravel's scheduler, and any other application
that can modify the same mobile reservations during the migration.

Once those checks are complete, run this block in cPanel Terminal:

```bash
cd /home/wwwkaina/kt-mobile-api &&
php artisan down &&
git pull --ff-only origin mobile-backend &&
php artisan config:clear &&
php artisan migrate --force --path=database/migrations/2026_09_18_000012_align_mobile_reservations_with_erp_status.php &&
php artisan route:clear &&
php artisan view:clear &&
php artisan config:cache &&
php artisan queue:restart &&
php artisan up
```

If a command fails, the chain stops. Keep the application in maintenance mode
until the failure is resolved, especially if new code is installed but the data
migration has not completed. Do not add an unconditional `artisan up` on failure.
The earlier payment-expiry repair was already confirmed applied on this server
(batch 90); the new migration above is separate. Apply both in date order on a
server where the earlier repair has not run.

The new migration converts only active legacy `pending booking` rows linked to
a same-company pending mobile payment, including their active history records.
It preserves expiry deadlines, cancelled seats, receipts, and unrelated bookings.
No production migration is run by the local build or Git push.

After success, resume any explicitly paused jobs and verify:

```bash
php artisan migrate:status --path=database/migrations/2026_09_18_000012_align_mobile_reservations_with_erp_status.php
git log -1 --oneline
curl --fail --silent --show-error https://mobile-api.kainattravels.com/api/mobile/v1/app/config
```


### ERP user/app controls

Deploy the passenger-control middleware, service, authentication limiters, and
booking/OTP changes together. Apply only the reviewed control migration via:

```sh
php artisan migrate --path=database/migrations/2026_10_20_000005_create_mobile_passenger_control_policies.php --force
php artisan route:clear
```

The matching ERP controls/privileges are on `api`, and the screens on `mix`.
They must share company data with this runtime. If ERP already created these
tables, the migration preserves existing policies and restrictions. Retain the
shared persistent cache; do not clear it during ordinary releases, as that resets
OTP attempt budgets. Existing departure locks and the new per-account database
lock must remain active. Rollback intentionally retains restrictions and usage.

### Login analytics and ERP route monitoring

1. Apply only `database/migrations/2026_10_20_000006_create_mobile_app_monitoring.php`
   using the normal reviewed deployment procedure. The same migration name on
   ERP `api` is safe to deploy against the shared database; do not run unrelated
   legacy migrations to install this feature.
2. Deploy `MobileAppTelemetry`, `MobileAuthController` and `MobileAppController`.
   Verify MOBILE_COMPANY_ID, MOBILE_TERMINAL_ID and MOBILE_BOOKING_USER_ID point
   to the intended company and active online terminal/service user. The mobile
   backend and ERP monitoring must read the same company database.
3. A normal app-config request reports runtime configuration. Subsequent
   successful logins/signup sign-ins populate the durable event history. Never
   backfill “total logins” from active tokens or call app opens downloads.
4. Deploy ERP monitoring endpoints and the rebuilt `mix` bundle. Until the
   runtime report arrives, availability is clearly marked as a terminal preview
   and login totals are unavailable. Actual store downloads require a separate
   Google Play / App Store reporting connection.

Local validation: 183 OTP, authentication rate-limit and scheduling tests passed
(1,529 assertions). No live mobile deployment or store connection was performed.


## Biometric quick login rollout

1. Deploy the mobile-backend biometric service/controller, routes, command,
   configuration, and migration with existing passenger-control code.
2. Run `php artisan migrate --path=database/migrations/2026_10_20_000007_create_mobile_biometric_sessions.php`.
   Rebuild configuration/route caches using the deployment's normal process.
3. Optional environment settings: `MOBILE_BIOMETRIC_LOGIN_ENABLED=true`,
   `MOBILE_BIOMETRIC_SESSION_DAYS=30`. Keep existing access-token expiry at 600 minutes.
4. Run the existing Laravel scheduler for daily expired-family cleanup.
5. Release the updated Android/iOS app. Existing app versions still use password
   login. The new app can continue password login if this API is not yet deployed.
6. On real devices, verify enrollment, cancellation, biometric lockout, enrollment
   changes, cold launch, expiry renewal, logout, password reset and ERP revocation.
   No production account or device biometric setup should be changed for automated QA.

This source change does not deploy the API, run production migrations, or publish
an app release. Biometric credentials require an online server check and never
contain the passenger password. Redact `refresh_token` anywhere request bodies
could be logged. Rollback should disable the feature first; password login remains.

### Recover from biometric migration error 1067

On legacy MySQL/MariaDB timestamp settings, the original biometric token table
can fail with `Invalid default value for 'created_at'`. The sessions table has
already been created, but Laravel has not recorded the migration as complete.
The repaired migration uses `DATETIME` for token creation time and creates each
table only when missing, preserving existing rows when the migration resumes.

Keep the API in maintenance mode and preserve the database backup. Deploy the
repaired migration from `mobile-backend`; do not drop the sessions table, roll
back the earlier successful control/monitoring migrations, or change SQL modes.
Review `php artisan migrate:status` and run only the remaining migration:

```bash
php artisan config:clear &&
php artisan migrate --force --path=database/migrations/2026_10_20_000007_create_mobile_biometric_sessions.php &&
php artisan route:clear &&
php artisan config:cache &&
php artisan queue:restart &&
php artisan up
```

If any command fails, leave the API in maintenance mode and resolve the error.
After success, confirm that the migration is recorded with `migrate:status` and
that `https://mobile-api.kainattravels.com/api/mobile/v1/app/config` returns JSON.
Then verify biometric enrollment and renewal using the updated app on a real
device; successful schema deployment alone does not establish that device flow.


## Passenger notifications rollout

Deploy the notification schema, inbox/controller, booking-change scanner and scheduled
command from `mobile-backend`, the administration endpoints/privileges from `api`,
and rebuilt screens from `mix`. See [MOBILE_NOTIFICATIONS.md](MOBILE_NOTIFICATIONS.md)
for scoped migration commands, Firebase environment settings, scheduling and staged
verification. Flutter has a matching setup guide in `kt_mobile_app/docs/NOTIFICATIONS.md`.
The inbox does not require Firebase; phone delivery does. No deployment or live
message send is implied by a successful local build.
