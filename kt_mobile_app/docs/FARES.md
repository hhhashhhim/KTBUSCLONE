# Fare display and backend contract

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

Deploy the matching backend changes before releasing this app update. New fields
default to zero for older API responses; the app does not infer missing adjustment
rules or override the returned total. No database migration is needed for this
contract change. Existing quotes are revalidated by the server before booking.

Checks: `flutter test test/travel_models_test.dart test/fare_checkout_test.dart
test/payment_test.dart` and `flutter analyze`.
