# Hotel / Travel Eye: portal bookings

Confirmed (`booked`) tickets created or confirmed through the portal booking
controller are submitted after the transaction commits and after the HTTP
response is sent, via `VerifyTravelEyeTicket::dispatchAfterResponse`.
Unpaid reservations are not submitted until confirmed. Cancelled/deleted tickets
and terminals without credentials are not sent. This change does not add calls
from third-party booking controllers or perform bulk historic submissions.

## Terminal settings and request

The **ticket's booking terminal**, not the operator's default terminal, supplies:

- `usrName`: Smarteye ID.
- `usrPass`: Smarteye API value (not the web-login Password field).
- `terminal`: the terminal city's name.
- `terminalName`: the terminal's name.

The request is POST multipart/form-data to
`https://hoteleye.punjab.gov.pk/TravelEyeApiPost/verify`, matching the supplied
Postman example. CNIC and passenger phone are digits-only. Departure time is
`YYYY-MM-DD HH:MM:SS`. Passenger, journey, seat, company, bus and assigned driver
data come from the ticket and its related records; optional unknown fields are
not invented.

Every newly submitted ticket has a voucher `KT-C{company_id}-T{ticket_id}`.
Tickets sharing an invoice, departure or seat number still get different
vouchers. Retrying the same ticket retains its voucher. Already successful
historic submissions are not automatically resent or renamed in local logs;
their old voucher still identifies the existing remote record.

## Delivery and diagnosis

The existing after-response delivery mechanism is retained; this is not a
durable queue and does not introduce a new worker/cron requirement. Prompt
browser response flushing depends on the web server's FastCGI/response buffering
configuration. Outbound connections have a 5-second connect timeout and the
configured overall timeout (`TRAVEL_EYE_TIMEOUT`, default 15 seconds).

`travel_eye_api_logs` records ticket ID, voucher, status, attempts, HTTP status,
response and missing-field/transport failures. Success requires both HTTP 2xx
and a true API `status`; HTTP 200 alone is not confirmation. Retries skip a
ticket already logged as successful. A failed request does not undo or modify
the booking. Unexpected job errors are caught and recorded without credential
values. Request logs omit credentials and response/error text redacts them.

No automatic retry or live passenger submission is performed by the tests.
Before resending historical failures, inspect their remote state to avoid
duplicating records, especially those previously submitted using shared vouchers.

## Deployment

Pull the API branch and clear cached configuration if terminal/API environment
settings changed. No frontend build or new migration is required for this fix.
The existing `travel_eye_api_logs` table and terminal Smarteye settings migration
must already be installed. Tests use an isolated in-memory database and fake
HTTP responses; they never contact the government API.
