# Kainat Travels Passenger Mobile API

Base path: `/api/mobile/v1`

The mobile API is isolated from the staff-facing `/api/web/v1` routes. Passenger tokens are issued to `PassengerAccount`, never to ERP `User` records. `MOBILE_COMPANY_ID`, `MOBILE_TERMINAL_ID`, and `MOBILE_BOOKING_USER_ID` must all point to records in the same company.

## Public endpoints

- `GET /app/config`
- `POST /auth/register`
- `POST /auth/login`
- `POST /auth/forgot-password`
- `POST /auth/verify-reset-otp`
- `POST /auth/reset-password`

## Authenticated passenger endpoints

- `POST /auth/logout`
- `POST /auth/resend-otp`
- `POST /auth/verify-otp`
- `GET|PUT /profile`
- `GET|POST /saved-passengers`
- `PUT|DELETE /saved-passengers/{savedPassenger}`
- `GET /cities`
- `GET /destinations?origin_id={id}`
- `GET /schedules?origin_id={id}&destination_id={id}&date=YYYY-MM-DD`
- `GET /schedules/{scheduleDetail}/seats?origin_id={id}&destination_id={id}&date=YYYY-MM-DD`
- `POST /bookings/quote`
- `POST /bookings`
- `GET /bookings?page=1&per_page=20` (`per_page` is capped at 50)
- `GET /bookings/{invoice}`
- `GET /notifications`
- `GET /fleet`
- `GET /wallet`

All successful mobile responses use:

```json
{"success":true,"message":"...","data":{},"errors":null}
```

Validation failures use the same envelope with status `422` and field errors.

## Schedule search performance

`GET /schedules` keeps the same request, authentication, response fields, and ERP eligibility rules. Search now loads shared booking-window settings, fares, adjustments, terminal restrictions, and tickets in batches using `MobileSearchData`. The helper exists only within one `schedules()` call; it is not a cross-request cache. Seat lookup, quote, and booking validation still read current data independently.

Ticket batches are scoped to company and exact schedule/date pairs. Cancelled and soft-deleted tickets remain excluded. The same fresh ticket collection supplies journey-overlap checks and the run-wide online quota. Search counts seats using the same eligibility loop as the full seat layout, without constructing all seat response objects. Comparisons on the ERP's DATE columns (`departure_date`, `schedule_date`) use direct comparisons so existing indexes can be used without wrapping columns in `DATE()`.

Local benchmark on 2026-09-12: 20 matching buses, one fare class, SQLite in memory, one warm-up plus five measured service calls. Before: 188 queries and 24.30 ms median. After the final change: 17 queries and 7.53 ms median (91% fewer queries, approximately 3.2 times faster). This measures service overhead, not network latency, production MySQL performance, or phone loading time. The configured command-line local MySQL database was unavailable (`Unknown database`), so no live-database latency claim or index migration is made.

Regression coverage includes bounded query counts with 20 buses, multiple fare classes and adjustments, booking windows, exact overnight runs, company isolation, cancelled/deleted tickets, journey overlap, quotas, and changes between repeated calls on the same service instance. Run the focused suite with:

```bash
php vendor/bin/phpunit --do-not-cache-result tests/Feature/MobileSchedulingRulesTest.php
```

The scheduling suite creates its own disposable SQLite in-memory connection; it does not run legacy ERP migrations against the configured database. Deployment requires only the updated backend PHP files; the Flutter API contract is unchanged.

## ERP scheduling and online-booking rules

`MobileSchedulePolicy` reads the existing ERP tables on every request. It is used by schedule search and direct seat lookup; quoting and booking creation use that same seat lookup. Booking creation rechecks eligibility, seats, fares, and the remaining online quota inside the existing `stayLock:{schedule_id}` lock and database transaction. A quote does not reserve a seat or exempt a passenger from subsequent ERP changes.

- `MOBILE_TERMINAL_ID` must identify a non-hidden, non-deleted **online terminal** in `MOBILE_COMPANY_ID`. Mobile tickets then consume the ERP's online quota and retain online-terminal reporting attribution. Missing, hidden, foreign-company, or non-online terminals return `503`.
- `MOBILE_BOOKING_USER_ID` must identify a non-hidden service user in the same company. Its existing `check_booking_minutes` setting controls the ERP booking-opening window described below.
- Schedule permission requires an active `schedule_terminal_visibilities` row with `visibility = 1` for this company and terminal. No permitted schedules means no results.
- Hidden/deleted cities, schedules, and routes cannot be booked. Dropped runs are excluded using the company, schedule ID, and **schedule date**, including for journeys whose departure date differs from the run's first departure date.
- A matching `terminal_visibilities.online_visibilty = 1` blocks that route's origin/destination pair online. The inverted flag and its spelling are inherited from ERP; `0` permits online visibility. Physical-counter visibility does not grant mobile access.
- Where company-specific `route_online_terminals` assignments exist, the mobile terminal must be assigned. A route without assignments keeps the existing online API's unrestricted-terminal behavior, subject to all other rules.
- `terminals.advance_booking` uses the same exclusive upper date as ERP: `departure_date < today + advance_booking`. A value of `1` allows today only; `null` means no upper date limit. Past departure dates are rejected again at booking time.
- When the service user's `check_booking_minutes` is enabled and the city pair has a non-negative `booking_minutes`, sales open at departure time plus the mobile terminal's route-specific `terminal_time_differences.time_difference`, minus `booking_minutes`. This is an **opening window**, not a pre-departure closing cutoff. The boundary uses the Laravel application timezone.
- Terminal `available_seats` and route `online_seat_choices` restrict selectable seats. Search counts use those restrictions as well as occupied seats.
- For city pairs with `limited_seats.limited_seat = 1`, the remaining quota is the route's `online_seats` minus active online tickets for the entire run, across online terminals and city pairs. Cancelled/deleted tickets do not consume it. `maximum_selectable_seats` is capped by that remaining quota and may be `0`; exhausted seats are `unavailable`. Quote and booking requests cannot exceed the current limit.

Restricted journeys are omitted from schedule search; direct seat, quote, and booking requests return `404` with the mobile error envelope when the journey is no longer eligible. Seat/quota conflicts return `409`. The app should refresh availability after either response. City catalog endpoints still list visible ERP cities; schedule search determines whether a particular journey is offered.

No new migration or endpoint is required for these checks. Configure the mobile online terminal's schedule/route permissions and service user in ERP before enabling bookings. Later changes to these existing ERP settings take effect on the next API request without a Flutter release. New kinds of ERP rules still require corresponding backend implementation and tests.

Regression coverage: `tests/Feature/MobileSchedulingRulesTest.php` exercises real HTTP endpoints, Eloquent models, and services against a dedicated SQLite in-memory database, including successful bookings, direct-ID access, and ERP changes after quoting.

## Safety and feature limits

- Schedule, fare, and seat data come from existing Laravel models.
- Quotes expire after ten minutes and booking creation rechecks live seats and fares under a cache lock.
- Counter reservations use the existing advance-reservation workflow. JazzCash and Bank Alfalah hosted payments are available when explicitly enabled and configured; see [mobile payment setup](MOBILE_PAYMENTS.md).
- Mobile gateway credentials are read from server configuration, independently of the legacy website. Online payments are disabled by default.
- Wallet reads the existing loyalty-card balance (`card_assigns.starting_points`) for the signed-in passenger. A quote may include an optional `points_to_use` integer; the server calculates and revalidates the discount when the booking is created. The existing ticket `points_usage` field is preserved so the current cancellation workflow can return points.
- `GET /fleet` returns active `mobile_fleet_media` records for the configured company. Each record may be linked to a bus class and has a `media_type` such as `exterior`, `interior`, or `seats`. Images are never bundled into the app: the URL is stored in the database. Management/upload screens for these records belong in the staff system and are not part of the passenger mobile API.
- Notifications return an empty list and the feature flag remains disabled because no passenger notification store or device-token delivery integration exists.
- SMS/OTP delivery requires a production provider implementation. `MOBILE_OTP_DRIVER=log` is limited to local/testing environments.
- Password reset uses a short-lived OTP followed by a one-time, 15-minute reset token. Existing passenger API tokens are revoked when the password changes.
- Cancellation, refund, and rescheduling are deliberately not exposed to passengers.

## Mobile payment contract

See [MOBILE_PAYMENTS.md](MOBILE_PAYMENTS.md) for configuration, lifecycle, response examples, and verification steps. `POST /bookings/{invoice}/payment/refresh` requires the passenger bearer token and returns the same booking envelope as `GET /bookings/{invoice}`. Booking responses now include an optional `payment` object; clients must tolerate `null` for counter reservations.


### Payment preview

`GET /app/config` and `POST /bookings/quote` include `payment_environment` (`sandbox` or `live`) and `payment_preview` (boolean). When preview is true, the quote can list configured methods for display, but `POST /bookings` for an online method returns HTTP 409: `Payment preview only. No booking or payment will be submitted.` Preview also prevents hosted checkout and gateway inquiries. Clients must label preview and disable payment submission. Merchant secrets are never included in either response.
