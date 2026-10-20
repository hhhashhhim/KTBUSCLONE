# Mobile passenger notifications

Repository: `hhhashhhim/KTBUSCLONE`. Mobile runtime: `mobile-backend`.
ERP administration: `api`; ERP Vue screens: `mix`; Flutter: `mobile-frontend`.
This feature requires coordinated deployment to the existing shared company database.
No SMS, email, WhatsApp, or payment gateway operation is introduced.

## Behavior

A durable private inbox works without Firebase. The scheduler records committed
booking, payment, cancellation, seat, departure-time and refund-accounting changes.
It also records a reminder in the two hours before a confirmed departure, using
Pakistan time. Historical invoices are silently baselined on first inspection.
Sandbox mobile payments are excluded. A refund alert describes a record change,
not money paid back to a passenger.

The worker scans 500 historical invoices per run, prioritizes invoices created in
the last 24 hours, and separately scans today's/tomorrow's confirmed departures.
Changes to older records can take a complete history scan to appear. Departure
alerts use dates/times recorded on tickets; a schedule-master-only edit that does
not update tickets needs the ERP **Notify passengers** announcement workflow.
Re-running the worker does not duplicate inbox events or departure reminders.

ERP announcements write directly to inboxes after a preview, permission check and
explicit Send. The worker handles their optional phone delivery. Phone content is
always generic: no passenger, seat, route, payment or booking details on the lock
screen. Message data contains only the inbox ID, which the passenger API checks
against the current company/account when opened.

## Install and enable

Use the normal reviewed deployment procedure and existing database backup. Install
only this migration; do not run unrelated pending legacy ERP migrations:

```sh
php artisan migrate --path=database/migrations/2026_10_20_000008_create_mobile_notifications.php --force
```

The same migration exists on `api`, with identical table definitions, and can be
applied by the ERP `mobile-app:install` command. Table guards preserve data when
resuming partial installation. Explicit required dates use DATETIME to avoid old
MySQL implicit TIMESTAMP defaults. Rollback intentionally preserves inbox history,
deduplication state, devices and campaign/audit records.

Deploy the new services, controller, command, route changes and config together.
Refresh route/config caches using the deployment's existing process. Ensure the
existing Laravel scheduler calls `schedule:run` every minute. Do not create a
second independent worker for the same company/database. The command uses a
company cache lock; configure a shared lock-capable persistent cache for multiple
servers. Test only against staging or a dedicated passenger before releasing.

```sh
php artisan mobile:notifications
```

That command **can send actual phone alerts** when push is enabled. Running it is
not a read-only health check. ERP shows its last completed run and configuration
status. No queue worker is needed specifically for this command.

Configuration defaults in `config/mobile_notifications.php`:

```dotenv
MOBILE_NOTIFICATIONS_ENABLED=true
MOBILE_PUSH_ENABLED=false
MOBILE_FCM_PROJECT_ID=your-firebase-project-id
MOBILE_FCM_CREDENTIALS_FILE=/private/server/path/firebase-service-account.json
```

Keep the service-account JSON outside the web root and Git, readable only by the
runtime identity. Use a Firebase project/service account allowed to send Cloud
Messaging messages, and enable its HTTP v1 API. Upload the correct APNs credentials
to that project's iOS app. Only after configuration should `MOBILE_PUSH_ENABLED`
be set to true and the config cache refreshed. The project ID in the private JSON
must match `MOBILE_FCM_PROJECT_ID`. The Flutter app must use the same Firebase
project's **public** app configuration. See its `docs/NOTIFICATIONS.md`.

`features.notifications` in the shared company config allows ERP App Settings to
pause/resume notifications. The global environment switch can also disable them.
When paused the inbox API returns 503 and this worker does not create or send
alerts; existing records remain. On resuming, unread messages under 24 hours old
may still be queued. If changing that behavior, review pending records explicitly.

`GET /app/config` advertises notifications only when enabled and schema is installed.
`features.push_notifications` also requires the push switch, project ID and readable
credentials file. “Configured” is not proof of valid credentials/provider delivery.

## Passenger API contract

Base `/api/mobile/v1`, bearer authentication, verified passenger/control middleware,
and existing success/error envelopes. Requests cannot access another account or
company. Endpoints retain existing middleware and do not expose device credentials.

| Method | Path | Input / result |
| --- | --- | --- |
| GET | `/notifications` | Optional `paginated=1`, `page` (1–100000), `per_page` (1–50, default 20), `unread` (0/1) |
| GET | `/notifications/{id}` | Owned message; otherwise 404 |
| POST | `/notifications/{id}/read` | Idempotent; returns `unread_count` |
| POST | `/notifications/read-all` | Required `through_id`; only owned IDs up to this watermark are marked read |
| PUT | `/notifications/device` | `token` (Firebase installation ID), `platform` (`android`/`ios`); 10/minute |
| DELETE | `/notifications/device` | `token`; removes only this passenger/company binding; 10/minute |

A legacy list is returned unless `paginated=1`. Paginated `data` contains `items`,
`unread_count`, `pagination` (`current_page`, `last_page`, `total`). Each item is:

```json
{"id":123,"kind":"booking_confirmed","title":"Booking confirmed","body":"Your seats are confirmed.","booking_id":42,"read_at":null,"created_at":"2026-10-20T12:00:00+05:00"}
```

Device IDs are encrypted with the application's encryption key at rest and indexed
by SHA-256. Each binding records the current Sanctum access-token ID. FCM's HTTP v1
`fid` target matches Flutter Firebase Messaging 17 `register`/`onRegistered`.
Service-account authorization uses a short-lived signed OAuth assertion and cached
OAuth bearer; neither credentials nor provider response bodies are logged.

## Delivery and reporting semantics

- Only unread messages newer than 24 hours are queued; a later device registration
  does not replay an already processed message.
- Current verified account, company, account controls, password-reset requirements,
  and a non-revoked/non-expired access token are checked before sending. Normal
  access expiry remains 600 minutes. Session revocation stops subsequent sends.
- Up to 500 inbox records are queued and 100 device deliveries attempted per run.
  Transient errors retry up to five attempts with backoff. Unregistered devices are
  removed. A crashed attempt has a five-minute lease before recovery.
- Each notification/device pair has one durable delivery record. Provider/network
  uncertainty can still cause a retry after provider acceptance; collapse IDs/tags
  reduce duplicate display but cannot guarantee exactly-once phone delivery.
- **Push accepted** counts distinct passenger inbox messages accepted by FCM;
  **Failed devices** counts exhausted or permanent device failures. Neither proves
  the phone displayed or the passenger read an alert. Read counts come from the
  authenticated inbox read endpoint.
- FCM/APNs/OS settings, connectivity, app force-stop and session expiry can prevent
  phone alerts. The durable inbox remains the source of message history.

## Validation and dependencies

Automated tests use SQLite, fake FCM HTTP and temporary generated test keys. They
cover isolation, read watermarks, encrypted binding, revoked/expired sessions,
deduplication, retries, historical baselining, recent-booking priority, rescheduled
history, sandbox exclusion and migration recovery/MySQL SQL generation.
No real credentials, production migrations or actual passenger sends are required.
Real-device delivery remains a separate check after Firebase/APNs and signed builds
are configured. Retention of notification/audit history is currently unchanged;
no automatic purge removes historical inbox or campaign records.

Official references: [FCM Flutter setup](https://firebase.google.com/docs/cloud-messaging/flutter/get-started),
[HTTP v1 sending](https://firebase.google.com/docs/cloud-messaging/send/v1-api),
[message fields](https://firebase.google.com/docs/reference/fcm/rest/v1/projects.messages).
