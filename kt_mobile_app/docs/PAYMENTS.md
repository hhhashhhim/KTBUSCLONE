# Mobile payments

The app supports the existing website's JazzCash wallet and Bank Alfalah card checkout through the mobile backend. Pay at Branch remains available when configured. OneLink is not advertised separately: the website labels its Bank Alfalah option Card / OneLink, but the implemented gateway contract selects card transaction type `3`.

## App flow

Live credential preview is configured locally at the user's request. Quotes include `payment_preview: true` and `payment_environment: live`; JazzCash and Bank Alfalah can be selected for review, while the app disables booking/payment submission. The backend independently blocks creation, checkout URL issuance, provider forms, inquiries, and reconciliation. Live credentials exist only in the ignored backend `.env`; none are in Flutter. No live gateway request or money transfer was used to verify this setup. Preview does not establish provider authentication or transaction readiness. Actual live checkout still requires public HTTPS, registered callbacks, provider acceptance, and explicit removal of preview mode.

1. Get a fare quote and enable only its `payment_methods`. Show **JazzCash Mobile Account** (`jazzcash`) and **Debit / Credit Card** (`bank_alfalah`) in both live and sandbox modes, with unavailable methods disabled when the API does not offer them. Card checkout uses Bank Alfalah; JazzCash uses the existing `MWALLET` hosted flow. An empty result provides a “Check again” action to reload the quote after setup.
2. Create the booking with `quote_token` and `payment_method`. Online reservations include a `payment` object.
3. Open the backend's signed HTTPS checkout URL in the system browser. The app contains no merchant keys and collects no card details.
4. Return to the app and check payment status. The screen checks on resume and every 15 seconds while foregrounded and pending. Only a backend-confirmed ticket gets a boarding QR.
5. Existing reservations can be reopened through My Bookings → View payment status, including after an app restart.

Launching checkout or returning from the browser does not indicate success. Network failures retain the last known state. Expired reservations and payments requiring head-office review do not produce a boarding ticket. Passengers can check again or contact support through booking details.

## Confirmed API contract

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
    "method": "jazzcash",
    "status": "pending",
    "expires_at": "2026-09-12T12:10:00+05:00",
    "checkout_url": "https://YOUR_API_HOST/api/mobile/v1/payments/UUID/checkout?expires=TIMESTAMP&signature=SIGNATURE"
  }
}
```

This is an illustrative field layout, not a runnable payment. `payment` is null for counter reservations. Methods are `counter`, `jazzcash`, and `bank_alfalah`. Payment states are `pending`, `paid`, `expired`, and `review_required`. A `review_required` booking may have `payment_status: paid` because funds were received but seats could not be confirmed. The app must check booking confirmation as well as payment status.

The checkout URL is null after checkout starts or the reservation expires. It must match the configured API host, port, and mobile payment path, use HTTPS, and contain the server signature. Once started, checkout is not silently relaunched; check its status or use support if the browser session was lost.

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
- `lib/features/payments/presentation/payment_screen.dart`: browser launch, status verification, recovery states.
- `lib/features/travel/domain/travel_models.dart`: optional payment on bookings.
- `lib/features/travel/data/travel_repository.dart`: payment refresh request.
- `lib/features/travel/presentation/lovable_search_flow.dart`: method selection and payment routing.
- `lib/features/tickets/tickets_screen.dart`: resume payment and confirmed-ticket QR display.
- `lib/app/router.dart`: payment route.
- `test/payment_test.dart`: trust validation and payment-state widget tests.
