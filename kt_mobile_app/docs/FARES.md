# Fare display and backend contract

## Special-seat availability on schedule cards — 8 November 2026

Schedule results now consume the confirmed integer `available_special_seats`
field and show **N special discount seats** only when positive and the trip has
available seats and a known fare. The count represents available seats with an
actual quota saving, not all reusable seats or ordinary discounted fares. Missing
fields default to zero. See [the schedule-card notes](SCHEDULE_RESULTS.md) for
the backend contract. Both the backend and Android **1.0.13 (14)** are released;
see [the release report](BUILD_REPORT_1.0.13_2026-11-08.md).

## Special quota seats — 7 November 2026

The app now marks available special quota seats with an emerald outline and a
small `%` symbol. A **Special fare** legend appears when the map contains an
eligible seat. Selected seats keep their normal green fill/checkmark and the
special marker; occupied and unavailable seats retain their existing appearance.
Screen-reader labels state the special saving and payable price. A lower ordinary
schedule/terminal fare alone does not produce a special marker.

The verified mobile backend contract adds boolean `is_special`, numeric
`quota_discount`, `quota_discount_percentage`, and
`fare_before_quota_discount` to each seat. The marker requires the explicit server
flag, a positive saving and an available seat. Older responses default to no
special marking. `price` remains the server-authoritative payable seat fare.

Quotes now include `quota_discount` as a component of their existing `discount`.
Both checkout flows show **Special seat discount** separately from any remaining
ordinary discount. They continue to display the server's total, without applying
the special saving a second time. Backend changes on `mobile-backend` enforce
midway caps, exemptions, reuse eligibility, rounding, stale quotes and payment
confirmation. Flutter does not calculate these rules.

Files changed for this work (within `kt_mobile_app/`, branch `mobile-frontend`):

- `lib/features/travel/domain/travel_models.dart`
- `lib/features/travel/presentation/lovable_search_flow.dart`
- `lib/features/travel/presentation/search_flow.dart`
- `lib/features/travel/presentation/special_fare_widgets.dart` (new)
- `lib/features/travel/presentation/checkout_widgets.dart`
- `test/travel_models_test.dart`
- `test/seat_layout_test.dart`
- `test/fare_checkout_test.dart`
- `docs/FARES.md`

Validation: **78 Flutter tests passed** across travel models, seat layouts, both
checkout flows, schedule results and payment regression suites. A rendered seat
preview was inspected, including the special marker after selection. The seat
tests cover compact phones, scrolling, original fares and accessible labels.
`flutter analyze --no-pub` reported no issues. Existing pending changes were
preserved. The subsequent authorized release built **1.0.10+11**, passed all
**251 Flutter tests**, and is Active in Google Play internal testing. See
[the release report](BUILD_REPORT_1.0.10_2026-11-07.md). Nothing was committed or pushed.

The corresponding backend and shared ERP compatibility migration were deployed
before the Android release. Existing ERP settings must still assign a quota
discount to the mobile selling terminal: production terminal 24 currently has
zero active positive quota-discount assignments. Older app builds still receive
correct prices from the new backend but do not show the ring.

## Ordinary fare calculation

All prices come from `/api/mobile/v1`. Search displays schedule `fares[].amount`,
seat selection displays each seat's `price`, and checkout obtains a fresh
`POST /bookings/quote`. Booking submits only the server quote token and payment
method; the app never supplies an authoritative fare.

The backend follows the existing ERP seat picker and booking validator:

- Base fare comes from the company, city pair and seat fare class. The ERP tariff
  must be complete and the bus's fare classes must be active.
- A schedule discount applies only when active and assigned to the mobile selling
  terminal. A terminal discount must match that terminal and route and include the
  travel date within its start/end dates. Both discounts can apply together.
- An active schedule surcharge takes precedence over both discounts. It sets the
  price to original fare plus surcharge, including when its amount is zero.
- Percentages use the original fare. ERP integer casts, intermediate whole-rupee
  rounding, and final nearest-Rs.-50 rounding are preserved. Each seat is rounded
  before summing; existing loyalty redemption applies afterward.

For example, a Rs. 2,500 base fare with a 10% schedule discount is Rs. 2,250.
Adding an active Rs. 100 surcharge makes it Rs. 2,600 under ERP rules. The quote
then reports zero applied discount and a Rs. 100 surcharge. These rules live on
`mobile-backend`; Flutter displays the response without recomputing them.

The confirmed quote contract includes numeric `base_fare`, `discount`,
`surcharge`, signed `rounding_adjustment`, `taxes`, `fees`, `wallet_deduction`,
and `total`. The displayed components reconcile as:

```
total = base_fare - discount + surcharge + rounding_adjustment
        + taxes + fees - wallet_deduction
```

Both checkout screens show nonzero surcharges and rounding adjustments separately.
Discount and wallet rows display deductions. Money formatting retains paisa when
present; the total and payment button always use the server's `total`.

The current checkout booking summary now includes the original fare, discount,
and any nonzero surcharge, rounding, tax, fee or wallet adjustments. The original
fare is crossed out when the quote reports an applied discount. Wallet redemption
alone does not cross out the original fare. The selected-seat footer also shows
the sum of the server's `base_fare` crossed out above the sum of `price` when the
price is lower. Older seat responses without `base_fare` use `price` as their
original value, so no discount is invented. Search cards already pair each fare's
`original_amount` with its `amount` and retain their existing crossed-out display.

## Rawalpindi–Faisalabad investigation, 6 November 2026

Read-only checks in the deployed `mobile-backend` application confirmed:

- Mobile selling terminal: 24, Online Mobile; company: 1.
- The live destination service for Rawalpindi (city 1) returns Faisalabad (city 3)
  and Faisalabad Kamalpur Interchange (city 130).
- Schedule 1792, route 94, on 7 November 2026 at 16:15 is mobile-visible and
  returned one schedule-search result for Rawalpindi to Faisalabad.
- Its Luxury Class FSD fare returns `original_amount: 2300`, `discount: 299`,
  `surcharge: 0`, `rounding_adjustment: -1`, and `amount: 2000`.

The current checkout had hidden these adjustment details despite retaining the
server's payable total. The client also retained city/destination responses for
the whole provider-container lifetime. These providers now dispose when unused,
so a new search visit or reselecting an origin reloads the catalog. The current
search screen also has a **Refresh cities** button, which reloads the lists and
clears old selections. This addresses stale client discovery after ERP sharing
changes; it does not override backend visibility rules. The live check could not
inspect the user's installed app cache, so it does not prove that caching was the
only cause of the reported missing destination.

No backend code, production configuration, schedule permission or fare was changed
for this investigation. These Flutter changes require a new app build/release;
the existing live backend already supplies the required fields.

The full breakdown requires the backend contract above, which the checked live
deployment already supplies. New adjustment fields default to zero for older API
responses; the app does not infer missing adjustment rules or override the returned
total. No database migration is needed. Existing quotes are revalidated by the
server before booking.

Checks: fare models, schedule cards, checkout adjustments, selected-seat original
prices, city refresh and payment regression tests: **72 tests passed** across
`travel_models_test.dart`, `fare_checkout_test.dart`, `seat_layout_test.dart`,
`schedule_results_test.dart`, `city_catalog_test.dart`, `payment_test.dart`,
`payment_checkout_test.dart` and `payment_auto_checkout_test.dart`.
`flutter analyze --no-pub` reported no issues; `git diff --check` passed.

Files changed on `hhhashhhim/KTBUSCLONE`, branch `mobile-frontend` (all paths below
are relative to `kt_mobile_app/`):

- `lib/features/travel/application/travel_providers.dart`
- `lib/features/travel/domain/travel_models.dart`
- `lib/features/travel/presentation/lovable_search_flow.dart`
- `lib/features/travel/presentation/checkout_widgets.dart`
- `test/city_catalog_test.dart` (new)
- `test/travel_models_test.dart`
- `test/fare_checkout_test.dart`
- `test/seat_layout_test.dart`
- `docs/FARES.md`

The initial investigation made no commit, push, app release or production mutation.
The subsequently requested Android update was built as **1.0.9 (10)** and published
to Google Play Internal testing; see [the release report](BUILD_REPORT_1.0.9_2026-11-06.md).
Backend configuration and fare rules remain unchanged, and no Git commit or push
was performed.

## Wallet points and the checkout button — 8 November 2026

Android 1.0.14 requests a new server quote when a changed points amount is
applied. Both Booking summary → Total amount and the Continue/Reserve button
display that quote's `total`. A positive `wallet_deduction` appears as a negative
Wallet line in the summary. The previous button is hidden while the new quote
loads; Flutter does not calculate redemption rates or deduct money locally.

The live investigation found the environment wallet default enabled but company
1's saved `features.wallet` override disabled. The backend work unit enabled
only that override, with a private backup and transaction, at 01:52 PKT.
Public HTTP 200 at 01:52:08 confirmed `wallet: true`, version 1.0.14 and
maintenance disabled. Existing loyalty-category rates were preserved.
No new app build is needed. Reopen checkout to request a fresh quote if points
were selected before the setting was corrected.

Additional tests apply 50, 125, 250 and then zero points and verify the summary
and button amounts for both counter and Bank Alfalah checkout. The checkout,
fare and payment suites passed **36 tests**, and analysis of the changed test
file found no issues. Backend verification also passed three tests with 38
assertions, including real loyalty models, the company override, balance-limit
rejection and unchanged points balances during quoting.

Frontend files changed for this follow-up: `test/checkout_points_test.dart` and
this document on `hhhashhhim/KTBUSCLONE` / `mobile-frontend`. Backend tests and
documentation were handled separately on `mobile-backend`; see its
`MOBILE_DEPLOYMENT.md` wallet-enablement entry. No real booking, payment or
points debit was created for verification. No commit, push or new store upload
was performed. No deployment dependency remains pending.

## Pending reservations shown as a fare error — 8 November 2026

The checkout failures at 01:55 PKT were HTTP 409 responses. The passenger's
reported message matched the backend's pending-reservation guard: “Please
complete your existing reservations before making another booking.” The live
limit was two reservations. The two recent Bank Alfalah holds expired at
01:55:49 and 01:58:41; their recorded statuses were `expired`, updated at
01:56:03 and 01:59:07 respectively. A read-only check at 02:03:34 found no
qualifying pending reservations, so retrying checkout could proceed past this
guard. No reservations, payment states, balances or limits were changed by this
investigation.

Checkout now recognizes that existing HTTP 409/message contract and shows
“Complete your existing reservations” with a “View my trips” action. The action
refreshes bookings and opens `/tickets`. Other quote failures retain their
existing error and retry behavior. The payable button stays hidden when the
quote fails.

Changed on `hhhashhhim/KTBUSCLONE` / `mobile-frontend`:

- `kt_mobile_app/lib/features/travel/presentation/lovable_search_flow.dart`
- `kt_mobile_app/test/checkout_points_test.dart`
- `kt_mobile_app/docs/FARES.md`

Validation: **38 tests passed** across checkout points, fare checkout and
payments; analysis of the changed Dart files found no issues; `git diff --check`
passed. Backend code was inspected on `mobile-backend` without changes. The
clearer UI needs a future app build/upload; retrying after hold expiry works in
the current app. No commit, push, new build or store upload was performed for
this follow-up.

The subsequently requested upload published this UI as **1.0.15 (16)** to
Google Play internal testing on 8 November at 02:18 PKT. The required version
was updated only after tester availability was verified; see
[the release report](BUILD_REPORT_1.0.15_2026-11-08.md). The full release suite
passed 280 tests. Nothing was committed or pushed.
