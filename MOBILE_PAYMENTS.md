# Mobile hosted payments

Implemented for `hhhashhhim/KTBUSCLONE`, branch `mobile-backend`. The Flutter client is maintained separately on `mobile-frontend` in `kt_mobile_app/`.

## Gateway choices and source

The reference website at `/Users/malik/Desktop/Codes/KTC Website Public/app` uses JazzCash wallet page redirection and Bank Alfalah's handshake/card page redirection. No website files or credentials were copied into the Flutter app. The Bank Alfalah flow uses `TransactionTypeId=3` (cards); the website's OneLink label is not a separately verified mobile method.

Provider references: [JazzCash merchant integration guide](https://payments.jazzcash.com.pk/SandboxDocumentation/Content/documentation/Payment%20Gateway%20Integration%20Guide%20for%20Merchants-v4.2.pdf), [JazzCash page redirection reference](https://sandbox.jazzcash.com.pk/SandboxDocumentation/v4.2/index.html), and [Bank Alfalah APG integration guide](https://merchants.bankalfalah.com/MerchantPortal/Content/APG%20Merchant%20Integration%20Guide%20v1.1.PDF). JazzCash's status inquiry URL and detailed `pp_PaymentResponseCode=121` / `pp_Status=Completed` contract follow the existing backend's `confirmJazzcashPendingPayment` flow. These must be verified against the merchant's sandbox/orchestrator account before production activation.

## Enablement

Online methods remain disabled by default. Do these steps on the intended backend deployment, after reviewing the changes:

1. Apply `database/migrations/2026_09_12_000008_create_mobile_payments_table.php`. This adds a mobile payment ledger; it does not rewrite ERP tables.
2. Configure `MOBILE_COMPANY_ID`, `MOBILE_TERMINAL_ID`, and `MOBILE_BOOKING_USER_ID` as documented in `MOBILE_API.md`. Existing route, terminal, quota, and service-user rules still apply. Provider identity is stored on the payment record; terminal attribution remains the configured mobile terminal.
3. Set a public HTTPS `APP_URL`. Flutter's `API_BASE_URL` must use the same host/port and end in `/api/mobile/v1`. Configure trusted reverse proxies appropriately so signed URLs retain HTTPS. No passenger token is placed in browser URLs.
4. Fill the relevant `MOBILE_JAZZCASH_*` or `MOBILE_ALFALAH_*` values from the merchant's environment-specific configuration. Use secrets management or the server's ignored environment file, never Flutter defines or committed source. `.env.example` lists the variables without credentials. No legacy hard-coded credential is automatically reused.
5. Set `MOBILE_PAYMENT_METHODS=counter,jazzcash,bank_alfalah` (or the desired subset). A company's `mobile_app_configs.payment_methods` array overrides this list when present. Set `MOBILE_ONLINE_PAYMENTS_ENABLED=true` after sandbox acceptance. Missing credentials, an invalid gateway host, a missing ledger table, or non-HTTPS `APP_URL` prevent online methods from being advertised.
6. Register/allow the mobile return URL pattern `/api/mobile/v1/payments/{payment-uuid}/return` with the providers. It accepts GET and POST. Providers must allow the dynamic payment path used in the submitted return URL. It is an API route and does not require a browser session or CSRF cookie. Refresh configuration/route caches as appropriate for deployment.
7. Ensure the Laravel scheduler runs every minute and uses a shared lock-capable cache (such as Redis) across application instances. The scheduler runs `mobile:reconcile-payments`. Do not enable online reservations without this job: it verifies payments when the app/browser does not return, and releases expired reservations.
8. Exercise sandbox checkout, provider return, app resume, failed/abandoned checkout, timeout, expiry, duplicate callbacks, and delayed settlement for both providers. Then separately select production merchant credentials/endpoints and perform the merchant's production acceptance process.

Known production endpoints from the existing integration:

- JazzCash checkout: `https://onlinepayments.jazzcash.com.pk/payment-orchestrator/CustomerPortal/transactionmanagement/merchantform`
- JazzCash status: `https://onlinepayments.jazzcash.com.pk/payment-orchestrator/api/v2/rest/payments/status/inquiry`
- Bank Alfalah base: `https://payments.bankalfalah.com` (sandbox: `https://sandbox.bankalfalah.com`).

JazzCash URLs have no default: configure the URLs supplied for the selected merchant environment. Gateway requests require HTTPS and an allowlisted provider host, use bounded timeouts, and do not follow redirects. Bank Alfalah encryption keys are used only on the server. Provider-required hosted form fields are delivered to the browser page, never as Flutter API data; the page is non-cacheable and sends no referrer.

## API contract

All passenger endpoints require `Authorization: Bearer <passenger-token>` and `Accept: application/json`. JSON POST bodies use `Content-Type: application/json`.

- `POST /api/mobile/v1/bookings/quote`: existing request; `data.payment_methods` may include `counter`, `jazzcash`, `bank_alfalah` when configured.
- `POST /api/mobile/v1/bookings`: existing `{quote_token, payment_method}` body. The backend recalculates/revalidates fares and seats, creates ERP reserved tickets (`type=advance booking`) and one payment ledger row in one transaction. A retry of the same used online quote and method returns the existing invoice. A pending quote is not itself a seat lock. Online gateway payments require a positive amount.
- `GET /api/mobile/v1/bookings/{invoice}` and booking listing: return the existing booking fields plus `payment` (`null` for counter reservations).
- `POST /api/mobile/v1/bookings/{invoice}/payment/refresh`: no body fields required. Verifies the owned payment and returns the updated booking in the existing `success/message/data/errors` envelope. A passenger cannot access another passenger's or company's payment.
- `GET /api/mobile/v1/payments/{uuid}/checkout?expires=...&signature=...`: temporary signed browser URL, issued only by the backend. It prepares the gateway form once and automatically POSTs it to the provider using a per-response CSP nonce. JavaScript-enabled clients see only a loading message; a manual Continue button is available only when JavaScript is disabled. Return and error pages never auto-submit. Reopening an already started checkout directs the passenger to check the app's status; it does not create another charge attempt.
- `GET|POST /api/mobile/v1/payments/{uuid}/return`: browser/gateway return. Request-provided success, amount, reference, or callback URL is never used to settle tickets. It only triggers a server-to-server status inquiry using the stored reference and configured merchant.

Illustrative `payment` object:

```json
{
  "id": "server-generated-uuid",
  "method": "jazzcash",
  "status": "pending",
  "expires_at": "2026-09-12T12:10:00+05:00",
  "checkout_url": "https://YOUR_API_HOST/api/mobile/v1/payments/UUID/checkout?expires=TIMESTAMP&signature=SIGNATURE"
}
```

The checkout URL is null once started, expired, paid, or awaiting review. Payment states are `pending`, `paid`, `expired`, `review_required`. A payment can be received while `booking.status=review_required`; such a booking has no boarding QR. Clients must require booking confirmation as well as payment before displaying a valid ticket.

Validation uses `422` with field errors. Ownership failures return `404`; verification/booking conflicts `409`; provider connectivity failures `503`. Signed checkout URLs return `403` when invalid/expired. Refresh is limited to 10 requests/minute per client, with at least 15 seconds between inquiries per payment.

## Settlement and expiry

The payment ledger stores integer paisa, currency, merchant method, immutable transaction reference, original invoice/quote, expiry, and wallet provenance. It never stores raw gateway response bodies or card details. JazzCash inquiries validate the stored reference and exact amount and check merchant/currency/bill reference when present; returned signatures are verified when present. Bank Alfalah inquiries validate reference, amount, merchant and store. Browser success messages cannot confirm a booking.

All mobile reservations use the ERP/API `advance booking` ticket type, including online payment holds. Verified payment changes tickets to `booked`; cancellation uses `canceled` and soft deletion. The ERP Cancel Ticket control therefore uses its existing `reserved-cancel` permission for holds and `cancel-ticket` for issued tickets. No mobile-specific ticket type is written. Advance/partial history records use the same ERP types.

Online holds have no legacy JazzCash ticket transaction ID until verified, so the legacy JazzCash job cannot settle them. `reserved:cancel` excludes only invoices linked to the same company in `mobile_payments`; `mobile:reconcile-payments` owns their verification, expiry and wallet return. Counter reservations and other ERP reservations keep their existing expiry workflow. An ERP cancellation removes checkout eligibility immediately; later receipts require review and cannot revive the seat.

Settlement uses the existing schedule cache lock and a database transaction with locked payment/ticket rows. Confirmation requires all expected pending seats and the stored amount to still match before expiry. Duplicate success does not reconfirm tickets. Expiry soft-deletes only the matching pending tickets, creates cancellation/audit records, and returns the loyalty points still attached to those tickets to their original card. Released ticket points are zeroed to prevent a second return.

The checkout window defaults to ten minutes, is configurable between one and thirty minutes, and is capped by the terminal's positive `reservation_cancel` limit and departure. The reconciler checks pending payments every minute. HTTP/verification exceptions retain the reservation for retry and produce an operator log entry. Expired payments that reached checkout are checked every thirty minutes for seven days. A delayed payment or a payment whose seats were changed/released enters `review_required`; it never revives a seat or issues a replacement ticket automatically. Head office must reconcile those receipts and handle refunds/rebooking through the existing staff workflow. Monitor reconciliation warnings and `review_required` payment rows; no new staff dashboard or automatic refund endpoint is included.

## Validation and changed files

Run `php vendor/bin/phpunit --filter Mobile`. The scheduling/payment lifecycle tests use an isolated SQLite in-memory connection; gateway calls are faked. The suite covers merchant/amount/reference mismatches, unsigned checkout, spoofed returns, duplicate creation/settlement, ownership, expiry/point return, delayed payment, and provider outages. No live provider transaction or production migration was run during implementation.

Changed backend files:

- `config/mobile_payments.php`, `.env.example`
- `database/migrations/2026_09_12_000008_create_mobile_payments_table.php`
- `app/Models/MobilePayment.php`
- `app/Services/Mobile/MobilePaymentGateway.php`, `MobilePaymentService.php`, `MobileBookingService.php`
- `app/Http/Controllers/Mobile/MobilePaymentController.php`
- `app/Http/Resources/Mobile/BookingResource.php`
- `routes/api/mobile.php`
- `resources/views/mobile/payment.blade.php`
- `app/Console/Commands/ReconcileMobilePayments.php`, `app/Console/Kernel.php`
- `tests/Feature/MobilePaymentGatewayTest.php`, `MobileSchedulingRulesTest.php`
- `MOBILE_API.md`, `MOBILE_PAYMENTS.md`

## Sandbox activation (local emulator)

Set `MOBILE_PAYMENT_ENVIRONMENT=sandbox`. This restricts gateway traffic to `sandbox.jazzcash.com.pk` and `sandbox.bankalfalah.com`; live endpoints are rejected. Live activation now requires explicitly setting `MOBILE_PAYMENT_ENVIRONMENT=live`.

Apply `2026_09_12_000009_add_environment_to_mobile_payments.php` after the base payment migration. Each new payment records its environment; an existing payment cannot be checked through a different environment. Pre-existing rows default to `live` so they cannot be silently reinterpreted as test payments.

Local/testing Laravel environments may use HTTP checkout on `127.0.0.1`, `localhost`, or Android's `10.0.2.2` only in sandbox mode. Flutter accepts these signed URLs only in development when the payment response explicitly identifies `environment: sandbox`. Public or live checkout continues to require HTTPS. Use a provider-approved public HTTPS callback if the merchant sandbox rejects a local return URL; no public tunnel is started automatically.

The quote now includes `payment_environment`; booking `payment` objects include `environment`. Checkout and the provider handoff page label sandbox payments clearly. Sandbox merchant credentials must be verified separately for each provider before advertising that method. Credentials and live activation are not inferred from the mode flag.


## Live credential preview without payments

Set `MOBILE_PAYMENT_ENVIRONMENT=live`, `MOBILE_PAYMENT_PREVIEW_ONLY=true`, and `MOBILE_ONLINE_PAYMENTS_ENABLED=true` with the selected methods and live merchant settings in the ignored backend environment file. Preview lets quotes list configured methods even on a local HTTP API. It does not relax live checkout HTTPS requirements: creating online bookings, issuing checkout URLs, gateway form generation, status inquiries, and reconciliation are blocked before side effects. No credentials are returned by the quote/config APIs. `features.online_payments` stays false in preview.

Both app configuration and fare quotes include `payment_preview` and `payment_environment`. The mobile checkout labels live preview, permits reviewing method choices, and disables submission. Older clients cannot bypass the backend preview guard.

The source website's live credentials were explicitly authorized for this local preview on 12 September 2026. They remain server-side. No live provider request, handshake, payment, reservation, or charge is used to validate preview. Before real activation, configure a public HTTPS mobile API and registered callback, complete provider acceptance and scheduler setup, and explicitly disable preview. Website production settings are unchanged.

## Activation preparation — 15 September 2026

At the user's request, the ignored local backend `.env` now selects live JazzCash
and Bank Alfalah payments with `MOBILE_PAYMENT_PREVIEW_ONLY=false`,
`MOBILE_ONLINE_PAYMENTS_ENABLED=true`, and
`APP_URL=https://mobile-api.kainattravels.com`. Existing server-side merchant
credentials are preserved. Source defaults remain disabled for unconfigured installs.
The earlier preview section describes the optional preview mode, not the current
local activation settings.

Follow `MOBILE_DEPLOYMENT.md` when uploading. Git/source upload does not transfer
ignored environment settings. Configure the server environment separately; the
local `.env` is an overlay of local service settings, not a complete production
Laravel environment. Provider callback registration, production acceptance, and
server scheduler operation are not established by changing these flags. No live
payment or production migration was performed for this activation preparation.

## JazzCash merchant-information rejection

On 18 September 2026, inspection of the live website's hosted checkout found
`pp_BankID` and `pp_ProductID` empty. The server-side comparison supplied by the
user confirmed that the mobile backend's merchant ID, password, integrity salt,
signing algorithm and checkout endpoint match the working website. The earlier
assumption that empty routing fields caused the rejection was not established.

Mobile checkout now defaults to the same empty routing fields. They remain
merchant-configurable through `MOBILE_JAZZCASH_BANK_ID` and
`MOBILE_JAZZCASH_PRODUCT_ID`; set values such as `TBANK` / `RETL` only when the
specific merchant integration requires them. Values are selected before signing.
The fallback is also empty when an older config cache lacks these optional keys.

Deploy `app/Services/Mobile/MobilePaymentGateway.php` and
`config/mobile_payments.php`, leaving the existing merchant credentials intact.
Leave the two optional routing variables empty for the current live account and
run `php artisan config:cache` from the mobile Laravel root. No migration,
Composer update, or Flutter rebuild is needed for this change. Restart PHP
workers if required by the hosting provider's OPcache configuration.

This aligns a confirmed request difference; it does not establish the exact
provider rejection reason or prove that live checkout succeeds. The other known
difference is the return URL: the website sends
`https://kainattravels.com/home/PaymentResponseRedirect`, while mobile sends
`https://mobile-api.kainattravels.com/api/mobile/v1/payments/{payment-uuid}/return`.
If rejection persists, confirm the approved mobile return URL/prefix with
JazzCash. Do not substitute the website callback: it handles website bookings
and does not implement the mobile payment ledger's verification workflow.

After deployment, use a fresh server-authorized checkout for acceptance testing;
do not replay an already-started checkout or assume an unresolved transaction
failed. Tests use fake credentials and make no live gateway requests. Never
share merchant credentials, complete checkout HTML, or raw gateway payloads.


## ERP reservation status alignment — 18 September 2026

References checked: `origin/api` (`TicketingApiController::bookSeat` reserves with
`advance booking` and confirms with `booked`) and `origin/mix`
(`BookingPage.vue` shows Reserved Seat and permits `reserved-cancel` for
`advance booking`). The mobile seat records now follow that contract.

Deploy the booking/payment services and `OnlineReservedCancelTicket` together,
then apply `2026_09_18_000012_align_mobile_reservations_with_erp_status.php` before
resuming booking traffic and scheduler workers. The migration converts only
active `pending booking` tickets belonging to a same-company pending mobile
payment, plus their active advance/partial records. It preserves timestamps,
payment deadlines, receipts, cancelled/deleted seats and non-mobile bookings.
It is idempotent and has no reverse conversion. No production migration has
been performed here.

The existing mobile response labels (`pending`, `confirmed`, `expired`,
`review_required`) describe the booking/payment summary; they are not stored
as ERP ticket types. Staff cancellation is returned as `canceled`, with no
checkout URL or boarding QR. Payment ledger states track gateway verification
separately. No Flutter rebuild or ERP frontend change is required.
