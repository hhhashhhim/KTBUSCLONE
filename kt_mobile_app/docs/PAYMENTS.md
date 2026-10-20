# Mobile payments

New mobile bookings support Bank Alfalah card checkout and Pay at Branch when the API offers them. JazzCash was removed from new mobile checkout on 19 October 2026. The app filters it from older API quotes and rejects a stale JazzCash selection before making a booking request. The matching `mobile-backend` change excludes JazzCash even when environment or company settings still include it and rejects new JazzCash reservations with HTTP 422. Existing JazzCash reservations remain readable and verifiable; historical labels, callbacks and reconciliation are retained. The website and ERP payment choices are unaffected.

OneLink is not advertised separately: the website labels its Bank Alfalah option Card / OneLink, but the implemented gateway contract selects card transaction type `3`.

## App flow

On 15 September 2026, the user requested payment activation for backend upload and subsequent local app testing. The backend's ignored environment file now uses `payment_preview: false`, live providers, and the public HTTPS API origin. The server must receive these settings separately from source upload; follow `MOBILE_DEPLOYMENT.md` in the `mobile-backend` worktree. Merchant credentials remain server-side. The app enables submission only when the deployed API offers the method and reports preview disabled. No live gateway request or money transfer was used to verify activation. Registered callbacks, provider acceptance, migrations, and the scheduler must be verified on the deployment.

1. Get a fare quote and enable only its supported `payment_methods`: **Debit / Credit Card** (`bank_alfalah`) and **Pay at Branch** (`counter`). Card checkout uses Bank Alfalah in both live and sandbox modes and stays disabled unless the API offers it. An empty supported result provides a “Check again” action to reload the quote after setup. JazzCash never appears as a new checkout option.
2. Create the booking with `quote_token` and `payment_method`. Online reservations include a `payment` object.
3. After online booking creation, the app opens **Secure payment** automatically after a successful server status check. It loads the backend's signed HTTPS checkout URL using Android WebView / iOS WKWebView. The backend automatically POSTs its prepared provider form using a per-response CSP nonce, so neither intermediate page requires another tap. Provider forms and redirects remain inside the app. The app contains no merchant keys and collects no card details.
4. Close checkout or finish the backend return page to refresh payment status. The return request is allowed to finish before the WebView closes. The screen also checks on resume and every 15 seconds while foregrounded and pending. Only a backend-confirmed ticket gets a boarding QR.
5. Existing reservations can be reopened through My Trips, including after an app restart. Ticket details fetch the booking and verify pending/expired online payments. The main action is **Complete payment** when the server still offers an unstarted checkout, **Check payment status** when payment is unresolved or needs review, or **Book again** when both booking and payment are expired. Expired tickets retain a secondary **View payment status** link.
6. Complete payment in My Trips opens checkout directly after verification. Explicit payment navigation uses `/payments/{id}?checkout=1`; plain payment/status links only show status. The entry intent is consumed once: closing checkout, returning from the provider, polling, or resuming the app never automatically relaunches it. A failed verification hides checkout until a successful check and an explicit payment tap. Repeated taps cannot open duplicate payment routes or checkout screens. A local countdown ends the checkout affordance but does not declare the server reservation expired.

Both changes must be deployed: update the Flutter app and deploy `MobilePaymentController.php` plus `resources/views/mobile/payment.blade.php` from `mobile-backend`, then clear Laravel's compiled view cache. Until the backend update is deployed, the existing provider bridge still requires Continue to payment. No new endpoint, response field, or migration is required. JavaScript-disabled browser clients retain a manual form fallback; the app enables JavaScript. Error/return pages never auto-submit.
7. Book again clears the previous booking flow and opens a fresh search for current seats and fares. No old quote, seat selection, or payment is reused. My Trips refreshes when entered, on foreground resume, and after returning from ticket details. Details refresh after payment, on foreground resume, and through pull-to-refresh.

Launching checkout or returning from the WebView does not indicate success. Network failures retain the last known state and offer a return to payment status without replaying checkout. Expired reservations and payments requiring head-office review do not produce a boarding ticket. Passengers can check again or contact support through booking details. The WebView never attaches the passenger API bearer token to provider requests; HTTPS redirects are supported for bank authentication. Insecure and external-app navigation is blocked, with the existing local sandbox exception.

## Confirmed API contract

### Automatic checkout change — 21 September 2026

Repository: `hhhashhhim/KTBUSCLONE`. The change is split between these worktrees and branches; it does not require a migration.

Files changed on `mobile-frontend` under `kt_mobile_app/`:

- `lib/app/router.dart`
- `lib/features/travel/presentation/lovable_search_flow.dart`
- `lib/features/payments/presentation/payment_screen.dart`
- `lib/features/tickets/tickets_screen.dart`
- `test/payment_test.dart`
- `test/payment_checkout_test.dart`
- `test/payment_auto_checkout_test.dart`
- `test/ticket_payment_actions_test.dart`
- `docs/PAYMENTS.md`

Files changed on `mobile-backend`:

- `app/Http/Controllers/Mobile/MobilePaymentController.php`
- `resources/views/mobile/payment.blade.php`
- `tests/Feature/MobileSchedulingRulesTest.php`
- `MOBILE_PAYMENTS.md`

Verification includes automatic entry for new/resumed bookings, stale URLs, failed verification, cancellation/return, app resume, repeated taps, backend-only confirmation, both gateway form formats, and signed checkout replay rejection. An isolated Chrome check with intercepted requests verified one automatic POST per provider with fields preserved, plus the JavaScript-disabled manual fallback. No live payment was submitted.

### Endpoints

Implemented separately in the `mobile-backend` worktree:

- `POST /api/mobile/v1/bookings`: existing quote-token and payment-method request; online retries of the same used quote return its existing reservation.
- `GET /api/mobile/v1/bookings/{invoice}`: read an owned booking.
- `POST /api/mobile/v1/bookings/{invoice}/payment/refresh`: perform server-side payment verification and return the updated booking.

All three use the passenger bearer token, `Accept: application/json`, and the existing mobile success/error envelope. Refresh returns `404` for another passenger/company, `409` for a booking/payment conflict, and `503` for provider connectivity failures. A refresh response is throttled to 10 requests/minute and server inquiries are separated by at least 15 seconds. Existing request validation returns `422` and field errors.

Online booking responses include:

```json
{
  "payment": {
    "id": "server-generated-payment-uuid",
    "method": "bank_alfalah",
    "status": "pending",
    "expires_at": "2026-09-12T12:10:00+05:00",
    "checkout_url": "https://YOUR_API_HOST/api/mobile/v1/payments/UUID/checkout?expires=TIMESTAMP&signature=SIGNATURE"
  }
}
```

This is an illustrative field layout, not a runnable payment. `payment` is null for counter reservations. New booking methods are `counter` and `bank_alfalah`; existing payments may still return `jazzcash`. Payment states are `pending`, `paid`, `expired`, and `review_required`. A `review_required` booking may have `payment_status: paid` because funds were received but seats could not be confirmed. The app must check booking confirmation as well as payment status.

The checkout URL is null after checkout starts or the reservation expires. It must match the configured API host, port, and mobile payment path, use HTTPS, and contain the server signature. Once started, checkout is not silently relaunched; check its status or use support if the browser session was lost.

## Pending backend requirement: retry a verified failed payment

The existing `mobile-backend` routes and `MobilePaymentService` were checked for the ticket-action change. There is no retry endpoint or verified failed-attempt status. Its gateway inquiry returns a paid/not-paid result; not-paid alone must not authorize another charge. **Retry payment is therefore not enabled in Flutter.** A pending payment without a checkout URL continues to offer status verification.

Implement this separately on `mobile-backend` before integrating retry:

- Distinguish provider-confirmed terminal failure/cancellation from processing, unknown outcomes, and inquiry/network errors for each gateway. Confirm the provider contract; a closed WebView, timeout, return URL, or missing success response does not establish failure.
- Return an explicit server-authorized next action/retry eligibility with the booking. Preserve existing response fields and auth/error envelopes. An unresolved or received payment, expired/cancelled reservation, released seats, or departed trip must not permit retry.
- Provide an authenticated, owned-booking retry operation that rechecks the existing transaction, original reservation deadline, seats, fare, and passenger/company access under a lock. Do not extend the reservation, revive released seats, or reuse an old checkout URL.
- Keep each payment attempt/reference and its audit history, make duplicate retry requests idempotent, and reconcile late callbacks against all attempts so a delayed successful payment cannot issue duplicate tickets or silently permit another charge.
- Document the exact URL, method, request/response fields, validation, conflict, rate-limit, and provider-failure behavior. Add backend tests for concurrency, late success, expired/released seats, cross-passenger access, and duplicate requests. Then add the Flutter **Retry payment** action using the confirmed response contract.

These are requirements, not implemented endpoints or fields. No Laravel files were changed on `mobile-frontend`.

For local sandbox testing only, a development build accepts signed HTTP checkout URLs on `10.0.2.2`, `127.0.0.1`, or `localhost` when the backend payment explicitly includes `environment: sandbox`. Live and public checkout still require HTTPS. Quotes include `payment_environment`, and the app displays “Sandbox · Test payments” when applicable. The backend enforces separate sandbox/live gateway hosts and records the environment of each payment.

## Backend setup dependency

### Sandbox validation on 12 September 2026

The supplied website includes live checkout forms and a separate JazzCash sandbox API example. Explicitly authorized sandbox-only checks found:

- Hosted checkout paths under both `CustomerPortal` and `payment-orchestrator/CustomerPortal` returned HTTP 404. The alternate `MerchantPortal` path in the legacy merchant guide also returned 404.
- The legacy status inquiry returned HTTP 200 with provider code `199` (technical difficulties).
- The approved PKR 1 wallet test returned provider code `110` (invalid `TxnRefNo`) with both form and documented JSON encoding. The numeric-reference retry also failed validation. No test payment succeeded.
- A credential-only request reached required-field validation; this does not establish that the credentials are valid for payment processing.

Real JazzCash sandbox checkout remains disabled until a working gateway contract is verified. Bank Alfalah still needs verified sandbox merchant credentials. None of these checks sent a request to a live payment gateway or created a bus booking.

Before enabling live payments, follow `MOBILE_PAYMENTS.md` in the `mobile-backend` worktree: install its migration, configure merchant credentials and gateway URLs server-side, configure a public HTTPS `APP_URL` and matching Flutter `API_BASE_URL`, register the return URL, and run the reconciliation scheduler. Complete provider sandbox acceptance before switching to live merchant endpoints. The integration is disabled by default. No production migrations, gateway transactions, commits, or pushes were performed as part of implementation.

## Changed Flutter files

- `lib/features/payments/domain/booking_payment.dart`: payment contract, method labels, trusted checkout URLs.
- `lib/features/payments/presentation/payment_method_selector.dart`: API-authorized selection, sandbox availability, and retry.
- `lib/features/payments/presentation/payment_screen.dart`: in-app checkout, status verification, recovery states.
- `lib/features/payments/presentation/payment_checkout_screen.dart`: native WebView, close/return handling, loading and error states.
- `lib/features/travel/domain/travel_models.dart`: optional payment on bookings.
- `lib/features/travel/data/travel_repository.dart`: payment refresh request.
- `lib/features/travel/presentation/lovable_search_flow.dart`: method selection and payment routing.
- `lib/features/tickets/tickets_screen.dart`: resume payment and confirmed-ticket QR display.
- `lib/app/router.dart`: payment route.
- `test/payment_test.dart`: trust validation and payment-state widget tests.
