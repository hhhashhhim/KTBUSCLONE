# Backend upload, then local app testing

For the current 6 November release follow-up, use
[the scoped release runbook](MOBILE_RELEASE_2026-11-06.md). The earlier hosting
inspection below is historical: a later read-only check confirmed the mobile API
at `b78d1cc1`. Always verify the live revision and migration status again.

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

## cPanel update for the 23 October journey-time and seat-map release

This update populates schedule `arrival_at` from the existing ERP city-pair
`fare_tables.time_difference`. Overnight and multi-day journeys are supported;
missing or invalid durations keep `arrival_at` null. Configure journey durations
through the ERP for city pairs that do not have them. No new database migration
or Composer dependency change is required.

The release also includes `booked_gender` (`male`, `female`, or `null`) on occupied
seat responses. The corresponding Flutter screens display the estimated journey
times and seat colours; older apps can ignore these fields. Earlier registration,
notification, and biometric migrations must already be installed; use their
scoped rollout sections below if pending.

In cPanel Terminal, first check the existing API checkout:

```bash
cd /home/wwwkaina/kt-mobile-api
git remote -v
git branch --show-current
git status --short
```

Confirm `origin` is `hhhashhhim/KTBUSCLONE` and the branch is `mobile-backend`.
Review any local source changes before continuing; do not discard them or switch
branches automatically. Preserve the server's existing `.env`, `APP_KEY`, uploads,
and storage. Run each command below separately, in order. Stop immediately if
one fails; do not continue to the next command until the error is resolved:

```bash
cd /home/wwwkaina/kt-mobile-api
php artisan down
git pull --ff-only origin mobile-backend
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan queue:restart
php artisan up
```

If a command fails after maintenance mode starts, resolve it before bringing
the app back up. Do not clear the application cache: it holds OTP attempt budgets.
Use the same PHP version for Composer and Artisan; the dependency lock requires
PHP 8.2 or later. After a successful update, verify the commit and public API:

```bash
git log -1 --oneline
curl --fail --silent --show-error https://mobile-api.kainattravels.com/api/mobile/v1/app/config
```

## Pending registration security rollout (21 October 2026)

Deploy the backend authentication files and the matching Flutter build together.
The app now restores sessions through `GET /auth/session` and persists the new
full token returned by OTP verification. Older builds retain a revoked pending
token after verification and need updating.

After the existing mobile schema is installed, apply this additional migration:

```bash
php artisan migrate --force --path=database/migrations/2026_10_21_000009_create_pending_passenger_registrations.php
```

It creates a separate pending-registration table; it does not migrate or delete
existing ERP/customer/passenger data. `MOBILE_REGISTRATION_TTL_HOURS` defaults to
24. Codes still default to 10 minutes through `MOBILE_OTP_TTL_MINUTES`. Clear and
rebuild route/config caches. Keep a persistent shared cache for rate limits.

The scheduler invokes `mobile:prune-pending-registrations` hourly. The command
can also be run manually after migration. It deletes only expired temporary
signups and their tokens. Expired signups are denied immediately even if cleanup
has not run. Legacy unverified accounts are restricted and their customer details
are hidden until verification; they are not automatically removed. Review any
customer edits made before this fix separately; this rollout cannot undo them.

### Files changed for pending registration security

- `.env.example`
- `MOBILE_API.md`
- `MOBILE_DEPLOYMENT.md`
- `app/Console/Commands/PrunePendingPassengerRegistrations.php`
- `app/Console/Kernel.php`
- `app/Http/Controllers/Mobile/MobileAuthController.php`
- `app/Http/Middleware/Authenticate.php`
- `app/Http/Middleware/CustomSanctumTokenVerify.php`
- `app/Http/Middleware/EnforceMobilePassengerControls.php`
- `app/Http/Requests/Mobile/RegisterPassengerRequest.php`
- `app/Http/Resources/Mobile/PassengerResource.php`
- `app/Models/PendingPassengerRegistration.php`
- `app/Providers/RouteServiceProvider.php`
- `app/Services/Mobile/MobileOtpService.php`
- `app/Services/Mobile/MobilePassengerControls.php`
- `app/Services/Mobile/MobileRegistrationService.php`
- `config/mobile.php`
- `database/migrations/2026_10_21_000009_create_pending_passenger_registrations.php`
- `routes/api/mobile.php`
- `tests/Feature/MobileAccountDeletionTest.php`
- `tests/Feature/MobileApiContractTest.php`
- `tests/Feature/MobileBiometricLoginTest.php`
- `tests/Feature/MobileOtpTest.php`
- `tests/Feature/MobileSchedulingRulesTest.php`

The separate `mobile-frontend` work unit changes only:

- `kt_mobile_app/lib/features/auth/data/auth_repository.dart`
- `kt_mobile_app/test/auth_otp_test.dart`
- `kt_mobile_app/test/session_recovery_test.dart`
- `kt_mobile_app/docs/WHATSAPP_OTP.md`
- `kt_mobile_app/pubspec.yaml` (version `1.0.4+5`)
- `kt_mobile_app/docs/ANDROID_RELEASE.md` (matching test-build instructions)

Local checks: the full backend suite passed with two optional MySQL checks skipped
(no dedicated MySQL test database configured), followed by additional OTP/linking
regression checks. PHP syntax checks and Git whitespace checks passed. The full
Flutter suite passed; additional OTP/session-recovery tests and `flutter analyze`
also passed. Backend tests use disposable SQLite databases and fake WhatsApp HTTP.
The existing PHP dependency emits a nullable-parameter deprecation on PHP 8.4;
it did not fail the tests. No real OTP, production database migration or deployment
was performed. Backend and Flutter changes remain separate in their respective
worktrees/branches; source commits and build artifacts are prepared separately.

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

## Mobile-only security rollout (29 October 2026)

These changes are confined to the mobile app and its dedicated API. The shared
Laravel dependency lock, legacy payment helpers, ERP reports/controllers, and
staff-facing routes are unchanged. Their previously reported dependency,
credential and debug-route risks remain separate work; this release must not be
represented as a complete ERP/server security remediation.

Back up and stage the dedicated mobile API deployment. Apply only the reviewed
mobile quote migration using the deployment's actual PHP executable:

```bash
php artisan migrate --force --path=database/migrations/2026_10_29_000001_link_mobile_booking_snapshots.php
```

The migration changes only `mobile_booking_quotes` and preserves current records.
New mobile booking creation returns 503 until it is installed. Do not roll back
this metadata when rolling back mobile application code. Review the new allowance
values in `.env.example`; existing numeric account/company overrides are retained.
Rebuild the dedicated API's route/config caches through its normal deployment
process. Keep the existing scheduler; it now also prunes abandoned mobile quotes.

### Files changed in this mobile API work unit

- `.env.example`, `config/mobile.php`: mobile allowances and dedicated worker settings.
- `app/Services/Mobile/MobileBookingService.php`: customer protection and booking snapshots.
- `app/Services/Mobile/MobilePassengerControls.php`: reservation limits and account locking.
- `app/Services/Mobile/MobileNotificationDelivery.php`, `app/Services/Mobile/MobileBackgroundQueue.php`: private queued delivery and connection validation.
- `app/Jobs/Mobile/ReconcilePayment.php`, `app/Jobs/Mobile/DeliverNotification.php`: dedicated mobile jobs.
- `app/Console/Commands/ReconcileMobilePayments.php`, `app/Console/Commands/PruneMobileBookingQuotes.php`, `app/Console/Kernel.php`: mobile dispatch and cleanup scheduling.
- `database/migrations/2026_10_29_000001_link_mobile_booking_snapshots.php`: mobile quote metadata only.
- `tests/Feature/MobileSchedulingRulesTest.php`, `tests/Feature/MobileNotificationsTest.php`: security and worker regressions.
- `MOBILE_API.md`, `MOBILE_DEPLOYMENT.md`: contract and rollout documentation.

Local validation on 29 October 2026: 366 backend cases, 364 passed and two optional
MySQL checks skipped, with 2,396 assertions. Tests used disposable SQLite and fake
provider/queue clients. The matching Flutter checkout passed all 219 tests and
`flutter analyze`; changed/new PHP syntax checks and Git whitespace checks passed.
These checks do not establish live provider acceptance, MySQL concurrency or load
capacity. No production migration, app build, deployment, commit or push was run.

### Optional dedicated mobile background workers

For higher traffic, set `MOBILE_BACKGROUND_QUEUE_CONNECTION` to an existing durable
Laravel queue connection such as `redis` or `database`. Start workers first and
keep the same queue settings on the mobile scheduler and mobile workers:

```dotenv
MOBILE_BACKGROUND_QUEUE_CONNECTION=redis
MOBILE_BACKGROUND_QUEUE_NAME=mobile
MOBILE_NOTIFICATION_BATCH_SIZE=1000
```

```bash
php artisan queue:work redis --queue=mobile --sleep=1 --tries=3 --timeout=60
```

Run supervised workers and verify the selected connection's `retry_after` exceeds
the worker timeout (the current Redis/database default is 90 seconds). The
`database` option requires the existing jobs/failed_jobs tables; check installation
before enabling it. These settings do not change the ERP's default queue or queue
names. Leaving the connection empty preserves synchronous mobile processing.
Unknown, sync and null queue connections are rejected when background mode is set.

Payment checks are dispatched individually, scoped to `MOBILE_COMPANY_ID`, with
bounded retries and per-payment deduplication. Mobile push jobs contain only
internal delivery/company IDs, recheck account/token/feature eligibility at send
time, and retain the existing retry ledger. Queued mode dispatches up to the
configured batch per scheduler run; inline mode keeps its existing 100-delivery
batch. Multiple workers can process independent payments and notifications.

All app instances and workers that sell the same seats must coordinate through
the same shared lock-capable cache and namespace, including counter/web writers.
Do not add API replicas while each server uses separate file-based locks. This
release keeps the existing cross-channel schedule lock. Verify shared-cache,
worker, provider-rate-limit, and MySQL-concurrency behavior in staging before
scaling; no maximum-user capacity has been measured by these changes.

Flutter changes are confined to `kt_mobile_app/` on `mobile-frontend`. Both mobile
platforms now validate HTTPS production configuration before API use. Passenger
API requests reject other origins/paths and disable redirects; checkout WebViews
retain their separate payment-provider navigation flow. A new mobile build is
needed for those client checks; the booking controls are enforced by the API.

## Data retention and Play Data safety release gate

Prepared on 2026-11-06; **not a statement of the live server's current behavior**.
The following periods are proposed company policy unless explicitly marked as
owner-confirmed; they are not Google-mandated deadlines.
Approve the policy, deploy and verify the implementation, update both the public
privacy/deletion pages and Flutter privacy text, then complete Data safety.

| Data | Proposed period and prepared enforcement |
| --- | --- |
| Account credentials/profile, saved travellers, unused/unlinked quotes, biometric sessions/tokens, push devices/deliveries, inbox and account login telemetry | Removed transactionally on confirmed in-app account deletion. Account controls and seat counters also removed; documented fraud restrictions remain separately. |
| Normal notification/delivery history | 90 days from notification creation; routine cleanup defers active delivery leases. Campaign definitions and booking-state idempotency markers remain to prevent replay. |
| Stale push registrations | 90 days since last registration/update; removed sooner for an invalid token or account deletion. Backend receipt of an FCM delivery is not evidence of user activity. |
| Successful login events | 90 days in UTC. A hidden per-account first-login date survives until account deletion so old users are not counted as new after history expiry. Reports become retention-window reports. |
| OTP and reset authentication material | Existing OTP validity 10 minutes and reset-token validity 15 minutes. Prepared daily expiry cleanup removes expired hashes; physical cleanup can lag expiry by one scheduler interval. Pending signup records still expire after 24 hours and the existing hourly job removes them. |
| Expired unused booking quotes | Existing daily quote job removes drafts more than one day past expiry. Used or invoice-linked passenger snapshots are retained with booking evidence. |
| Minimal deletion evidence | Proposed 365 days: surrogate account ID, company ID and deletion timestamp only. The job refuses audit expiry until the maximum backup age is confirmed and strictly shorter than this period. |
| Application and access/security logs | Proposed 30 days. New opt-in `mobile_daily` Laravel channel rotates application logs; host/CDN/WAF/APM logs require separate operator settings. Old log files need a separate approved cleanup. |
| Hosting backups | Owner confirmed on 6 November 2026 that hosting backups expire within 30 days. This is a hosting-level period, not application cleanup. Backup location, encryption, access and restoration controls still require verification. Replay deletions before restored data can serve traffic. |
| Separate replicas, manual exports and other restore copies | Proposed maximum 30 days, encrypted and access restricted; not covered by the hosting-backup confirmation or implemented by application code. Inventory their actual lifetimes and expire them through the responsible operator. |
| ERP customer/ticket/payment/loyalty data and booked passenger snapshots | No automatic financial purge in this change. Accountant/legal owner must approve required fields, periods, unresolved transaction handling and legal holds. Retaining a ticket does not justify retaining every profile field. |
| Identity-hash blacklist and investigations | Restricted security/fraud exception; hashes remain personal data. Named owner reviews necessity/expiry at least annually and documents case-specific holds. No automatic unblocking or ERP purge in this change. |

For tax records, [FBR guidance](https://fbr.gov.pk/categ/file-income-tax-return/51147/80860/71163)
states six years. Registered companies may also be subject to Companies Act 2017
section 220(5), requiring books and relevant vouchers for at least ten preceding
financial years ([official SECP Act](https://www.secp.gov.pk/document/the-companies-act-2017-updated-18-aug-2022/?wpdmdl=45422)).
Confirm the operating legal entity and provincial tax/transport requirements with
its accountant. Do not publish “one financial year,” six years, or ten years as a
blanket rule for all passenger data. Existing public/mobile wording must be reconciled
with the approved field-level schedule before release.

### Deploy and verify without changing the ERP retention policy

1. Review this diff alongside the already pending backend changes. Take a protected
   deployment backup and record its expiry; do not commit secrets or production `.env`.
2. Deploy backend files on the separate mobile API host and run the scoped migration
   `database/migrations/2026_11_06_000001_preserve_mobile_first_login.php` through the
   approved deployment procedure. It adds a nullable account-lifetime marker and
   backfills existing company-matched login history in bounded batches. Test on the
   actual MySQL version first. The migration intentionally preserves the marker on rollback.
3. Keep `MOBILE_RETENTION_ENABLED=false`; run `php artisan mobile:prune-personal-data`.
   This is read-only preview with counts, never phone numbers, CNICs or tokens. Review
   counts against staging fixtures and the approved periods. Execution requires both
   the enable flag and explicit `--execute`. Company ID must be configured. Missing
   tables/columns are guarded; resolve reported migration gaps before activation.
4. Set confirmed periods from `.env.example`. The owner has confirmed a 30-day
   hosting-backup expiry. Leave `MOBILE_RETENTION_BACKUP_MAX_DAYS` blank until the
   longest relevant restorable-copy lifetime is established, including any separate
   replicas or manual exports. Set it to 30 only if no relevant restore source lasts
   longer. Rebuild Laravel config cache and restart workers
   through the normal deployment procedure after changing configuration.
5. After review, enable the job. The Laravel scheduler runs
   `mobile:prune-personal-data --execute` daily with overlap protection; the command
   has its own execution gate. Verify the existing minute cron invokes `schedule:run`,
   monitor success/failure and confirm counts decrease. A paused or failed scheduler
   means physical retention promises are not being met. Repeat dry-run until eligible
   backlog is cleared; do not infer completion from a single large-batch run.
6. On this mobile API host only, choose `LOG_CHANNEL=mobile_daily`,
   `MOBILE_LOG_RETENTION_DAYS=30`, `LOG_LEVEL=info` and `APP_DEBUG=false` after confirming
   operational needs. This opt-in preserves the existing ERP log configuration.
   Rotation does not remove the old `laravel.log`, host logs, exports or backups.
   Inspect error logging for request bodies, credentials, OTPs, full CNICs and payment
   payloads; redact at source. Arrange separate host/CDN/security-log retention.
7. Verify a disposable account in staging: delete it, confirm access/biometric tokens
   stop working, private rows disappear, pending push jobs cannot restore them, and
   other companies plus ticket/payment records remain unchanged. Exercise concurrent
   deletion/profile/quote/device requests on MySQL; SQLite tests prove stale-request
   handling but do not simulate real database lock contention.
8. Test restore procedure on an isolated host. Keep a protected, current deletion
   ledger outside the restored snapshot; reapply account deletions and private-data
   cleanup before enabling any API, notification or payment workers. Audit-row expiry
   guards alone do not implement backup restoration or provider erasure.

Historical deleted-account cleanup uses the minimal deletion ledger to remove
leftover mobile-only records. It does not guess that an arbitrary missing account
or an unmatched ERP row is safe to erase. Used quotes, shared financial records,
blacklists, campaign definitions and booking-state markers are not aged out by this
job. Any necessary retention change to these records is a separate reviewed unit.

**ERP writer dependency:** read-only inspection of the `api` branch found that
`app/Http/Controllers/Api/MobileAppNotificationController.php::send()` selects
campaign recipients before its transaction and later bulk-inserts notification
rows without locking/rechecking passenger accounts. An in-flight staff campaign
can therefore recreate an inbox row after account deletion. Confirm the deployed
ERP revision and fix that writer on its explicitly approved branch: revalidate
company/account ownership and lock accounts in consistent ID order inside the
transaction before inserting. Do not deploy the `api` branch wholesale or copy it
into this worktree. The retention job repairs ledger-backed orphans on its next
run, but immediate deletion across all writers requires this separate fix.
Campaign audience metadata in that source contains audience type/schedule ID,
not a saved list of passenger IDs; preserve campaign idempotency state.

### Processor and frontend follow-up

SAR Zone WhatsApp receives the OTP destination and template/code fields. Bank
Alfalah receives hosted-checkout transaction information and may collect payment
details directly. Firebase receives messaging registration/installation data. The
backend configuration documents these integrations; it does not establish their
contracts, subprocessors, server regions or deletion commitments.

Deleting our stored FCM token stops our future sends but does not itself delete the
Firebase installation. Review the Flutter deletion flow and provider API separately
on `mobile-frontend`; Firebase documents up to 180 days for associated live/backup
data after an installation-deletion request. Do not promise all provider copies are
gone in 30 days. Confirm actual Firebase products and SDK collection using
[Firebase Play disclosures](https://firebase.google.com/docs/android/play-data-disclosure)
and [installation deletion](https://firebase.google.com/docs/projects/manage-installations#delete_a_firebase_installation).

Assign a company owner for the public deletion page/inbox and processor requests.
A proposed manual-request target is 30 days, with identity verification, completion
tracking, necessary retention exceptions and processor follow-up. Google requires
reasonably quick handling, not a universal 30-day deadline. Keep an in-app deletion
route and a working external request page identifying Kainat Travels and describing
retained categories/periods ([Google deletion requirements](https://support.google.com/googleplay/android-developer/answer/13327111?hl=en)).

### Data safety evidence worksheet

Confirm behavior for the actual released app, enabled features, SDKs and live backend;
do not submit these as final declarations based on source code alone.

| Google category to check | Evidence and purpose |
| --- | --- |
| Personal info: name, phone, email, user IDs and other info (including CNIC/gender where collected) | Account management, authentication, traveller/booking fulfillment. Record which fields are mandatory or optional at each flow. |
| Financial info: purchase history / applicable other financial info | Tickets, payment status/reference and loyalty balances. Hosted card details can be excluded only if collected directly by the provider under its own terms and never accessed by the app. |
| App activity: app interactions | Backend signup/password/biometric login events and notification interaction history exist even without an analytics SDK. |
| Device or other IDs | FCM registration/installation identifiers and account-bound device/session records. |

Do not mark all data “not shared” merely because it goes to a service provider:
Google's processor exception depends on processing on the developer's behalf.
Confirm WhatsApp, bank, Firebase and hosting contracts and actual data use. Verify
encryption in transit for the app, proxy-to-origin, database and processor paths;
HTTPS in app configuration alone is insufficient evidence for every path. Confirm
any crash analytics, diagnostics, analytics exports or location use before adding or
excluding those categories. Read [Google Data safety guidance](https://support.google.com/googleplay/android-developer/answer/10787469?hl=en).

### Live details still required from the owner/operator

Provide non-secret facts or a redacted hosting report; never paste passwords, API
keys, private keys, service-account JSON, full `.env` or passenger database dumps.

- **Operator and deployment:** host/provider name, region, API document root/release
  revision, PHP/MySQL versions, installed mobile migrations, company scope, cron
  entry and worker/queue names. Existing documentation suggests cPanel at
  `/home/wwwkaina/kt-mobile-api`; verify the live path and deployment.
- **Transport and logging:** proxy/CDN/WAF providers; origin and database connection
  encryption; Laravel log channel/level/rotation; web access/error, security, APM,
  worker and database log retention and whether request bodies are recorded.
- **Every retained copy:** backup provider, frequency, oldest retained copy, replica,
  snapshot/export/download lifetimes, encryption/access controls, expiry mechanism
  and person responsible for restore/deletion replay.
- **Processors:** SAR Zone/WhatsApp, payment bank and Firebase products actually in
  use; contracts/purpose, retained fields, regions/subprocessors, deletion contact
  and retention commitments. Confirm whether Analytics, Crashlytics or BigQuery
  exports are enabled in addition to FCM, and whether legacy JazzCash remains active.
- **Business approval:** operating legal name/entity type, tax registrations and
  financial year; accountant-approved financial/CNIC schedule and legal holds;
  owner for fraud-restriction review, public privacy/deletion pages and manual requests.

Until these facts and deployment verification are complete, keep the Play Data
safety form as a draft. No production retention/deletion execution is authorized
or performed by preparing this change.

### Local validation and change inventory — 6 November 2026

Validation used a disposable copy of the current `mobile-backend` source, with a
fresh `composer install --no-interaction --no-scripts --no-plugins --prefer-dist`
from the unchanged `composer.json` and `composer.lock`. No production `.env`,
storage contents or cached bootstrap configuration were copied. The runner cleared
inherited application settings and used `APP_ENV=testing`, in-memory SQLite,
array cache/mail/session drivers and the synchronous test queue. Runtime: PHP
8.4.21 and PHPUnit 9.6.34. The existing local `vendor` directory has Symfony files
inconsistent with its installed-version metadata; it was left untouched.

- Full mobile regression: **345 tests, 2,351 assertions, zero failures, two
  explicitly skipped MySQL integration cases**. Both skips are
  `MobilePaymentExpirySchemaTest::test_deadline_survives_payment_updates_on_legacy_mysql`,
  for the `existing cPanel schema` and `new installation` data sets. They require
  an explicitly configured `MOBILE_MYSQL_TEST_DATABASE`; no live database was used.
- After the final retention configuration parsing adjustment,
  `MobileDataRetentionTest` passed again: **15 tests, 51 assertions**.
- `php -l` passed for all **30 changed or untracked PHP files** in the worktree,
  including pre-existing work. `git diff --check` passed.
- The SQLite tests verify deletion scope, stale-writer rejection, retention
  boundaries, company isolation, first-login preservation and authentication
  cleanup. They do not prove concurrent MySQL lock behavior, host scheduling,
  deployed configuration, provider erasure or backup restoration.

This retention/deletion task changes the following backend files; several already
contained unrelated pending backend work, which remains separate in scope:

| Area | Files changed for this task |
| --- | --- |
| Cleanup services and command | `app/Services/Mobile/MobileAccountDataDeletion.php`, `app/Services/Mobile/MobilePersonalDataRetention.php`, `app/Console/Commands/PruneMobilePersonalData.php` |
| Account and personal-data writers | `app/Http/Controllers/Mobile/MobileAccountDeletionController.php`, `MobileNotificationController.php`, `MobileProfileController.php`, `MobileSavedPassengerController.php`; `app/Services/Mobile/MobileBookingService.php`, `MobileNotificationDelivery.php`, `MobileNotificationInbox.php` |
| First-login preservation | `app/Services/Mobile/MobileAppTelemetry.php`, `app/Models/PassengerAccount.php`, `database/migrations/2026_11_06_000001_preserve_mobile_first_login.php` |
| Configuration and scheduling | `.env.example`, `config/mobile.php`, `config/logging.php`, `app/Console/Kernel.php` |
| Regression coverage | `tests/Feature/MobileAccountDeletionTest.php`, `MobileNotificationsTest.php`, `MobileSchedulingRulesTest.php`, `MobileDataRetentionTest.php`, `MobileRetentionTelemetryTest.php` |
| API/operator documentation | `MOBILE_API.md`, `MOBILE_DEPLOYMENT.md`, `MOBILE_NOTIFICATIONS.md` |

Earlier pending quote-pruning, booking/payment hardening, background-queue jobs,
legacy-service configuration and October migrations are not newly implemented by
this task. No Flutter files, `api` branch code, dependency lockfiles or production
data were changed. No commit, push or deployment was performed.

### Live hosting inspection — 6 November 2026 (Pakistan time)

Read-only inspection used the owner's existing Mochahost/cPanel Chrome session.
No settings, deployments, scheduled jobs, customer records or backups were changed;
no support message was sent. Terminal checks read selected non-secret environment
and cached configuration fields, runtime/version identifiers, log-file sizes and a
bounded controller-source excerpt. No database connection or query was executed.
Do not store cPanel session URLs, credentials, full configuration, customer log
contents or database exports as evidence in this repository.

| Item | Observed live state | What this establishes |
| --- | --- | --- |
| Hosting account | Mochahost cPanel Hosting, Ultimate (`mochaUltimate`), primary domain `kainattravels.net`; active | Provider/product identified. Public terms name World Host Group US Inc.; confirm applicability to this account. |
| Server | `s1294.sgp1.mysecurecloudhost.com`; cPanel 136.0 build 51, Apache 2.4.69, MariaDB 10.6.28 | The hostname suggests Singapore, but country/region and backup locations still require provider confirmation. |
| Mobile deployment | `/home/wwwkaina/kt-mobile-api`, branch `mobile-backend`, commit `cc2e21d298f194d25fa745b8f9a781a39663aa14`; CLI PHP 8.2.35 | Current deployed revision identified. CLI runtime does not independently prove the web PHP version. |
| ERP deployment | `/home/wwwkaina/api.kainattravels.net`, branch `master`, commit `efb99dcc09a94adb815d947c8c4e1205fdc732e9` | This differs from the local `api` reference; do not assume those branches are interchangeable. |
| Mobile public certificate | `mobile-api.kainattravels.com`: AutoSSL Domain Validated, expires 14 December 2026; renewal enabled | Installed domain certificate confirmed. This alone does not establish every backend/provider connection's encryption. |
| Runtime configuration | `.env`: production, debug false; cache file present | Production flags observed; selected cached values were also checked below. |
| Application logging | Cached default `stack`, stack channel `single`, level `warning`; `laravel.log` exists | The active Laravel channel does not rotate logs by age. Any host-level rotation remains unverified. |
| Database connection | Environment and cached MySQL host both `localhost`; port 3306 | Local database host configured. No database contents, credentials, live TLS/session variables or stored-data encryption inspected. |
| Mobile services | Cached OTP driver `whatsapp`; push-enabled flag true; environment queue `sync`, cache `file` | WhatsApp and push are enabled in configuration. This is not an end-to-end provider delivery test or a Firebase product inventory. |
| New retention rollout | `PruneMobilePersonalData.php` and the 6 November first-login migration file absent; retention enable/backup values unset and cached enable value absent | Prepared retention work has not reached this deployment. No claim of active 90-day cleanup can be made. |
| cPanel cron | One-minute ERP `artisan schedule:run`; separate one-minute mobile `artisan mobile:notifications` | No mobile API `artisan schedule:run` entry appears in the cPanel list. Other host-managed scheduling was not inspected. |
| Notification cron output | Appends to `storage/logs/mobile-notifications-cron.log`; file exists | This separate log also needs an explicit rotation/expiry owner. |
| Raw access logs | Archiving enabled, statistics processing every 24 hours; monthly previous-archive removal checked; custom retention unchecked; summary says server default is indefinite | Conflicting UI signals prevent claiming a verified maximum retention. October and November mobile API archives are listed. No logs were downloaded or read. |
| Standard cPanel backups | Account Backups says no automatically generated backups currently available | This standard feature exposes no automatic backups. It does not prove the absence of separate host/JetBackup copies. |
| JetBackup 5 | Menu available, but panel stayed blank during inspection and retry | Backup frequency, oldest copy, expiry, encryption and location remain unverified. No backup/restore/download initiated. |

The actual cPanel jobs observed were:

```text
* * * * * /usr/local/bin/php /home/wwwkaina/api.kainattravels.net/artisan schedule:run>> /dev/null 2>&1
* * * * * cd /home/wwwkaina/kt-mobile-api && /usr/local/bin/php artisan mobile:notifications >> storage/logs/mobile-notifications-cron.log 2>&1
```

The live ERP notification controller SHA-256 is
`5e038e410a5252f6e7d9aa6533d9c7b51e2018d6c9f4637b050f0c84f4de329a`.
It differs from the local `api` controller, so the live source was checked directly.
Its `send()` selects recipients before the transaction, locks the company record,
and bulk-inserts notification rows using those selected account IDs without an
account-row lock/recheck. The deletion race therefore **is present in the live
writer**, not merely the reference branch. A separately approved ERP source change
and coordinated deployment remain necessary; no live edit was attempted.

The remaining Mochahost support request can now be narrowed to:

1. Confirm application/database and all backup countries/regions for this Ultimate
   account, its contracting entity, infrastructure subprocessors, applicable
   data-processing terms, and any use of hosted passenger data beyond providing
   hosting/support/security on our behalf.
2. The owner has confirmed that hosting backups expire within 30 days. Explain why
   JetBackup is blank and confirm backup schedule/scope, expiry mechanism and any
   separate manual/support copies outside that hosting-backup period. Describe
   deletion handling while hosting stays active and restoration responsibility for
   keeping deleted accounts from returning.
3. Resolve the Raw Access retention contradiction. State the effective maximum age
   and deletion mechanism for archived access logs, plus server/security/database/
   support/monitoring logs; identify recorded fields and any request bodies or
   authentication headers. Confirm whether host rotation covers the application's
   `laravel.log` and `mobile-notifications-cron.log`.
4. Confirm any proxy/CDN-to-origin and backup-transfer encryption, whether databases
   or backups are encrypted at rest, and any additional logging/monitoring services
   not visible to this cPanel account. Identify which controls are host-managed.

Mochahost's [privacy policy](https://terms.mochahost.com/privacy-policy) describes
up to 30 days of backup retention **after hosting cancellation/termination**. It
does not establish a 30-day rolling schedule or passenger-account erasure period
while this service remains active. Its [terms](https://terms.mochahost.com/) allow
offsite backups potentially in another country within the region. The 30-day
hosting-backup expiry recorded here comes from the owner's account-specific
confirmation, separately from that public cancellation policy.

### Owner-confirmed backup period and Play disclosure — 6 November 2026

The owner explicitly clarified: **"Hosting backups expire within 30 days."**
Accept this as the supplied account-specific backup period; it does not assert that
all passenger data, active booking/financial records, application logs, manual
exports or separate service-provider copies are deleted within 30 days. The blank
JetBackup panel did not independently establish the backup setting or other controls.

The live Play Console Data safety form was inspected after this clarification. Its
account-deletion section has a deletion URL field and requires the linked page to
describe deleted/retained categories and additional retention. There is no numeric
backup-retention field in that section. The saved deletion URL remains
`https://www.kainattravels.com/delete-account`. Do not select the optional "user data
is automatically deleted within 90 days" answer on the strength of backup expiry:
that is a different claim about user data.

Prepared wording for the public privacy and account-deletion pages:

> Hosting backups expire within 30 days. After data is removed from our live hosted
> systems, copies may remain in those backups until they expire. This backup period
> is separate from the retention of records we continue to keep and data held by
> other service providers, as described in this policy.

Publish this alongside the approved specific retained-data periods and exceptions.
The current public deletion page still states a full financial year for ticket and
payment records and has no backup-period paragraph. Neither the public pages nor
the Play Console declaration were changed or submitted by this documentation update.
The live cleanup rollout, ERP writer fix and remaining disclosure facts still apply.

Separate application/business follow-up remains: deploy and verify the mobile
cleanup work and proper scheduler, fix the deployed ERP writer on an approved
source branch, review Firebase installation deletion and enabled products, obtain
SAR Zone/payment-provider retention commitments, and approve the financial/CNIC
retention policy. The hosting dashboard also displayed an unpaid-invoice notice;
the owner should review billing separately to maintain service continuity.
