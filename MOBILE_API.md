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

Booking quote, creation, list and detail endpoints preserve intentional business-error messages and their 4xx statuses (for example, expired quotes and unavailable seats). Database errors and unexpected failures are reported through Laravel's server-side exception logger and return HTTP `500` with `{"success":false,"message":"Something went wrong. Please try again.","data":null,"errors":{}}`, regardless of `APP_DEBUG`. Intentional service failures with a 5xx status retain that status but use the same generic message and are also reported. SQL, bindings, passenger details and stack traces are never included in these booking error responses. This change requires backend deployment only; no migration or Flutter change is required.

## Fare adjustments and quote breakdown

Mobile fares follow the existing ERP `BookingController::selected` seat-price
calculation and `seatFareIsWrong` booking validator. These ERP files and the
legacy online controllers remain unchanged. `MobileFareCalculator` mirrors their
calculation; regression tests compare mobile search, seats, quotes and bookings
against the original, independent ERP validator.

Eligibility and lookup criteria:

- Base fare: company, departure city, destination city and the seat's fare class
  from `fare_tables`. ERP casts the base fare to whole rupees.
- Fare table: the city-pair tariff must cover all company fare classes, and classes
  used by the selected bus must exist and be active, as in the ERP seat picker.
- Schedule discount: the schedule's `discount_id`, active, with an assignment to
  the selling terminal. Mobile uses `MOBILE_TERMINAL_ID` for that terminal.
- Terminal discount: the selling terminal and schedule route must match, and
  the travel/departure date must be within `start_date` and `end_date`, inclusive.
- Surcharge: the schedule's `surcharge_id` must refer to an active surcharge.
  ERP does not require a discount-terminal assignment for a surcharge.
- Soft-deleted fare/adjustment records are excluded by the existing models.

ERP calculation order:

1. Start with the original integer base fare.
2. Apply an eligible schedule discount. Percentage: subtract a percentage of the
   original fare and round to whole rupees. Flat: subtract the integer flat value.
3. Subtract any eligible terminal discount, as a percentage of the original fare.
4. If an active schedule surcharge exists, **replace the discounted result with
   original fare plus surcharge**. A percentage surcharge is calculated from the
   original fare and rounded to whole rupees. A flat surcharge uses its stored
   amount. This precedence also applies to an active zero-value surcharge.
5. If the resulting fare differs from the original, apply ERP `customRound`
   (round to whole rupees, then the nearest Rs. 50). Otherwise preserve the fare.

For a Rs. 2,500 base fare, a 10% schedule discount gives Rs. 2,250. With an
additional 5% terminal discount and no surcharge, the pre-rounding fare is
Rs. 2,125 and the payable seat fare is Rs. 2,150. If a Rs. 100 active surcharge is
assigned, the ERP seat fare is Rs. 2,600; those discounts do not reduce it.

Legacy online **search cards** historically combine adjustments differently from
the ERP seat picker/booking validator. Mobile consistently uses the latter's
bookable fare for its own search, seat selection and checkout. This does not
change the existing website, ERP or partner API behavior.

`GET /schedules` fare entries retain `original_amount` and `amount`, and add
`discount`, `surcharge` and signed `rounding_adjustment`. Seat entries retain
`price` and add `base_fare`, `discount`, `surcharge` and `rounding_adjustment`.
`POST /bookings/quote` keeps its existing request/authentication and adds numeric
`surcharge` and signed `rounding_adjustment` to `data`. The breakdown reports only
effective adjustments, so `discount` is zero while a surcharge takes precedence.
ERP's intermediate rounding is reflected in each effective adjustment. The final
rounding line reconciles those monetary components to the actual seat fare.

```
total = base_fare - discount + surcharge + rounding_adjustment
        + taxes + fees - wallet_deduction
```

Each selected seat is calculated/rounded independently before totals are summed.
Existing mobile wallet redemption applies afterward. Flutter displays the server
components and total, without reproducing these rules. A negative ERP result is
rejected with HTTP `422` rather than silently changed into a free mobile booking.

The existing quote JSON payload stores the breakdown; no migration is needed.
Booking rechecks current seat prices under its existing lock; a changed fare
returns HTTP `409` and requires a fresh quote. An old quote calculated with stacked
discounts and surcharge is rejected if it no longer matches the ERP fare.
Payment amounts come from the stored, revalidated server quote. Deploy the mobile
backend correction before releasing the Flutter breakdown display. Older clients
continue to use the server total and ignore new fields.

Regression tests cover original ERP validator parity, surcharge precedence
(including zero), flat/percentage calculations and intermediate rounding,
terminal/date/active/deleted-record criteria, class eligibility, complete tariffs,
mixed classes, stale quotes, wallet order and gateway amount. Run:

```bash
php artisan test --filter='MobileSchedulingRulesTest|MobileApiContractTest'
```


## Payment expiry storage

`payment.expires_at` remains an ISO-8601 deadline with a timezone offset. Status
checks and checkout updates must not move that deadline. The checkout window is
configured by `MOBILE_CHECKOUT_MINUTES` (default 10, clamped to 1–30 minutes) and
capped at journey departure and the ERP terminal's positive `reservation_cancel` limit.

Legacy MySQL/MariaDB can assign `ON UPDATE CURRENT_TIMESTAMP` to the first
non-null TIMESTAMP column. On affected installations, updating `checked_at`
overwrites `expires_at` and prematurely expires the booking. Migration
`2026_09_17_000011_fix_mobile_payment_expiry_column.php` changes it to a non-null
DATETIME with no automatic default or update. Existing stored values and payment
statuses are preserved; expired payments and released seats are not revived.
New installations use DATETIME from the initial payment migration. No Flutter
rebuild or API contract change is needed.

## Booking passenger gender

Booking quote requests accept `passengers.*.gender` as `male` or `female`. The
mobile backend converts these labels to the existing ERP ticket codes (`male = 1`,
`female = 0`) before creating tickets and their advance/partial history records.
Booking creation, detail and list responses return the text labels to mobile clients.
Unrecognized legacy ticket codes are returned as `null`, not assigned a gender.

The ERP ticket contract has no verified code for `other`. Booking quotes using it
return a field validation error (`422`); old persisted quotes using it are also
rejected at booking creation with `422`, with no booking records committed. Profile
and saved-passenger gender fields remain unchanged. Supporting `other` in bookings
requires an agreed ERP representation and corresponding backend integration.

Deploy `app/Services/Mobile/MobileBookingService.php` and
`app/Http/Requests/Mobile/QuoteBookingRequest.php` for this fix. No database migration
or Flutter rebuild is required. Regression tests use integer gender columns and
SQLite insert guards to reproduce the production MySQL type constraint.

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
- Signup, resend, and password-reset OTPs support SAR Zone WhatsApp delivery. See configuration below. `MOBILE_OTP_DRIVER=log` remains limited to local/testing environments.
- Password reset uses a short-lived OTP followed by a one-time, 15-minute reset token. Existing passenger API tokens are revoked when the password changes.
- Cancellation, refund, and rescheduling are deliberately not exposed to passengers.

## Mobile payment contract

See [MOBILE_PAYMENTS.md](MOBILE_PAYMENTS.md) for configuration, lifecycle, response examples, and verification steps. `POST /bookings/{invoice}/payment/refresh` requires the passenger bearer token and returns the same booking envelope as `GET /bookings/{invoice}`. Booking responses now include an optional `payment` object; clients must tolerate `null` for counter reservations.


### Payment preview

`GET /app/config` and `POST /bookings/quote` include `payment_environment` (`sandbox` or `live`) and `payment_preview` (boolean). When preview is true, the quote can list configured methods for display, but `POST /bookings` for an online method returns HTTP 409: `Payment preview only. No booking or payment will be submitted.` Preview also prevents hosted checkout and gateway inquiries. Clients must label preview and disable payment submission. Merchant secrets are never included in either response.

## WhatsApp OTP delivery

Set `MOBILE_OTP_DRIVER=whatsapp` and `MOBILE_WHATSAPP_API_KEY` in the backend environment. Use the existing SAR Zone middleware credential, not `companies.whatsapp_auth_key` (that field belongs to the older messaging integration). Never put this key in Flutter or tracked files. Defaults are client `kainat-travels`, template `otp`, language `en`; override with `MOBILE_WHATSAPP_CLIENT_CODE`, `MOBILE_WHATSAPP_OTP_TEMPLATE`, and `MOBILE_WHATSAPP_LANGUAGE`. Clear/rebuild Laravel configuration cache after deployment and restart long-running PHP workers if applicable.

The backend POSTs to `https://wa.sarzone.com/api/v1/whatsapp/send-template` with Bearer authentication and the same template fields as the existing booking OTP helper. It accepts Pakistani mobile forms `03xxxxxxxxx`, `3xxxxxxxxx`, and `923xxxxxxxxx` (formatting is stripped for delivery). It does not change passenger account lookup/uniqueness semantics. Codes use cryptographic randomness, are stored hashed in passenger-account fields, expire after `MOBILE_OTP_TTL_MINUTES` (default 10), and are consumed on verification. Signup and password reset use separate fields. Resending replaces the previous code. Provider acceptance requires HTTP 2xx and JSON `success: true`; it does not prove handset delivery. No automatic HTTP retry is performed.

Existing JSON endpoints and request bodies remain unchanged:

- `POST /auth/register`: `full_name`, `mobile`, `cnic`, `password`, `password_confirmation`, optional `email`; returns 201 with passenger, token, and `verification_pending: true`. Additional data fields: `verification_delivery_sent` (boolean, provider accepted) and `verification_message` (nullable safe failure explanation). Delivery failure keeps the unverified account/token so the app can offer resend; it never marks the passenger verified.
- `POST /auth/resend-otp`: Bearer passenger token, no body; success 200, delivery/configuration failure 503 or invalid delivery number 422.
- `POST /auth/verify-otp`: Bearer passenger token, `{ "code": "123456" }`; success 200 with `data.passenger` including `mobile_verified: true`; invalid/expired code 422.
- `POST /auth/forgot-password`: `{ "mobile": "03001234567" }`; 202 with generic account-existence wording; delivery/configuration failures use the mobile error envelope (503, or 422 for invalid delivery number).
- `POST /auth/verify-reset-otp`: `mobile`, `code`; returns the existing one-time reset token. `POST /auth/reset-password` continues to consume it and revoke passenger sessions.

WhatsApp mode never logs OTPs, API keys, full phone numbers, or provider response bodies. `disabled` remains the safe default for unconfigured deployments. Existing staff booking/loyalty/discount WhatsApp flows are unchanged. No migration is needed. Run `php vendor/bin/phpunit --do-not-cache-result --filter 'MobileOtpTest|MobileApiContractTest'`; tests use a disposable SQLite database and fake HTTP, never send a real WhatsApp message.


## Account deletion

`DELETE /api/mobile/v1/account` requires a passenger Sanctum bearer token for the configured company, `Accept: application/json`, and JSON `{ "password": "<current password>", "confirmation": "DELETE" }`. Throttled to 5 requests/minute. Staff tokens and other-company accounts are rejected (403); missing/expired tokens return 401. Invalid fields or wrong password return 422 in the normal `{success, message, data, errors}` envelope. A wrong password does not sign the passenger out. Rate limiting returns 429.

Success: HTTP 200, `{ "success": true, "message": "Your mobile account has been deleted.", "data": null, "errors": null }`. A single transaction records an audit event (account ID, company ID, timestamp), physically deletes the passenger account, saved passengers and mobile booking quotes, and revokes all its tokens. Failure rolls everything back. Passwords, OTP/reset digests and email are removed with the account. No OTP is sent for deletion; the current password provides fresh verification.

Shared ERP customer details (including name, contact and CNIC), invoices/tickets, payment records and loyalty records are retained. Account deletion does not cancel bookings, refund payments or erase loyalty balances. Payment reconciliation remains independent of the deleted account. Passengers should save ticket references before proceeding and contact support for existing trips or data retained by the business. A new signup can use the same number, requires normal verification and receives a new account ID; old mobile invoices are not reassigned to it. Customer-linked loyalty records may be available again after signup.

Deploy the new `mobile_account_deletions` migration and API before releasing the Flutter flow. No production migration is run by this implementation. The app must display API failures without claiming deletion, and clear its session and passenger caches after confirmed success. If a response is lost, the next request may return 401 because deletion already revoked the token; do not infer success from a network error.

Business follow-up: define and publish retention periods for the shared ERP records and deletion audit. This feature deletes the mobile account; it does not claim complete erasure of all customer data or implement a public web deletion-request page.


## ERP reservation status compatibility

Mobile-created tickets and their advance/partial records use the ERP's
`advance booking` status for holds, `booked` after verified payment, and
`canceled` with soft deletion on cancellation. ERP staff use the existing
`reserved-cancel` / `cancel-ticket` permissions. Mobile payment records retain
their separate payment lifecycle; API summary labels remain compatible with
existing clients. See `MOBILE_PAYMENTS.md` for the scoped data repair migration,
terminal reservation deadline and scheduler deployment requirements.
