# Bus search cards

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
