# Call Center API

Implementation branch: `api`. ERP views are a separate work unit on `mix`.
All endpoints below are relative to `/api/web/v1/call-center`, authenticated by
existing Sanctum bearer token. Send `Accept: application/json` and JSON bodies.
Success envelope is `{ "status": true, "data": ... }`. Validation is HTTP 422
with Laravel `{message, errors: {field: [message]}}`; unauthenticated 401,
unauthorized 403, inaccessible/missing call 404. No endpoint changes booking,
payment, rescheduling or refund records.

## Enable access

Run the two `2026_10_13` migrations after deploying backend code. They create
call/audit/review tables and add `ticketing > call-center` to existing company
and role permission trees. Also run
`2026_10_15_000001_add_call_center_passenger_lookup_index` for scoped passenger
history lookup on large ticket tables. Run
`2026_10_15_000002_add_call_center_inquiry_topic` for the optional General
Information topic, and `2026_10_15_000003_add_call_center_complaint_journey`
for the optional complaint journey details. Run
`2026_10_15_000004_add_call_center_complaint_resolution` before enabling the
Complaints tab; it adds closing remarks, closing time and closing agent.
Run `2026_10_16_000001_add_call_center_customer_agreement` before enabling
Refund/Reschedule call recording; it adds five nullable request/response columns.
Phone/CNIC lookup resolves exact customer
matches first, then checks their tickets within the caller's access scope.
Run `2026_10_15_000005_add_call_center_role_privileges` and
`2026_10_15_000006_separate_call_center_permissions` for detailed role controls.
Call Center is a top-level company and role module, independent of Ticketing.
Both the company and role must enable `call-center` and the requested action.
Missing actions deny access. The role must belong to the signed-in user's
company, and hidden users are denied. An active own terminal is required for
recording calls. No cross-company mode is provided.

Company templates make the module available. New roles start with the module
and all 19 action/scope privileges disabled. The upgrade migrates the previous
`ticketing > call-center` entry into its own module, removes the nested entry,
and preserves its effective enabled state (including an old disabled Ticketing
parent). Existing granular denials remain denied. Older enabled module-wide
roles receive their former actions; review remains limited to former supervisors.
A standalone setting always takes precedence over stale nested grants. Repeated
migration or role reads do not restore revoked privileges. The additive privilege
migrations retain configured values on rollback to avoid silently changing access.

## Role privileges

Configure **Users → Roles → Permissions → Call Center**. The independent module
stores `{name: "call-center", allow: boolean, childs: [], buttons: [{name, allow}]}`.
`POST /api/web/v1/role/get` also returns `call_center_privileges`, with a label,
description and group for each action. Saving through `role/update` preserves the
boolean choices, requires Roles + Edit role access, and scopes ordinary admins
to their own company's roles. Fresh role templates do not inherit company grants.

All identifiers below have the prefix `call-center-`.

| Privilege | What it permits |
| --- | --- |
| `view-calls` | Read the call log and details, including complaints, within scope. |
| `create-calls` | Record new calls. Does not implicitly grant lookup, callbacks or follow-ups. |
| `edit-calls` | Edit a call that the role may view. |
| `follow-up` | Create/edit linked follow-ups; requires view-calls and the relevant create/edit action. |
| `manage-callbacks` | Schedule, change or remove reminders while recording/editing. Finishing a call may clear its reminder. |
| `assign-calls` | Assign/reassign other agents, including releasing an existing other-agent assignment. Self-assignment of unassigned calls needs no separate grant. |
| `passenger-lookup` | Search passengers/tickets and link them. A follow-up may inherit its visible parent's link. |
| `view-history` | Read a caller's previous calls and complaints within scope. |
| `quick-answers` | Read fares, departures, seats and terminal details. |
| `view-complaints` | Read the dedicated complaints workspace. Without view-calls, list/detail access is restricted to complaints. |
| `update-complaints` | Append progress remarks without closing a complaint; requires view-complaints. Does not require edit-calls or close-complaints. |
| `close-complaints` | Close complaints with remarks or reclassify them; requires view-complaints. No generic edit grant is needed for dedicated closure. |
| `reopen-complaints` | Reopen or reclassify closed complaints; also requires edit-calls and view-complaints. Reclassification additionally requires close-complaints. |
| `shift-report` | Read/print shift totals. |
| `daily-report` | Read/print daily totals and recorded booking movements. |
| `review-reports` | Save a review; additionally requires the relevant report and supervise. |
| `view-audit` | Include change history on a call the role may view. |
| `supervise` | Extend granted actions/report totals from own/assigned records to permitted team records. Grants no actions by itself. |
| `all-terminals` | Extend granted actions across company terminals; assigned route restrictions still apply. |

The API validates these actions even for direct requests. Parent resolution needs
close-complaints + view-complaints for complaints, or edit-calls for other calls;
failure rolls back the follow-up. Changing a complaint's category cannot bypass
closing remarks or reopen permission. Audit data is omitted without view-audit.
Viewing a call log or caller history intentionally includes complaint records;
view-complaints separately controls the dedicated workspace and complaint actions.


Passenger/ticket access preserves assigned `users.route_ids` (`all` means all
routes, missing/empty assignments mean none), company and terminal boundaries.
Cross-terminal permission does not bypass route assignments. Agents see their
own or assigned calls; supervisor sees all calls in their scope. Summaries count
an agent's own calls only; supervisors see scoped team counts. Assignments do not
change original agent, terminal or business date attribution.

## Options and lookup

- `GET /options`: `data` contains `categories:[{value,label}]`,
  `statuses:[{value,label}]`, `customer_responses:[{value,label}]`, `shifts:[1,2,3]`, `agents:[{id,name,terminal_id}]`,
  `terminals:[{id,name}]`, `current_user:{id,name,terminal_id}`, timezone
  `Asia/Karachi`, and `permissions`. Each privilege above appears without the
  prefix and with underscores (for example `view_calls`, `close_complaints`).
  Compatibility aliases: `view` means module access, `create=create_calls`,
  `update=edit_calls`, `reports=shift_report || daily_report`, and
  `review=review_reports && supervise`. These are server-computed booleans.
- `GET /passengers?search_by=phone|ticket|cnic&q=...`: `data.passengers` is an
  array of `{id,name,contact,cnic_masked,tickets:[...]}`. Complete phone number
  (10+ digits) or 13-digit CNIC is required; common phone/CNIC formatting is
  normalized. Pakistani 0/92 phone prefixes match each other. Ticket search
  accepts only 1–18 numeric digits and matches ticket ID, booking number or invoice exactly and can return multiple
  matches (booking numbers are not globally unique). Results are bounded to
  20 passengers and 25 tickets per passenger; ticket search returns matching
  tickets, including old tickets. Search does not expose raw CNIC.
- Ticket fields: `id,invoice_id,booking_no,seat_no,type,schedule_date,
  schedule_time,seat_fare,terminal_id,terminal_name,departure,destination,
  payment_status,can_link_booking`. `payment_status` is `recorded_booking`,
  `unconfirmed`, or `cancelled`, describing ERP records rather than
  external gateway verification. `tickets.is_partial` means a partial journey and does not alter this status. Only active `type=booked` tickets can be newly linked to BK.

## Calls

- `GET /calls`: `data` is a Laravel paginator with `data:[call],current_page,
  last_page,total,per_page` (25 per page). Optional filters: `date` YYYY-MM-DD,
  `shift` 1/2/3, `category`, `status`, `terminal_id`, `agent_id`, `search`
  (CC-reference, caller phone/name or exact external reference),
  `pending_callbacks=1`, `page`, `queue=today|callbacks_due|open_complaints|awaiting_agreement|all`.
  `awaiting_agreement` contains nonclosed RF/RS calls with No answer and ignores date filters.
  `today` defaults to the current Pakistan business date (an explicit date can
  override it). `callbacks_due` includes nonclosed callbacks due now or earlier,
  oldest callback first; `open_complaints` includes all nonclosed complaints.
  These two queues ignore the date filter so older work stays visible. `all`
  has no implicit date filter. Pending callbacks includes any nonclosed call
  with a callback time. Cross-terminal filters require explicit permission.
- `GET /calls/{id}`: one scoped call with `audit:[{id,actor_id,actor:{id,name},
  action,changes,created_at}]` only with view-audit; inaccessible parent calls cannot be linked.
- `POST /calls`: required `shift,category`; optional `status,caller_name,
  caller_phone,customer_id,ticket_id,seat_count,remarks,assigned_to,callback_at,
  parent_call_id,external_reference,call_date,inquiry_topic,outcome,resolve_parent,
  complaint_from_city,complaint_to_city,complaint_bus_time,complaint_seat_number,
  customer_response,change_seat_count,proposed_refund_amount,proposed_travel_date,proposed_bus_time`. Returns HTTP 201.
- `PATCH /calls/{id}`: same editable fields except `call_date`; only submitted
  fields update. Original agent/date/terminal remain unchanged. Audit stores
  changed fields, previous/new values and actor atomically.
- `GET /calls/{id}/complaint-assignees`: requires view-complaints, edit-calls
  and assign-calls plus access to the complaint. Returns `[{id,name}]` for active
  users with complaint access within its company, terminal and ticket-route
  boundaries, including cross-terminal staff only when their role permits it.
- `POST /calls/{id}/assign-complaint`: same permissions, for an open complaint.
  Send `assigned_to` and `expected_assigned_to` (current user ID, or null if
  unassigned). Under a row lock, rejects a stale responsible person or a closed
  complaint with 409, validates recipient access, changes only responsibility,
  appends the standard assignment audit, and notifies the new assignee.
  Repeating the same current target is a no-op. Extra call fields are ignored.
  Returns the complaint with history metadata and `can_view`: false means the
  previous assignee has handed over their only basis for access; remove that
  card from their view. Original description, notes, callbacks and status stay
  unchanged. No additional migration is required for this assignment feature.
- `GET /calls/{id}/complaint-updates`: requires view-complaints and an accessible
  complaint; returns 25 entries per page, newest first, with
  `{id,kind:update|closed|follow_up|assignment,remarks,created_at,actor:{id,name}|null}`.
  Includes progress updates, responsibility changes, previous closing remarks and linked FU calls,
  including follow-ups to those FU calls. These remain visible after reopening.
  FU entries additionally include `call_id,reference,callback_at`; their `id` is
  `follow-up-{call_id}` to avoid collisions with numeric audit IDs. Their time is
  the original `called_at`, their actor is the recording agent, and callback_at
  is present only while that call has a pending callback. Notes are read from
  the linked call, so edits appear once and old linked records need no backfill.
  Assignment entries read existing `updated` audits containing `assigned_to` and
  include `from_assignee` and `to_assignee` (`{id,name}` or null for unassigned).
  Names are resolved within the current company; deleted users retain a user-ID
  label. Assignment changes from the call log also appear, with no backfill.
  Only FU links are followed; unrelated calls and audit fields are excluded.
  Parent access requires view-complaints and normal own/assigned/team scope.
  Related FU summaries may include other staff's calls within the viewer's
  company, terminal and ticket-route boundaries; this does not grant direct
  access to their full calls. Restricted intermediate links are not traversed.
  View calls and View call change history are not needed for these summaries.
  `page` must be a positive integer. Sorting uses event time descending, then
  audit-before-FU for equal timestamps, then ID descending within the source.
- `POST /calls/{id}/complaint-updates`: requires view-complaints and
  update-complaints. Requires nonblank `remarks` (up to 5000 characters) and
  `request_id` (UUID). Appends an immutable `complaint_update` audit event with
  the server's agent and Pakistan timestamp; leaves description, status,
  assignee, callbacks, call counts and report totals unchanged. Returns the call
  with `latest_complaint_update` and `complaint_history_count`. Retrying the same
  call/request ID and trimmed text returns the saved call without a duplicate;
  reusing an ID for different text returns 409. New updates on closed complaints
  return 409. Scoped call locking serializes updates with closure.
  List/detail responses include the latest progress entry and total history
  count (including permitted linked FU calls) when the user has view-complaints.
  The latest progress entry still refers to a saved progress note. Older closing events already in the
  audit trail appear in history; legacy complaints without audit events still
  retain their existing resolution display.
- `POST /calls/{id}/close-complaint`: requires `resolution_remarks` (nonblank
  text, at most 5000 characters). Applies only to accessible complaints. Closes
  the complaint, clears its callback, preserves the original `remarks`, and sets
  `resolution_remarks,resolved_at,resolved_by` on the server with an audit entry.
  Retries of an already closed complaint return it without changing its closing
  remarks or adding another audit. Company, terminal, route and agent/assignee
  access controls are the same as updating a call. Use `queue=open_complaints`
  for pending work and `queue=all&category=C&status=closed` for closed complaints.

Categories are exactly `GI` General Information (fares/timings/terminal location),
`BK` Confirmed Booking, `C` Complaint, `FU` Follow-up, `RF` Refund and `RS` Reschedule. Statuses are `new`,
`in_progress`, `waiting`, `closed`. GI and BK both permit calls without a passenger
or ticket. If a ticket is linked to BK, it must be an existing active booked
ticket and `customer_id` must match it. BK seats are derived from active booked
seats in its invoice within the viewer's booking scope; the
snapshot stays unchanged when later editing notes or when tickets are cancelled.
Unlinked BK calls count in call totals but contribute zero to verified seat
totals, regardless of a submitted `seat_count` or optional seat-number text.
Removing a ticket link clears its seat-count snapshot. A passenger may be linked
without selecting a ticket, but the existing passenger access scope still applies.
BK is a count of confirmed-booking calls, not unique bookings or sales conversion.
FU requires `parent_call_id` linking an earlier visible interaction. `external_reference`
is a free text complaint/other reference; `reference=CC-{id}` is always server-set.

The simpler form sends `outcome=finish|follow_up|record`. For complaints,
`record` saves the call while keeping it open (or preserving its existing
status). Legacy `finish` also keeps complaints open. Other categories use
`finish` to close the call and clear its callback; `follow_up` sets waiting and requires a complete caller
phone (at least ten digits) and a future `callback_at` in Pakistan time. A missing
assignee defaults to the recording agent. Complaints with an explicit outcome
require notes. Existing clients can omit outcome and retain the legacy status
workflow, but setting a new/open complaint directly to closed is rejected:
use the close-complaint endpoint with closing remarks. Existing closed complaints
can still have their details edited. `record` is supported for complaints and Refund/Reschedule calls, as detailed below.
`inquiry_topic` accepts `fare|timings|terminal|other` or null; non-GI calls
clear it. The current UI requires a GI topic, while the API accepts null for
older clients and records.

Complaints, Confirmed Booking, Refund and Reschedule calls accept four independently optional,
nullable journey details (the existing `complaint_*` field names are retained
for compatibility):
`complaint_from_city` and `complaint_to_city` are city names (up to 120 characters),
`complaint_bus_time` is `HH:mm` in Pakistan time, and `complaint_seat_number` is
text up to 20 characters. Each category can be saved without a linked ticket.
These values describe the reported
journey and do not change the linked booking or its derived seat count. A PATCH
preserves omitted fields; explicit null clears a field. GI and FU clear all four.
Supporting these fields for BK requires no additional migration.
Changes are included in the existing audit and report-review fingerprints.

For a FU call only, `resolve_parent=true` together with `outcome=finish` closes
the linked earlier call and clears its callback in the same transaction. It
creates an audit entry on the earlier call. Omit it to leave that call unchanged.
The parent must be earlier and visible within the same access scope.
If the parent is a complaint, the follow-up's nonblank `remarks` are required
and stored as its closing remarks with closing agent/time. Missing remarks
roll back the entire follow-up save. The close audit retains the follow-up ID.

Server sets `called_at` in Asia/Karachi. `call_date` is a shift business date,
defaulting to today; agents can select today or yesterday for a shift crossing
midnight. Callbacks use `YYYY-MM-DD HH:mm:ss` in Asia/Karachi; if no assignee is
provided they default to the recording agent. No SMS, WhatsApp or phone call is
sent. Agents assigned to a call must also be able to access its linked booking.

Call fields: `id,reference,company_id,terminal_id,agent_id,call_date,called_at,
shift,category,inquiry_topic,status,caller_name,caller_phone,customer_id,ticket_id,seat_count,
remarks,assigned_to,callback_at,parent_call_id,external_reference,created_at,
updated_at,complaint_from_city,complaint_to_city,complaint_bus_time,
complaint_seat_number,resolution_remarks,resolved_at,resolved_by,customer_response,
change_seat_count,proposed_refund_amount,proposed_travel_date,proposed_bus_time,
agent:{id,name},assignee:{id,name}|null,terminal:{id,name}|null`.
Call list/detail and complaint-close responses also include `resolver:{id,name}|null`.

## Caller history and quick answers

- `GET /history?customer_id=...` or `?phone=...` returns
  `{recent_calls:[call],open_complaints:[call],open_complaint_count:number}`.
  History is limited to five recent calls and ten open complaints; the count
  includes all visible open complaints. Linked-customer and matching-phone
  calls are combined. Customer IDs require an accessible ticket. Phone lookup
  also supports callers without bookings, normalizes 0/92 prefixes, and always
  preserves company, terminal, route and agent scope.
- `GET /quick-answers/options` returns `{cities:[{id,name}],
  terminals:[{id,name,city_id,address,contact}]}` using visible company cities
  and permitted terminals.
- `GET /quick-answers?departure_id=...&destination_id=...&date=YYYY-MM-DD&terminal_id=...`
  requires different visible company cities and today or a future travel date.
  Terminal defaults to the user's own; broader access needs the explicit
  permission. Returns `{departures:[{id,schedule_name,bus_class,departure_at,available_seats}],
  fares:[{class_name,base_fare}],terminal:{id,name,address,contact},checked_at,
  fare_note,schedule_note}`. Schedules honor company, assigned routes, terminal
  visibility, hidden/deleted/dropped services and terminal time offsets. Only
  future departures are returned (up to 100 candidate schedule details).
  `available_seats` is a nonnegative integer, or null when the seat map/journey
  cannot be verified. Counts use the departure's actual bus-class seat map and
  original service `schedule_date`, including overnight journeys. Nondeleted
  occupied tickets from all terminals in the same company are counted once per
  physical seat, only when their journey overlaps the requested leg. Booked,
  reserved and blocked seats are unavailable; soft-deleted cancellations do not
  occupy seats. A seat can be reused after a nonoverlapping leg. Base-map
  `not_for_sale` seats and terminal seat restrictions (when the user's
  `check_allowed_seats` is enabled) are respected. Occupancy queries expose no
  passenger data and do not reserve or lock seats. Recheck before booking.
  No additional database migration is required for availability.
  Fares come from the existing fare table and are omitted if there are no
  visible departures. These are base fares, not a final quote; existing booking
  rules determine discounts, surcharges and seat availability. No booking or
  financial record is changed.

## Reports and review

- `GET /summary?date=YYYY-MM-DD&shift=1&terminal_id=...`: shift and terminal are
  optional. `data={rows:[{agent_id,agent_name,shift,GI,BK,C,FU,RF,RS,total,seats}],
  totals:{GI,BK,C,FU,RF,RS,total,seats},reviews:[review],snapshot_tokens:{1:hash,2:hash,3:hash}}`. Seats counts BK seats only. Tokens for shifts excluded by the shift filter are null; load that shift or all shifts before reviewing.
- `GET /daily-report?date=YYYY-MM-DD&terminal_id=...`: same rows/totals plus
  `complaints:{closed,pending},followups:{closed,pending},ratios:{booking,
  general_information},seat_movements:[{terminal_id,terminal_name,shifted,
  refunded}],seat_movements_note,review:review|null`. Ratios are category call
  count / total calls *100, or 0 when empty. Pending means any nonclosed status
  among calls recorded on the selected business date, not a global backlog.
- `POST /shift-review`: requires review-reports, supervise and shift-report, `{date,shift,terminal_id?,remarks?,snapshot_token}`.
- `POST /daily-review`: requires review-reports, supervise and daily-report, `{date,terminal_id?,remarks?,
  next_day_actions?,snapshot_token}`. Both return a review with `id,date,shift,terminal_id,
  remarks,next_day_actions,reviewed_at,reviewer:{id,name},stale`.

Review terminal defaults to own terminal for restricted supervisors and 0
(company/all permitted terminals) for cross-terminal supervisors. Daily review
uses shift=0. Review POST requires the 64-character snapshot token from the displayed daily report or selected shift. If data changed since that report loaded, HTTP 409 returns `Report changed. Reload it before reviewing.` The provided token is saved, so an update arriving during review still marks the result stale. Response tokens hash the exact returned report rows, including when another call arrives while the report is loading. Review fingerprints the actual call contents and (daily) recorded
seat movement totals; subsequent changes mark review `stale=true` until reviewed
again. Regular agents do not receive broader supervisor certifications.
Category/status/agent/queue filters are prohibited for reports; daily reports also
prohibit shift filters so a review always refers to the full indicated scope.

Seat movements are existing ERP events on the selected calendar date, not
manually entered call counts. Shifts use nondeleted `ticket_reschedules` audit
rows joined to old tickets, by `created_at` and issuing terminal. Recorded
refunds use nondeleted `booking_cancels` rows with original `type=booked`, a
soft-deleted `type=canceled` ticket and positive `tickets.refund_amount`. Counts
are distinct ticket IDs. These show recorded refunds, not financial settlement.
Legacy records without a recorded refund amount and pending cancellation
requests are excluded. Ordinary agents see only movements they performed;
supervisors see all permitted movements. Existing transactions remain authoritative.

## Validation and rollout

Run isolated feature tests with SQLite memory:
`DB_CONNECTION=sqlite DB_DATABASE=:memory: php vendor/bin/phpunit --filter CallCenterTest`.
The tests create only a minimal in-memory legacy schema plus the new migrations;
they do not migrate or query a development/production database. Deploy backend
and run migrations first, explicitly grant roles, then deploy the separate mix
views. The local preview on 15 October 2026 uses these migrations against the
local imported database, with Call Center and supervisor access granted to the
local Admin role. Cross-terminal access remains disabled. Production rollout
is still separate; no production database was changed.

Verified on 15 October 2026: the full PHPUnit suite passed with 56 tests and
494 assertions, including 46 Call Center tests. New coverage checks outcomes,
callback requirements, date-independent queues, scoped history, explicit parent
resolution, permitted Quick Answers data, per-service seat counts, partial and
overnight journeys, terminal seat restrictions, sold-out and unknown counts,
and optional complaint/confirmed-booking journey create/read/edit/clear,
validation and auditing, without altering tickets or derived booking seat counts.
Unlinked Confirmed Booking coverage verifies create/edit/callback, optional
customer links, linking/unlinking tickets, zero verified seats and customer scope.
Complaint closure coverage verifies open-on-record, required closing remarks,
original-description retention, actor/time, callback clearing, duplicate retries,
permission boundaries, follow-up rollback/link auditing and stale report reviews.
A read-only local HTTP check returned verified counts for all seven Rawalpindi
to Karachi departures on the imported database.

Detailed privilege verification includes action denials despite supervisor scope,
complaint-only read access, create-only access, callback/assignment/linking checks,
closure and reopening bypass attempts, separate report and review permissions,
company-level overrides, role save/reload, company boundaries and idempotent
migration of legacy grants. Call Center functions with Ticketing disabled.
The local imported database has both privilege migrations applied; production
rollout still requires the API migrations and matching mix assets.

Files for detailed access: `app/Support/{CallCenterPermissions.php,CallCenterScope.php}`,
`app/Http/Controllers/RoleController.php`,
`app/Http/Controllers/Api/{CallCenterController.php,CallCenterInformationController.php}`,
`database/migrations/2026_10_13_000002_add_call_center_permissions.php`,
`database/migrations/2026_10_15_000005_add_call_center_role_privileges.php`,
`database/migrations/2026_10_15_000006_separate_call_center_permissions.php`,
`tests/Feature/CallCenterTest.php`, and this document.

## Refund and reschedule customer calls (16 October 2026)

The user-selected workflow records agreement and requests in Call Center;
Ticketing remains responsible for executing seat changes and payments. No new
booking, cancellation, reschedule or payment endpoint is called by this workflow.
`create-calls` / `edit-calls` and all existing visibility restrictions apply.
RF/RS use the existing Record calls and Edit calls privileges; no separate financial-action privilege is granted.

RF/RS require `customer_response=agreed|declined|not_reached`. This records the
agent's report of the conversation. `change_seat_count` is an integer from 1 to
100, defaults to 1, and is separate from the verified BK `seat_count` (RF/RS store
zero there). Each call may be saved without linking a ticket or passenger.
Optional linked records are checked against the usual company, terminal and route
scope. Lookup is required to newly link records, except inheriting a visible
parent's existing link in a permitted follow-up.

RF supports nullable `proposed_refund_amount` (0 to 9,999,999,999.99, at most two
decimal places); RS supports nullable `proposed_travel_date` (YYYY-MM-DD) and
`proposed_bus_time` (HH:mm, Pakistan time). These are discussed proposals, not
completed transactions. Opposite-type proposal fields are cleared, and all five
agreement fields clear when switching away from RF/RS. Switching into a different
request category requires an explicitly supplied customer response. Changes are
included in the existing call audit and report snapshot.

`outcome=record` or finish closes an agreed/declined call; No answer stays waiting
and appears in the date-independent `awaiting_agreement` queue. `follow_up` keeps
the existing complete-phone/future-callback requirements. A new RF/RS follow-up
uses the same category and `parent_call_id`, needs create + follow-up + view-calls
+ edit-calls, requires a fresh response, and preserves the parent's passenger and
booking when linked. The new call atomically closes an earlier unanswered attempt
and clears its old callback, retaining the earlier No answer response and call date.
A second No answer leaves only the new attempt pending. Generic FU closure cannot
hide an unanswered RF/RS request. Notes edits do not reopen superseded attempts.

Both summary and daily-report include `customer_agreements`:
`{refund:{calls,agreed,declined,not_reached,agreed_seats},reschedule:{...}}`.
These totals use the report's existing date/agent/shift/terminal scope and count
conversation records; agreed_seats sums the discussed seat counts on agreed calls.
Repeat calls can discuss the same seats. RF and RS also appear in category totals,
so total calls is GI + BK + C + FU + RF + RS. Completed ERP seat movements remain
separate and are never increased by recording an agreement.

Validation on 16 October: the isolated suite passed with 65 tests / 643 assertions
including the permission checks for new follow-ups. New coverage includes agreement and
proposal validation, optional proposal fields, action/booking boundaries, daily
and shift totals, unchanged Ticketing tables, fresh-response follow-ups, failed
transaction rollback and report review staleness. The migration is applied only
to the local imported preview database.

## Complaint progress updates (16 October 2026)

Deploy the API and run `2026_10_16_000002_add_call_center_complaint_updates_permission`
before the matching complaints screen. Progress notes use the existing append-only
`call_center_audits` records, so no new table or column is required. There are now
19 granular Call Center privileges. The additive permission migration makes
Add complaint updates available to companies, enables it for existing roles with
both View complaints workspace and Close complaints, and preserves explicit
grants/denials. Other existing roles and new roles start denied; administrators
can enable Add complaint updates independently in Roles. Rerunning the migration
never restores a revoked grant.


## Complaint assignment notifications

Run `php artisan migrate --path=database/migrations/2026_10_16_000003_create_call_center_notifications_table.php --force`
after the existing Call Center migrations, before serving the new API. The new
`call_center_notifications` table stores recipient, assigning user, complaint,
creation/read time and superseded time; no existing complaints are rewritten or
backfilled with alerts. The migration has no permission changes.

Recording or reassigning an open complaint to another user creates a notification
inside the same transaction as the call and audit. Ordinary saves, self-assignment
and other categories do not notify. Previous alerts for that complaint are retired
on reassignment, removal of the assignee or category change, so they cannot
reappear if a user is later assigned the complaint again. Assignment now requires
the recipient to have View complaints workspace permission; existing terminal,
route, company and assignment-action checks still apply.

- `GET /notifications?page=1`: requires module + `view-complaints`; returns
  `data: {unread_count, notifications: <paginator>}`. Pages contain 20 items,
  newest first. Each item has `id, call_id, reference, message, created_at, read_at`.
  The unread count covers all pages. Only the signed-in recipient's current
  assignments within their live company/terminal/route scope are exposed.
  Closed complaints remain accessible from earlier notifications.
- `POST /notifications/{id}/read`: same access checks; marks only the signed-in
  recipient's visible notification read, retaining its first read time on retries.
  Returns `data: {id}`; unavailable/other-user notifications return 404.

These are database-backed website notifications, independent of email, browser
push permission, workers or a mobile app. Reading an alert does not close or
otherwise change its complaint.


## Linked follow-up history deployment

Run `php artisan migrate --path=database/migrations/2026_10_16_000004_add_call_center_parent_lookup_index.php --force`
after the previous Call Center migrations. This adds only a lookup index on
company/parent/category for linked conversations; no records or role grants change.
Deploy the combined-history API on `api` with the corresponding `mix` display.
Older linked FU calls appear immediately. The history query does not create
additional calls or copy notes into audits, and does not change complaint status,
callback scheduling, assignment or reporting. History discovery batches complaint
roots and traverses indexed parent links instead of scanning all company calls.


## Complaint workflow release — 16 October 2026

This release includes progress updates, combined complaint/follow-up/assignment
history, assignment notifications, and reassignment from the complaint workspace.
Deploy `api` before serving the matching production bundle from `mix`.
For installations already running the earlier Call Center/customer-agreement
migrations, apply these pending migrations in order using the deployment's
existing database configuration:

```bash
php artisan migrate --path=database/migrations/2026_10_16_000002_add_call_center_complaint_updates_permission.php --force
php artisan migrate --path=database/migrations/2026_10_16_000003_create_call_center_notifications_table.php --force
php artisan migrate --path=database/migrations/2026_10_16_000004_add_call_center_parent_lookup_index.php --force
```

These changes preserve existing calls and history. A fresh installation also
needs the earlier Call Center migrations documented above. Assignment itself
adds no further migration. Review Call Center role privileges after deployment;
workspace assignment uses View complaints, Edit calls and Assign calls.

Release validation: 82 backend tests / 938 assertions and PHP syntax checks passed.
The companion frontend passed 24 tests and a production Mix build. Local MySQL
verification covered mixed history and complaint assignment/notifications inside
transactions that were rolled back; browser fixtures verified successive
handovers while preserving updates and linked follow-up history.
