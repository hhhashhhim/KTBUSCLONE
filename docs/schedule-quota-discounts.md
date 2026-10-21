# Schedule quota discounts

On Schedule, **Quota Discount** opens per-user settings. Schedule edit permission
is required. Users must belong to the same company and be active. Add one row
per staff/API login with a percentage from 0 to 100 (at most two decimal places).
Zero disables the offer. Removing a row deactivates it and retains an audit trail.

The rule belongs to the master schedule and applies separately to each dated
departure. It is selected using the authenticated user, never a request-supplied
user or terminal ID. Add the website, Sasta Ticket and Bookkaru login accounts
separately when those channels should receive the offer.

## Eligibility and accounting

- A finite quota must be configured. Blank/unlimited disables quota discounts.
- The seat must already count in quota: a live, paid midway ticket booked before
  the starting-city departure time. Existing legacy tickets with no booking time
  follow the established quota counting rule.
- The selected journey must be non-overlapping with all active bookings of the
  same seat on that dated departure. Earlier and onward segments both qualify.
- Full-route/overlapping/blocked seats, unpaid or cancelled qualifying bookings,
  other dates, other companies, and users without a rule do not receive offers.
- The percentage applies to the current net fare after existing discounts and
  surcharges, rounded to the nearest rupee. Rs 1,000 at 2% becomes Rs 980.
- The discount remains available after departure on seats that were already
  counted in advance quota; post-departure initial sales do not qualify a seat.
- Booking rechecks the actual seat, class, eligibility and fare while holding
  the schedule lock. Stale prices are rejected with a refresh-seat-map message.
- Ticket `seat_fare` retains the amount before the quota discount. `discount`
  includes the quota saving plus any manual/loyalty discount. `quota_discount`
  records the quota component separately; do **not** deduct it again. Reservation
  confirmation retains its quoted saving; no double deduction occurs.

## Partner seat preview contract

Both `/api/v1/booking/schedule/preview` and the legacy
`/api/v1/checkk/schedule/preview` include these fields on seat-map cells:

```json
{
  "seatNo": "1",
  "fare": 980,
  "fare_before_quota_discount": 1000,
  "quota_discount": 20,
  "quota_discount_percentage": 2,
  "discount": 20,
  "is_special": "Yes",
  "is_speacial": "Yes"
}
```

`is_special` is `Yes` or `No`, **not a boolean**. `is_speacial` is a compatibility
alias for the requested spelling. Partners must compare to `Yes` explicitly;
the string `No` is truthy in JavaScript. Existing `original_fare`, where present,
is unchanged. `discount` is the total difference from that original fare;
`quota_discount` is just the extra quota saving.

Submit the returned `fare` in `selected_seats_fare`. Do not subtract `discount`
again. Ordinary seats retain their fares and return `No`, with zero quota saving.
Class-level fares in the available-schedules listing remain unchanged because
an offer is for specific seats, not every seat of that class. Seat preview is
the authoritative price and special-seat source. Partner frontend animation
must be implemented by each partner; this change only provides the fields.

Settings are read live; no 60-day schedule resync is required. Original schedules
and details are not replaced. Existing historic tickets are not repriced.

## Deployment

After pulling `api`, run only this migration in the API project:

```sh
php artisan migrate --path=database/migrations/2026_10_21_000001_create_schedule_quota_discounts.php --force
php artisan route:clear
```

Pull `mix` for the modal and current frontend assets. No build command is required
on production. No actual partner booking or payment is sent by the tests.
