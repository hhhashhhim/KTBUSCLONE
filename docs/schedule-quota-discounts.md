# Schedule quota discounts

On Schedule, **Quota Discount** opens per-terminal settings with searchable Select2.
Schedule edit permission is required. Terminals must belong to the same company
and must not be hidden or deleted. Add one row per terminal with a percentage
from 0 to 100 (at most two decimal places).
Zero disables the offer. Removing a row deactivates it and retains an audit trail.

The rule belongs to the master schedule and applies separately to each dated
departure. Every user assigned to that terminal shares its rule. Third-party APIs
use the authenticated API login's terminal, not a request-supplied terminal ID.
Configure the Website, Sasta Ticket and Bookkaru terminals as needed. Portal
preview and booking use the selected booking terminal (validated against the
company), falling back to the logged-in user's terminal when not supplied.

## Eligibility and accounting

- A finite quota must be configured. Blank/unlimited disables quota discounts.
- The seat must already count in quota: a live, paid midway ticket, including
  tickets sold or confirmed after the starting-city departure time. Booking time
  does not disqualify a paid midway seat from pink-seat reuse or quota discounts.
- The selected journey must be non-overlapping with all active bookings of the
  same seat on that dated departure. Earlier and onward segments both qualify.
- Full-route/overlapping/blocked seats, unpaid or cancelled qualifying bookings,
  other dates, other companies, and terminals without a rule do not receive offers.
- The percentage applies to the current net fare after existing discounts and
  surcharges. The resulting payable fare is rounded per seat to the nearest
  Rs 100 (Rs 2,130 becomes Rs 2,100; Rs 2,295 becomes Rs 2,300; halfway rounds up).
  The payable amount never exceeds its pre-quota fare. The recorded saving is
  the difference after rounding; small discounts may round away. Non-quota
  fares are unchanged. Portal and both partner APIs share this calculation.
- The discount remains available after departure, including seats first sold
  after departure. The quota usage count includes distinct paid midway seats;
  the booking cap still lifts at the starting-city departure time.
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

After pulling `api`, run these scoped migrations in the API project (already-run
migrations are skipped):

```sh
php artisan migrate --path=database/migrations/2026_10_21_000001_create_schedule_quota_discounts.php --force
php artisan migrate --path=database/migrations/2026_10_24_000001_create_schedule_quota_terminal_discounts.php --force
php artisan route:clear
```

Pull `mix` for the modal and current frontend assets. No build command is required
on production. No actual partner booking or payment is sent by the tests.

Terminal rules are stored in `schedule_quota_terminal_discounts`. The migration
preserves the original user table unchanged. Existing active user rules whose
users map to a valid same-company terminal transfer automatically if the rates
agree. Conflicting rates produce an inactive rule with a warning in the editor;
set a percentage and save to resolve it. Users without a valid terminal cannot
be transferred; configure their intended terminal explicitly. Migration retries
do not overwrite existing terminal rules. No historical tickets are repriced.
