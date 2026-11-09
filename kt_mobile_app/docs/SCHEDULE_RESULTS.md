# Bus search cards

## Available special-discount seats — 8 November 2026

Both schedule result layouts now show a green `%` label such as
**3 special discount seats** alongside the existing availability information.
The main ticket card puts it directly below **25 seats left**. It appears only
for a positive server count on a trip with available seats and a known fare.
Zero counts, sold-out trips, missing fares and older API responses omit the label;
ordinary discounted fares do not create a special-seat count. Singular/plural
labels and screen-reader wording identify the count as available seats.

The confirmed backend contract adds integer `available_special_seats` to each
authenticated `GET /api/mobile/v1/schedules` result. The backend counts seats
using the same journey/date, terminal permissions, paid reuse, positive saving
after rounding and availability rules as the seat map. It caps the count by
remaining online availability. Flutter displays the returned count without
calculating discount eligibility or changing the displayed schedule fare.
The existing result refresh behavior reloads both counts together.

Released in **1.0.13 (14)** on 8 November 2026: the backend service/resource
change is deployed and Google Play reports the app available to internal testers.
At 01:02 PKT, Multan → Rawalpindi schedule 1563 at 13:44 returned 20 available
special seats, matching the seat map. No migration, commit or push was performed.
See [the release report](BUILD_REPORT_1.0.13_2026-11-08.md) for full release validation.

Validation: 57 Flutter tests passed across schedule results, travel models, seat
layouts and checkout fares; the 14 schedule-card tests were also rerun with
rendered Material icons and Roboto button text for visual inspection. Tests cover
positive/singular counts, zero/missing counts, sold-out/unpriced trips, both result
layouts, screen-reader wording, sorting/selection, and 320–390 px screens with
text scaling up to 1.8. `flutter analyze --no-pub` reported no issues and
`git diff --check` passed. The backend scheduling/API contract suites passed
215 tests / 1,800 assertions using the existing isolated locked-dependency runtime
(the worktree's legacy vendor directory is incompatible). A dependency PHP 8.4
deprecation was emitted; no dependency files were changed.

Files changed for this addition, all under `kt_mobile_app/` on `mobile-frontend`:

- `lib/features/travel/domain/travel_models.dart`
- `lib/features/travel/presentation/schedule_result_card.dart`
- `lib/features/travel/presentation/search_flow.dart`
- `lib/features/travel/presentation/special_fare_widgets.dart`
- `test/schedule_results_test.dart`
- `test/travel_models_test.dart`
- `docs/SCHEDULE_RESULTS.md`
- `docs/FARES.md`

The separate `mobile-backend` worktree changes `app/Services/Mobile/MobileTravelService.php`,
`app/Http/Resources/Mobile/ScheduleResource.php`, `tests/Feature/MobileSchedulingRulesTest.php`,
`tests/Feature/MobileApiContractTest.php` and `MOBILE_API.md`. Existing pending
changes in both worktrees were preserved.

## Upcoming departures — 7 November 2026

The API is authoritative for upcoming boarding times in Pakistan time. Search
results now dispose their cache when the page is left, refetch when the app
resumes, and refresh once a minute while foregrounded. Both result screens show
loading during refresh rather than keeping stale rows selectable. New searches
therefore fetch current eligibility, and an open page does not retain departed
results indefinitely. Network responses can still age between refreshes; direct
seat, quote and booking requests recheck the departure boundary on the backend.
The app does not invent eligibility from the phone's clock.

Regression coverage includes repeat searches, foreground refresh, and resume;
the backend also covers exact departure time, stale quotes, timezone and overnight
midway boarding. Release: `1.0.11+12`.

The results page uses the existing authenticated `GET /api/mobile/v1/schedules`
contract with `origin_id`, `destination_id`, and `date`. It displays the returned
bus class, capacity, cities, departure/arrival times in 12-hour AM/PM format, and
available seat count.
Arrival uses the server's `arrival_at`, with a small “Estimated” label below it
and `+1 day` / `+2 days` when arrival falls on a later date. The journey duration
between the cities is the positive difference between the two timestamps,
rounded for display to the nearest half hour (15-minute ties round up, and a
positive journey under 15 minutes displays 30 minutes). Examples: `5 hr`,
`5 hr 30 min`, and `30 min`. The arrival timestamp itself is not rounded.
Absent or nonpositive journey times stay unspecified. Sorting and seat selection
use the original schedule object.

Both city names share a row below the time/estimate row so their first lines
stay aligned when the arrival label or a time wraps. Enabled “Select seats”
buttons fade horizontally from the theme's dark green to primary green, with a subtle outline
and shadow. Unavailable buttons retain their disabled appearance.
Vertical card padding and section gaps are slightly tighter, reducing each card
by 20 logical pixels while preserving text sizes and the button's touch area.

The displayed fare is the lowest `fares[].amount`. Its crossed-out original price
comes from **the same fare entry's** `original_amount`, and is displayed only when
it is greater than `amount`. Equal prices and surcharges do not get a discount
treatment. Missing `original_amount` defaults to `amount`, as before. Both prices
use PKR and retain fractional rupees when supplied. Empty fares show “Fare
unavailable”; booking still obtains the authoritative quote from the server.

On 23 October 2026, the operator confirmed that Wi-Fi, charging, meals, reclining
seats, entertainment, and luggage are standard on every bus. The six bright orange
Material icons appear directly on the white card, with no background strip, and
represent that confirmed fleet-wide offering;
long-press tooltips and accessibility labels identify each icon. They are not
inferred from a bus class or an undocumented API field. If amenities later vary by
bus, the backend must supply that configuration before adding per-bus filtering.

Arrival times require the matching `MobileTravelService.php` update from
`mobile-backend`. It now fills the existing `arrival_at` field using the ERP
city-pair tariff's `time_difference` (the same source as the existing booking
API). No migration or additional API field is needed. Until that update is
deployed, or when a city pair has no valid configured duration, the app keeps
the arrival placeholder and omits the duration instead of inventing a time.

## Server-based departure calendar — 7 November 2026

Both search layouts use the public app/config `server_date` for the default
travel day, the date picker's today marker, its first selectable day and its
existing 90-day presentation range. They interpret the returned calendar day
without converting it to the phone's timezone. The backend remains authoritative
for actual advance-booking limits and schedule eligibility.

The date is fetched on search entry, every foreground minute, on resume, before
opening the picker and before submitting a search. A future selection is retained;
a selection that has become past is moved to the current server day. If cities
change while a date refresh is pending, that search is abandoned safely.
Missing/malformed dates and failed requests show retry UI and prevent the picker
or search from using a guessed device date. The API response is non-cacheable.

Tests cover device/server date disagreement in both directions, all picker date
properties, midnight rollover, future selections, invalid calendar dates,
loading/retry/offline behavior and fresh dates on search submission.
