# Mobile App ERP module

The module belongs to `hhhashhhim/KTBUSCLONE`. Backend code is on `api`;
Vue screens, navigation, and role-permission presentation are on `mix`.
It adds an ERP workspace for the existing passenger mobile application.

## Submodules

| Submodule | Supported controls and monitoring |
| --- | --- |
| Overview | Scoped passenger, booking, active-seat and confirmed-sale counts; pending payments and payment exceptions. |
| App users | Find accounts, inspect verification and recent bookings, mask personal information, block/reactivate or blacklist accounts, set seat and OTP limits, require verified password resets, revoke passenger sessions. |
| Bookings and seats | Find mobile invoices, inspect original and current ticket history, cancel eligible selected seats through the existing cancellation service. |
| Payment monitoring | Inspect transaction references, payment status, environment, dates, and paid/unconfirmed exceptions. |
| Content and fleet | Manage the existing mobile fleet-media catalog, ordering, bus-class assignment, and visibility. |
| Reports | Daily sales, route performance, passenger activity, cancellations/refund accounting, and payment records; filtered CSV exports. |
| App settings | Version policy, mandatory updates, maintenance message, store links, public support contacts, supported wallet setting, app access switches, and default seat/OTP/authentication limits. |
| Notifications | Preview and send passenger announcements; view inbox/read counts and push acceptance/failure history. |
| Activity and audit | Staff identity, time, reason, affected record, and recorded changes for administration actions and exports. |

There is no Pay at Branch submodule. Existing app payment preferences and
historical payment records are preserved. Gateway credentials, payment
confirmation, money transfers, and payment retries are not exposed as staff
overrides. App installations, crashes, search conversion, promotions, and
notification delivery are not fabricated from booking data.

## Database and runtime dependencies

The ERP must connect to the same company data used by the mobile API. The
existing mobile backend owns these schemas:

- `passenger_accounts` and `customers`
- `invoices.passenger_account_id` and the existing `tickets` records
- `mobile_payments`, including its `environment` migration
- `mobile_app_configs`
- `mobile_fleet_media`
- `personal_access_tokens` for passenger-session management

The module discovers capabilities and returns an unavailable response for a
missing dependency. It does not recreate or overwrite the passenger API tables.
Deleted passenger accounts do not hide their retained invoices, tickets, or
payments. Ownership of an app booking is established by
`invoices.passenger_account_id`, never by matching customer phone numbers.

Passenger enforcement is implemented on `mobile-backend` by
`EnforceMobilePassengerControls`, `MobilePassengerControls`, the authentication
rate limiters, and the booking/OTP services. Deploy those changes to the runtime
serving `/api/mobile/v1/*`. A shared database alone cannot enforce new controls
on an older passenger API. The ERP API's legacy suspension middleware remains
compatible, but is not the full controls implementation. Payment checkout and
return callbacks stay outside the passenger-control middleware.

Changes in `mobile_app_configs` take effect when the passenger API reads the
shared configuration. With no company override, the mobile API can use its own
environment defaults; verify those defaults before creating an override in the
ERP. Mobile maintenance/version settings follow the existing mobile app
contract. They do not replace server maintenance or payment reconciliation.

Use the existing shared, lock-capable cache for the ERP and mobile API when
they can modify the same departure. Seat cancellation uses the same
`stayLock:<schedule_id>` lock as mobile booking and payment settlement.

## Installation

Deploy the reviewed `api` application changes and corresponding `mix` source
and frontend build through the existing ERP release process. Preserve current
environment files, uploaded media, persistent storage, and unrelated pending
work. This module does not change branches, merge histories, or deploy itself.

In the backend application, after the existing mobile schema is installed:

```sh
php artisan mobile-app:install --force
php artisan route:clear
```

The installer applies only:

- `2026_10_20_000001_create_mobile_app_admin_tables.php`
- `2026_10_20_000002_add_mobile_app_permissions.php`
- `2026_10_20_000003_add_mobile_media_placements.php` (when the mobile fleet table exists)
- `2026_10_20_000004_add_mobile_user_controls.php`
- `2026_10_20_000006_create_mobile_app_monitoring.php`
- `2026_10_20_000008_create_mobile_notifications.php`
- `2026_10_20_000009_add_mobile_notification_permissions.php`

It creates the administration audit and account-control tables, makes the
module available to companies, grants company admin roles, and adds
default-denied privileges for other staff roles. Existing unrelated permissions
are preserved. The installer is safe to rerun and does not run unrelated
pending ERP migrations.

Build the `mix` frontend using its existing Laravel Mix workflow. If its
compiled assets already contain uncommitted work, preserve that work and review
the resulting build before replacing deployment assets.

## Staff privileges

Under **Roles > Permissions > Mobile App**, enable the parent module, the
required submodules, and the relevant actions. The API enforces all three
levels together with company availability. Hiding a navigation item is not
the authorization boundary.

Action/scope privileges include:

- Dashboard viewing.
- Passenger viewing, account controls, session revocation, and sensitive contact
  viewing; separate blacklist, passenger-limit, and forced-password-reset privileges.
- Booking viewing, reserved-seat removal, and confirmed-seat cancellation.
- Payment viewing.
- Content viewing and management.
- Report viewing and export.
- Settings viewing and management; separate app-controls management.
- Audit viewing.
- Access to all company terminals.

Assigned route limits still apply when all-terminal access is granted.
Passenger accounts do not belong to a branch; restricted staff can only view
accounts linked to bookings in their permitted terminals/routes. An active,
company-matched Admin can manage accounts and revoke sessions for passengers
already visible in that scope. Other roles require all company terminals and
all routes for these actions. Every action still requires company module
availability, passenger-view permission, its dedicated privilege, and a reason.
Company Admin can also manage app settings, app controls, and fleet images
regardless of booking route assignments; other roles require full terminal/route
scope for configuration changes. Admin can read company configuration audits
and account audits for visible passengers. Explicit terminal/route audit filters
exclude entries without the corresponding terminal/route. Passenger lists,
tickets, invoices, and reports retain their existing visibility scope.
All-route access accepts the existing `all`
assignment or explicit selection of every current, non-deleted company route
in the ERP Users form. Explicit coverage is checked again on each request:
adding an unassigned route disables scope-dependent changes until it is assigned.
This does not widen ticket or invoice access to unassigned historical routes.
The module explains scope-related restrictions even when the role grants the
corresponding action.

Sensitive passenger details also require passenger-view permission. Passwords,
password-reset material, OTP digests, bearer tokens, and merchant credentials
are never part of module responses.

## Seat cancellation and financial interpretation

Selected tickets must belong to one visible mobile invoice. Cancellation
requires a reason and a refund percentage. Confirmed tickets require the
additional confirmed-seat privilege, even with a zero refund.

The operation:

1. Acquires the shared departure lock.
2. Locks the payment before the selected tickets, matching mobile settlement.
3. Rechecks invoice ownership, current ticket states, departure time and closing
   assignments.
4. Uses `TicketCancellationService` for ticket history, loyalty adjustments,
   ELT cleanup and soft deletion.
5. Records the staff action and refund-accounting amount in the module audit.

Pending or review-required gateway payments must be resolved first. Departed
or closing-assigned tickets use the existing ERP workflow. When terminal
cancellation approval is enabled, confirmed tickets must follow that workflow.
The current passenger API presenter imposes two compatibility limits:

- Online invoices require all remaining active seats to be selected; partial
  cancellation would otherwise invalidate the surviving seats' mobile QR.
- Historical invoices without a mobile payment record must retain at least one
  active seat; removing the last one would otherwise break passenger booking-history
  pagination. This returns `409` without changing tickets or the invoice.

Booking detail exposes `seat_removal_policy` with these restrictions. A separate
passenger API presenter update is required to lift them. This module does not
hide cancelled invoices or change passenger API source on another branch.
Within these constraints, journey-segment tickets can be cancelled individually
without cancelling another segment using the same physical seat.

`Ticket.refund_percentage` and `refund_amount` retain refund values.
`BookingCancel.percentage` follows the existing ERP report convention and
stores the deduction percentage (`100 - refund_percentage`).
This records refund accounting; it does **not** send money through a gateway
or prove a refund settlement.

Superseded `type = reschedule` ticket rows remain in booking detail history but
are excluded from current seat, sales, cancellation and payment-exception
totals.

## API contract

All routes below use the existing ERP Sanctum staff authentication and the
prefix `/api/web/v1/mobile-app`. JSON responses are
`{"status":true,"data":...}`. Validation uses the existing Laravel `422`
message/errors response; denied actions return `403`, invisible records `404`,
stale state or unsafe cancellation `409`, and missing schema `503`.

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/options` | Effective permissions, schema capabilities, allowed terminals/routes. |
| GET | `/overview` | Scoped metrics and authorized recent records. |
| GET | `/users` | Paginated, masked passenger list. |
| GET | `/users/{id}` | Passenger, authorized recent bookings, and authorized session metadata. |
| PUT | `/users/{id}/status` | `{status, expected_status, reason}`; active/blocked, with stale-state protection. |
| POST | `/users/{id}/revoke-sessions` | `{reason}`; deletes only that passenger's tokens. |
| GET | `/bookings` | Paginated mobile invoice projection. |
| GET | `/bookings/{id}` | Invoice details, seat history, and authorized payment details. |
| POST | `/bookings/{id}/remove-seats` | `{ticket_ids:[], refund_percentage, reason}`. |
| GET | `/payments` | Paginated safe payment fields; no checkout secrets. |
| GET | `/reports` | `report=sales\|routes\|passengers\|cancellations\|payments`. |
| GET | `/reports/export` | CSV with the same filters and a separate export privilege. |
| GET/PUT | `/settings` | Read settings/revision; write `{settings, revision, reason}`. |
| GET/POST | `/content` | Paginated fleet media; create supported media fields plus `reason`. |
| PUT/DELETE | `/content/{id}` | Update/archive media with `revision` and `reason`. |
| GET | `/activity` | Paginated, scoped module audit records. |

List responses contain `items` and
`pagination: {current_page,last_page,total,per_page}`. Supported common filters
are `from`, `to`, `search`, `status`, `terminal_id`, `route_id`, `page`, and
`per_page` (maximum 100). Booking/payment/report filters additionally accept
`environment=live|sandbox|all`; default `live` includes historical records
without an environment and excludes known sandbox payments. Account and
booking details remain addressable by authorized ID across environments.

Dates refer to booking creation, or payment creation for payment reports.
Travel dates remain separate columns. Confirmed sales are active confirmed
ticket fares less direct discounts; recorded refund liabilities are separate,
and payment amounts retain their recorded currency. These are operational
reports, not a bank-settlement reconciliation feed.

CSV exports require both dates, at most 366 days, and no more than 10,000 result
rows. Spreadsheet-formula prefixes in user-provided text are escaped.

Settings and content edits require a reason and the revision returned by the
latest GET. Conflicting edits return `409`. Minimum versions must not exceed
latest versions. Enabling a forced update or increasing the minimum version
requires valid HTTPS store links for both platforms. Only the supported
wallet feature is editable; payment methods and unsupported/derived feature
flags remain read-only.

## Validation

Run the module suites against their isolated in-memory fixtures:

```sh
php -d error_reporting=24575 vendor/bin/phpunit --filter MobileApp
```

The tests do not migrate or mutate the configured application database.
Before production use, verify the deployed staff routes, role grants,
shared mobile database, passenger suspension middleware, live configuration
response, and shared schedule locks using the normal staging process.

## Local activation verification — 20 October 2026

The existing local ERP serves the `mix` build at
`http://127.0.0.1:8085/mobile-app` and uses the `api` worktree on port 8086.
The administration migrations and image-placement migration are installed in the verified localhost database.
The frontend bundle and manifest were rebuilt; earlier assets and permission
values were backed up under `/private/tmp` before activation. No production
release, commit, or push was performed.

Read-only checks against the local MySQL data verified all eight submodules,
five report variants, and passenger/booking details. The dashboard query now
starts with scoped mobile invoices and uses existing ticket indexes, avoiding
aggregation of unrelated ERP ticket history. In the local verification, the
overview improved from approximately 44 seconds to 45 milliseconds.


## Image uploads and app placement

Open **Mobile App → Fleet & Content → Add image**. Choose **Upload picture**, select
a JPG, PNG or WebP file, review its preview, and select one or more app placements:

- Home banner: top of the home screen, above search.
- Home photo carousel: existing scrolling fleet photos on Home.
- Bus & Seat Guide: the categorized fleet gallery.
- Home promotions: informational images in Home's Travel highlights section.

Choose the category and optional bus class, display order (lower numbers first),
publish/hide state, and audit reason, then save. Images assigned to hidden,
inactive, deleted, or foreign-company bus classes are not shown to passengers.
The existing Manage app content privilege controls uploads, replacement,
placement, publication and removal. Companywide terminal/route access is still
required; this feature does not grant or expand staff access.

POST `/api/web/v1/mobile-app/content` accepts multipart fields: `image` (file),
`title`, `media_type`, optional `bus_class_id`, `placements[]`, `sort_order`,
`is_active` (`0`/`1`) and `reason`. For replacement, POST to `/content/{id}` with
`_method=PUT` and the current `revision`. JSON saves still accept `image_url`;
never supply both `image` and `image_url`. Placements must contain 1–4 distinct
supported values: `home_banner`, `home_carousel`, `bus_guide`, `home_promotion`.
Old records/default clients retain `home_carousel` and `bus_guide`. Returned
records include `placements` and `source` (`upload`/`url`).

Uploads validate actual image content/MIME, maximum 5 MiB, and dimensions of
32–4096 pixels on each side. SVG and other formats are rejected. Files use
random names beneath `storage/app/public/mobile-app/{company_id}`. Failed saves
remove their newly uploaded file. Replaced/soft-deleted files remain available
for audit/recovery; they leave the passenger catalog but are not private files.
Only upload images intended for public display. Set PHP/web-server request
limits above 5 MiB (for example, `upload_max_filesize=8M`, `post_max_size=10M`).

For local-disk storage, run `php artisan storage:link`. Set `APP_URL` to the
public HTTPS staff API origin before production uploads; the stored URL must
be reachable from phones. For separate hosts, expose the staff public storage
URL or configure that disk's public URL/CDN. Local development permits HTTP;
physical phones require a reachable LAN hostname/IP rather than 127.0.0.1.

Deploy the companion `mobile-backend` gallery reader and `mobile-frontend`
release for new placements. The reader remains compatible before the migration
and excludes banner/promotion-only content for older app requests. New Flutter
clients request `fleet?include_placements=1`, show published images first in
API order, and retain bundled photos in the carousel/guide for offline use.
Pull down on Home or the guide to refresh photos. These are display images;
promotional artwork does not change booking prices, discounts or eligibility.

## Passenger and app controls

Use **Mobile App > App Users > View user** for status, individual limits, and
password recovery. Use **App Settings > App Controls** for company defaults.
Each mutation requires its dedicated privilege and an audit reason. Company
Admin can control passengers already visible in their assigned scope; other
roles require all company terminals/routes. Company Admin configuration access
is independent of booking route assignments. Limits and policies use revision
checks to reject stale edits.

- **Blocked** stops the existing account. **Blacklisted** also stores SHA-256
  hashes of its normalized phone and CNIC, preventing reuse for registration
  and restricting matching existing accounts. Removing a blacklist requires
  the blacklist privilege; other accounts' entries remain intact.
- **Seat allowances** count seats reserved during the Pakistan calendar day
  and month, not the departure date. Cancelled reservations still count. The
  immutable ledger is written in the booking transaction while the passenger
  account row is locked. Legacy invoices without ledger rows count their
  original tickets, including soft-deleted tickets. Hard-deleted legacy ticket
  history cannot be reconstructed. Quotes alone do not consume allowance.
- Blank individual limits inherit app defaults. Blank app seat limits mean
  unlimited; zero means no new usage. Individual limits override defaults.
  Global and individual booking/OTP switches must both be enabled.
- **OTP budgets** cover signup, resend, and Forgot password together across
  IPs/devices and Pakistani phone formats. Minute/hour plus 24-hour/30-day
  windows start with the first attempt; these are not calendar periods.
  Failed/invalid requests also count. Code checks, sign-in, and final password
  resets have separate attempt budgets. Fixed IP ceilings remain enforced.
  Use persistent shared cache across all passenger API replicas; clearing it
  resets authentication budgets. Changes apply to new requests.
- **Require password reset** immediately revokes sessions and outstanding OTP
  and reset tokens. No password or OTP is disclosed to staff, and this action
  sends no message. The passenger completes the existing Forgot password flow;
  successful verified recovery clears the flag atomically. Blocked/blacklisted
  accounts and disabled OTP controls still prevent recovery.

The mobile-backend migration
`2026_10_20_000005_create_mobile_passenger_control_policies.php` creates the same
shared policy/control/blacklist/usage schema without ERP role dependencies.
Both migrations are idempotent and retain restriction/history tables on
rollback. Install the ERP permissions migration even if mobile schema is
already installed. No Flutter contract changes are required for these controls;
the current app displays API errors and provides verified password recovery.

## User-controls implementation files

All paths below are relative to their existing KTBUSCLONE checkout. These work
units remain separate; no branch merge is needed to review them.

| Branch | Changed files for user/app controls |
| --- | --- |
| `api` | `app/Http/Controllers/Api/MobileAppAdminController.php`, `app/Http/Controllers/Api/MobileAppSettingsController.php`, `app/Services/MobileAppAdminData.php`, `app/Services/MobileAppControlData.php`, `app/Support/MobileAppPermissions.php`, `app/Console/Commands/InstallMobileApp.php`, `routes/api/mobileApp.php`, `database/migrations/2026_10_20_000004_add_mobile_user_controls.php` |
| `api` checks/docs | `tests/Feature/MobileAppAdminTest.php`, `tests/Feature/MobileAppSettingsTest.php`, `tests/Feature/MobileAppAccessTest.php`, `docs/mobile-app-installation.md` |
| `mix` | `resources/js/pages/mobileApp/Passengers.vue`, `resources/js/pages/mobileApp/PassengerLimits.vue`, `resources/js/pages/mobileApp/Settings.vue`, `resources/js/pages/mobileApp/AppControls.vue`, `resources/js/pages/mobileApp/mobileApp.css`, `tests/frontend/mobile-app-admin.test.cjs`, `public/js/app.js`, `public/mix-manifest.json` |
| `mobile-backend` | `app/Services/Mobile/MobilePassengerControls.php`, `app/Http/Middleware/EnforceMobilePassengerControls.php`, `app/Services/Mobile/MobileBookingService.php`, `app/Services/Mobile/MobileOtpService.php`, `app/Http/Controllers/Mobile/MobileAuthController.php`, `app/Providers/RouteServiceProvider.php`, `routes/api/mobile.php`, `database/migrations/2026_10_20_000005_create_mobile_passenger_control_policies.php` |
| `mobile-backend` checks/docs | `tests/Feature/MobileOtpTest.php`, `tests/Feature/MobileAuthRateLimitTest.php`, `tests/Feature/MobileSchedulingRulesTest.php`, `MOBILE_API.md`, `MOBILE_DEPLOYMENT.md` |


## Admin configuration access fix (20 October 2026)

Company Admin can edit settings, app controls and images without changing
booking route assignments. Company module availability and dedicated
view/action checks remain enforced. Passenger lists and booking records retain
their existing terminal/route scope. Configuration audit records are visible to
company Admin; other companies' records remain excluded.

Files changed for this fix in `hhhashhhim/KTBUSCLONE`:

| Branch | Files | Change |
| --- | --- | --- |
| `api` | `app/Support/MobileAppScope.php`, `app/Services/MobileAppAdminData.php` | Identify an active, company-matched Admin and separate configuration authority from passenger scope. |
| `api` | `app/Http/Controllers/Api/MobileAppSettingsController.php`, `app/Http/Controllers/Api/MobileAppAdminController.php` | Enable authorized settings/control/content mutations and options; scope notices and configuration audits. |
| `api` | `app/Support/MobileAppPermissions.php` | Describe configuration access accurately in detailed role privileges. |
| `api` | `tests/Feature/MobileAppSettingsTest.php`, `tests/Feature/MobileAppAdminTest.php` | Cover settings/policy saves, image upload, company boundaries, disabled accounts, retained passenger restrictions, and scoped/full Admin audits. |
| `api` | `docs/mobile-app-installation.md` | Document access boundaries and validation. |
| `mix` | `resources/js/pages/mobileApp/MobileAppPage.vue`, `resources/js/pages/roles/Permissions.vue` | Display relevant scope notices and accurate Admin help. |
| `mix` | `tests/frontend/mobile-app-admin.test.cjs` | Verify screen-specific notices. |
| `mix` | `public/js/app.js`, `public/mix-manifest.json` | Rebuilt local ERP bundle. |

Validation completed: Mobile App feature tests pass (61 tests, 652 assertions),
frontend component/behavior checks pass, and the development bundle compiles.
The local ERP UI confirms enabled daily/monthly seat and OTP fields, and opens
Add image with Choose picture and placement controls. No real settings, images,
route assignments or passenger accounts were changed during browser verification.
No new schema migration, passenger API or Flutter change is required for this
permission fix. The local ERP is updated; other environments need deployment of
the API changes and rebuilt view bundle. Nothing was committed or pushed.

## App user CNIC and reports (20 October 2026)

App Users now has a dedicated CNIC column, formatted as `12345-1234567-1`.
The existing `mobile-app-view-sensitive-users` privilege controls full CNIC,
mobile and email visibility in lists, details and user report exports. Its
label now explicitly includes CNIC. Other roles retain masked values. CNIC
search accepts digits or hyphenated input only for staff with that privilege
and View app users, and never expands the permitted account scope.

Use **App Users > User reports**, or **Reports > Report > App user reports**.
The five new values accepted by the existing `GET /api/web/v1/mobile-app/reports`
and `/reports/export` endpoints are:

| `report` | Purpose | Date filter |
| --- | --- | --- |
| `user_registrations` | Daily registrations and current verified/unverified/blocked/blacklisted counts | Account registration |
| `user_verification` | User directory, CNIC, contact details and current verification | Account registration |
| `user_restrictions` | Currently blocked/blacklisted users | Last account-control update, not historical restriction-event date |
| `user_bookings` | Bookings, seats, cancellations and active confirmed sales per user; totals include repeat and zero-booking users | Booking creation |
| `users_without_bookings` | Accounts with no visible booking in the selected period/environment | Booking creation; account must exist before the period ends |

All five require both `mobile-app-view-reports` and `mobile-app-view-users`.
Exports additionally require `mobile-app-export-reports`. Existing company,
terminal and route restrictions apply. Users without any booking history are
visible only with companywide passenger scope; restricted staff can report
only accounts already linked to bookings in their permitted scope.

Filters include `search`, `status` (`active`, `blocked`, `blacklisted`),
`verification` (`verified`, `unverified`), `from`, `to`, `terminal_id`,
`route_id`, `page` and `per_page`. `environment` applies to the two booking
reports only. User search/status filters do not become booking-status filters.
The registration and verification reports use current verification/status,
not historical snapshots. No-booking results are not evidence of inactivity:
cancelled/pending bookings count, and only the selected period, payment
environment and permitted scope are examined.

Responses keep the existing `{items, pagination, columns, summary, note}`
structure. Directory rows expose only reportable identity fields and follow
the same masking as App Users. CSV uses the same presentation/masking,
escapes spreadsheet formula prefixes, retains the one-year/10,000-row limits,
and records an audit entry. Summaries cover all matching rows, not just the
current page. The UI keeps exports and pagination tied to the last applied
filters and describes the date meaning for each report.

Files changed in `hhhashhhim/KTBUSCLONE` for this addition:

| Branch | Files |
| --- | --- |
| `api` | `app/Services/MobileAppUserReports.php`, `app/Services/MobileAppAdminData.php`, `app/Http/Controllers/Api/MobileAppAdminController.php`, `app/Support/MobileAppPermissions.php`, `tests/Feature/MobileAppAdminTest.php`, `docs/mobile-app-installation.md` |
| `mix` | `resources/js/pages/mobileApp/Passengers.vue`, `resources/js/pages/mobileApp/Reports.vue`, `resources/js/pages/mobileApp/mobileApp.js`, `tests/frontend/mobile-app-admin.test.cjs`, `public/js/app.js`, `public/mix-manifest.json` |

Validation: 69 Mobile App feature tests / 763 assertions pass on isolated
SQLite; all 14 Vue components compile and frontend behavior checks pass;
`npm run development` succeeds. Local ERP browser checks confirm the full CNIC,
the User reports link, all five report views, empty states, registration counts,
and booking-period totals against the existing local records. No account,
permission, booking or policy data was modified during browser verification.

This uses the existing schema and requires no passenger API or Flutter change.
The local ERP is updated. Other environments need the `api` files and rebuilt
`mix` bundle deployed. Nothing was committed or pushed.


## Admin passenger account controls fix (20 October 2026)

Company Admin can open **App Users > View user > Account controls** for a
passenger already visible in their assigned routes/terminals. The status menu
includes Active, Blocked and Blacklisted. The same dialog exposes seat and OTP
limits, session revocation and password recovery when their action privileges
are available. A reason is required before saving. The restricted-role dialog
now explains why status and session controls are unavailable.

The API checks passenger visibility before every account mutation, even for
Admin. Accounts linked only to hidden routes, unbooked accounts outside the
staff member's passenger scope, and other companies remain inaccessible. The
Admin exception does not widen passenger lists, reports, bookings or invoices.
Account audit entries for visible passengers can be reviewed in Activity &
Audit; explicit terminal/route filters keep their existing meaning.

Files changed in `hhhashhhim/KTBUSCLONE` for this fix:

| Branch | Files |
| --- | --- |
| `api` | `app/Services/MobileAppAdminData.php`, `app/Http/Controllers/Api/MobileAppAdminController.php`, `app/Support/MobileAppPermissions.php`, `tests/Feature/MobileAppAdminTest.php`, `docs/mobile-app-installation.md` |
| `mix` | `resources/js/pages/mobileApp/Passengers.vue`, `resources/js/pages/roles/Permissions.vue`, `public/js/app.js`, `public/mix-manifest.json` |

Validation: Mobile App feature tests pass (72 tests, 817 assertions) on isolated
SQLite. All 14 Vue components and frontend behavior checks pass, and
`npm run development` succeeds. Local browser verification confirms the Admin
dialog shows the Active/Blocked/Blacklisted selector, editable seat and OTP
limits, and password recovery. No real passenger status, credentials, limits,
bookings or route assignments were changed during browser verification.

This permission fix needs no schema migration or passenger API/Flutter change.
It is available in the local ERP; other environments require the `api` changes
and rebuilt `mix` bundle. Passenger-side enforcement of previously added
controls still requires the corresponding mobile backend code to be deployed
against the same company/database. Nothing was committed or pushed.

## Routes, schedules and login monitoring (20 October 2026)

**Mobile App > Routes & Schedules** is a read-only page with travel date, online
terminal, route search and status filters. Route access is separate from the
availability of each departure. The route list counts each schedule/run once;
the detail view lists its city-to-city departures with a short explanation.
Existing `mobile-app-view-bookings` access controls both views. Assigned routes,
company boundaries and online-terminal scope remain enforced, including Admin
route assignments. No scheduling or booking permissions are changed here.

The projection matches `mobile-backend`'s `MobileSchedulePolicy`: active routes,
schedules/cities/terminals; route online-terminal mappings; positive schedule
visibility; the inverted `online_visibilty` city-pair flag; run-date cancellations;
exclusive advance-booking dates; and opening times with terminal offsets when
the mobile service user checks booking minutes. Deleted rules are excluded.
Allowed means app search visibility, not a guarantee of seats, a valid fare,
or passenger eligibility. Maintenance and paused-booking notices are shown
separately. Dates are current/future PKT dates, capped at 60 days.

The mobile backend reports its configured company/terminal/service user to the
shared `mobile_app_runtime` table when app configuration is fetched or a login
succeeds. Without that report, the ERP lets staff preview an explicitly chosen
online terminal; it does not claim the live app configuration is confirmed.
Opening-time checks that need an unknown service user show **Needs setup**.
No terminal is guessed from its name or from staff booking assignments.

**Overview > App logins** shows a compact 7/30-day chart, period logins, distinct
people, first recorded logins, and the total recorded since tracking started.
Both successful password logins and automatic signup sign-ins count; failed or
blocked requests and ordinary app opens do not. Tokens can be revoked or
replaced without erasing this history. “First recorded” is not necessarily a
newly created passenger: earlier login history cannot be reconstructed.
Events store only company/account identifiers, event kind, first-login flag
and UTC time. Graph days are bucketed in Pakistan time. Missing tracking and
days before tracking began show unavailable values, never fabricated zeros.
Aggregates follow the viewer's current passenger scope and the selected
terminal; payment environment and booking-date filters do not redefine logins.

**New downloads** explicitly shows that store reporting is not connected, with
links to Google Play Console and App Store Connect. Neither accounts, login
events nor app-config requests are used as a download count. Official references:
[Google Play statistics](https://support.google.com/googleplay/android-developer/answer/139628)
and [App Store Analytics](https://developer.apple.com/app-store-connect/analytics/).

New ERP endpoints (staff Sanctum authentication, standard status/data envelope):

- `GET /api/web/v1/mobile-app/availability/routes`: required `date`; optional
  `mobile_terminal_id`, `search`, `status`, `page`. Returns context, summary,
  route rows and pagination (25 rows).
- `GET /api/web/v1/mobile-app/availability/schedules`: also requires `route_id`;
  returns city-pair departure rows with `allowed`, `not_allowed`, `not_open` or
  `needs_setup` and a reason. A terminal override is a preview, not a settings edit.
- `GET /api/web/v1/mobile-app/engagement`: requires dashboard privilege; `days`
  is 7 or 30, with optional permitted `terminal_id`/`route_id`. Returns aggregate
  metrics, zero-filled tracked days, null pre-tracking days and source availability.

Install `2026_10_20_000006_create_mobile_app_monitoring.php` once on the shared
database. `mobile-app:install` includes it; the mobile backend has the same
idempotent migration name. Deploy the ERP `api` changes and compiled `mix`
bundle, plus the mobile backend recorder/controller changes. No Flutter update
is needed for login recording or runtime reporting. The mobile backend must
use the intended company, online terminal, booking service user and shared
ERP database. Google/Apple store reporting remains an external dependency.

Files changed in `hhhashhhim/KTBUSCLONE` for this addition:

| Branch | Files |
| --- | --- |
| `api` | `app/Services/MobileAppAvailability.php`, `app/Services/MobileAppEngagement.php`, `app/Http/Controllers/Api/MobileAppAdminController.php`, `app/Support/MobileAppPermissions.php`, `routes/api/mobileApp.php`, `app/Console/Commands/InstallMobileApp.php`, `database/migrations/2026_10_20_000006_create_mobile_app_monitoring.php`, `tests/Feature/MobileAppAdminTest.php`, `tests/Feature/MobileAppAccessTest.php`, this guide |
| `mix` | `resources/js/pages/mobileApp/Availability.vue`, `LoginActivity.vue`, `Overview.vue`, `MobileAppPage.vue`, `mobileApp.js`, `mobileApp.css` (all under that directory), `resources/js/pages/roles/Permissions.vue`, `tests/frontend/mobile-app-admin.test.cjs`, `public/js/app.js`, `public/mix-manifest.json` |
| `mobile-backend` | `app/Services/Mobile/MobileAppTelemetry.php`, `app/Http/Controllers/Mobile/MobileAuthController.php`, `app/Http/Controllers/Mobile/MobileAppController.php`, `database/migrations/2026_10_20_000006_create_mobile_app_monitoring.php`, `tests/Feature/MobileOtpTest.php`, `MOBILE_API.md`, `MOBILE_DEPLOYMENT.md` |

Validation: ERP Mobile App tests pass (80 tests, 911 assertions); mobile OTP,
authentication rate-limit and schedule-policy tests pass (183 tests, 1,529
assertions). All 16 Mobile App Vue components compile; behavior checks and
`npm run development` pass. Only the monitoring migration was applied to the
verified local ERP database. No historical events were fabricated, no passenger
was signed in for testing, and no live server, account restrictions or scheduling
permissions were changed. Nothing was committed or pushed.


## Passenger announcements

Open **Mobile App → Notifications**. Enter a title, message and internal audit
reason, then choose selected passenger IDs, passengers on a departure, or all
active app users. **Preview audience & message** shows the current recipient count;
**Send announcement** saves to those inboxes. The **Send update** shortcut in an
App Users list fills that passenger's ID. **Notify passengers** in Routes &
Schedules fills the departure ID (schedule-detail ID, not the schedule-master ID).

Announcements exclude blocked/blacklisted accounts. Selected passengers require
View app users and must all be visible in the staff member's terminal/route scope.
Trip audiences require View mobile bookings and include only active tickets within
that scope. Maximum 200 selected passengers or 10,000 recipients per send. An
empty or oversized audience is rejected instead of silently truncated.

Roles have separate **View notification history**, **Send passenger announcements**
and **Broadcast to all app users** privileges under the Notifications submodule.
Broadcast additionally requires company Admin or all company terminals/routes.
Limited staff see their own sends; company Admin/full-scope staff see company
history. Device tokens and server credentials never appear in ERP responses.

The API uses `/api/web/v1/mobile-app/notifications`:

| Method | Suffix | Contract |
| --- | --- | --- |
| GET | none | Paginated sent history plus worker heartbeat/configuration status |
| POST | `/preview` | `title`, `body`, `reason`, UUID `request_key`, `audience` (`selected`, `trip`, `all`), `passenger_ids` or `schedule_detail_id` |
| POST | `/send` | Same payload plus encrypted `preview_token` returned by preview |

Preview has no send side effect. Its proof binds the actor, message and recipient
IDs for 10 minutes. Changed content/audience requires another preview (409).
Retries retain the same request UUID and do not create duplicate campaigns/inboxes;
reusing it for a different payload is rejected. Successful sends and their audit
entry are one database transaction.

**App Settings → Passenger notifications** pauses/resumes the company feature.
Existing other feature settings are preserved. Backend migrations and worker must
be deployed for runtime effect. The Notifications page shows inboxes, authenticated
read count, FCM acceptance and failed devices separately. FCM acceptance does not
prove actual phone delivery; a missing/stale worker heartbeat is shown explicitly.

The inbox works without Firebase. Phone alerts require the mobile-backend service
account settings, the Flutter public Firebase app config, APNs configuration for
iOS, passenger OS permission and a valid app session. Follow the mobile-backend
`MOBILE_NOTIFICATIONS.md` and Flutter `docs/NOTIFICATIONS.md`. These source changes
do not apply production migrations, deploy the ERP/API, or send announcements.

## Individual loyalty-wallet adjustments (20 October 2026)

Open **Mobile App → App Users → View user → Wallet**. Authorized staff can set
the customer's existing loyalty-points balance to a nonnegative whole number
(including zero). The form previews the previous balance, new balance and
difference, and requires a reason. It changes the linked loyalty card's balance;
it does not create a PKR cash wallet or send money. The passenger app sees the
existing balance through its normal wallet refresh and booking validation.

**App Settings → Feature Availability → Wallet feature** must be enabled.
The shared company override takes precedence over the configured default, matching
App Settings and the current passenger API's wallet-enabled default.
The server rechecks this switch on every write, even if it changed after the
form was opened. Missing, expired, deleted, foreign-company or duplicate active
cards cannot be adjusted here. Assign/renew or correct the card under the
existing **Loyalty Card → Assign Card** screen.

The new `mobile-app-manage-wallet` privilege also requires View app users and
company Admin access or all company terminals/routes. Passenger visibility still
applies to Admin. Non-admin roles receive no adjustment privilege automatically.
Users with view permission can inspect eligible balances without editing them.

Authenticated staff endpoints, under `/api/web/v1/mobile-app`:

| Method | Endpoint | Contract |
| --- | --- | --- |
| GET | `/users/{id}/wallet` | `{status:true,data:{available,enabled,active,points_available,card_id,expires_at,revision,message}}`. Unavailable/disabled/unlinked states include an explanation and no editable balance. |
| PUT | `/users/{id}/wallet` | `{points_available,revision,reason}`; returns the updated wallet state. Points must be an integer from 0 to 2147483647; revision is the GET response's 64-character hash; trimmed reason is 3–1000 characters. |

Writes lock the company, passenger account, configuration and eligible card in a
transaction. The card lock coordinates with mobile booking redemption. A changed
balance/card revision returns `409` instead of overwriting a newer redemption or
adjustment. Replaying an already-applied request also conflicts. Validation/no
change is `422`, denied access `403`, invisible account `404`, and missing schema
`503`. The UI requires a reload after an uncertain or failed save.

`wallet_points_adjusted` appears in **Activity & Audit**, with actor, passenger,
customer/card IDs, reason, old/new points and difference. The balance and audit
commit together; audit failure rolls back the adjustment.

Deployment: include the `api` source changes and run only the new permission
migration, or rerun `php artisan mobile-app:install`. The specific migration is:

```bash
php artisan migrate --path=database/migrations/2026_10_20_000010_add_mobile_wallet_permission.php
```

Deploy the rebuilt `mix` assets alongside the API. Existing passenger/backend
wallet APIs and Flutter need no changes. No real customer balance is changed
during implementation or fixture verification.

Files for this feature:

| Branch | Files |
| --- | --- |
| `api` | `app/Http/Controllers/Api/MobileAppWalletController.php`, `app/Services/MobileAppWallet.php`, `app/Http/Controllers/Api/MobileAppAdminController.php`, `app/Support/MobileAppPermissions.php`, `routes/api/mobileApp.php`, `app/Console/Commands/InstallMobileApp.php`, `database/migrations/2026_10_20_000010_add_mobile_wallet_permission.php`, `tests/Feature/MobileAppWalletTest.php`, `tests/Feature/MobileAppAccessTest.php`, this document |
| `mix` | `resources/js/pages/mobileApp/PassengerWallet.vue`, `resources/js/pages/mobileApp/Passengers.vue`, `tests/frontend/mobile-app-admin.test.cjs`, rebuilt `public/js/app.js` and `public/mix-manifest.json` |

Verification commands:

```bash
php vendor/bin/phpunit --do-not-cache-result --filter MobileApp
node tests/frontend/mobile-app-admin.test.cjs
npm run production
```

Verification completed: 97 ERP Mobile App tests / 1083 assertions passed,
including 10 wallet tests. The frontend component/behavior suite and production
build passed. Browser checks covered the real local ERP's unlinked-card state,
plus isolated enabled/saved, disabled, view-only and stale-balance fixtures.
The new permission migration was applied only to the verified localhost preview
database. Production deployment/migration remains pending. No customer balances
were changed, and no commit or push was performed.
## Detailed role privileges (20 October 2026)

Open **Users → Roles → Permissions → Mobile App**. The module provides nine
submodule switches and 25 individual action/scope privileges:

| Area | Separate privileges |
| --- | --- |
| Overview | View dashboard |
| App users | View users; adjust wallet points; suspend/reactivate accounts; blacklist/release; passenger limits; require password reset; revoke sessions; view full CNIC/contact details |
| Bookings and seats | View bookings/availability; remove reserved seats; cancel confirmed seats |
| Payment monitoring | View mobile payments |
| Content and fleet | View content; manage content |
| Notifications | View history; send announcements; broadcast to all app users |
| Reports | View reports; export reports |
| App settings | View settings; manage app access/limits; manage public app settings |
| Activity and audit | View administration audit |
| Access scope | All company terminals, subject to assigned routes |

`MobileAppPermissions::definitions()` now publishes a `requires` array for each
action. `MobileAppScope::allows()` enforces these prerequisites against both the
company's enabled permissions and the role's enabled permissions, in addition
to the module and submodule gates. The `/mobile-app/options` response uses the
same rules, then applies the existing staff scope restrictions. The wallet
scope restriction is included in the App Users notice.

For example, wallet adjustment needs **View app users** as well as its dedicated
action; confirmed-seat cancellation needs both **View mobile bookings** and
**Remove reserved seats**; exports need **View mobile reports**; broadcasts need
notification history and sending. Audience-specific notification access,
companywide controls, assigned routes, passenger visibility, wallet availability,
and required audit reasons remain enforced by their endpoints.

`POST /api/web/v1/role/get` additionally returns:

- `mobile_app_company_permissions`: only the selected role company's Mobile App
  module, with missing capabilities defaulted to denied.
- `can_update_permissions`: whether the current staff member has the existing
  **Edit role** permission required by `role/update`.

The Roles editor displays dependencies and warns when a selected action is
inactive because the module, submodule, prerequisite, or company grant is
disabled. Choices are preserved without automatically granting prerequisites.
Details remain visible when the role's Mobile App module is disabled. Loading,
read-only, saving, failed-save, and retry states are explicit; duplicate saves
are blocked and failed saves retain the selections.

New/missing privileges remain denied for ordinary roles. The existing Admin
normalization remains subject to company access and endpoint scope rules.
This change does not assign privileges to existing staff or add unimplemented
profile-editing, passenger request-inbox, rescheduling, or gateway-refund actions.

Changed files for this work:

| Branch | Files |
| --- | --- |
| `api` | `app/Support/MobileAppPermissions.php`, `app/Support/MobileAppScope.php`, `app/Http/Controllers/RoleController.php`, `app/Http/Controllers/Api/MobileAppAdminController.php`, `tests/Feature/MobileAppRolePermissionsTest.php`, `docs/mobile-app-installation.md` |
| `mix` | `resources/js/pages/roles/Permissions.vue`, `tests/frontend/mobile-app-role-permissions.test.cjs`, `public/js/app.js`, `public/mix-manifest.json` |

Validation:

- Mobile App PHPUnit suite: **102 tests, 1,511 assertions passed**.
- Call Center regression suite, including shared Roles behavior:
  **76 tests, 1,075 assertions passed**.
- Both frontend Mobile App behavior suites passed, including Roles template
  compilation, dependency/company warnings, read-only access, load errors,
  duplicate saves, forbidden/validation/network errors, and retained selections.
- `npm run production` passed with existing Sass/Browserslist warnings.
- Browser verification inspected a real local role without saving it, then used
  an isolated component fixture for interactive privilege/save states.

Deploy the `api` changes with the rebuilt `mix` assets. No additional migration
or Flutter/mobile-backend change is required for this permissions refinement.
No changes were committed or pushed.
