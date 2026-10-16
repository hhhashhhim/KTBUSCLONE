# Call Center ERP Views

Implementation branch: `mix`. The matching backend is implemented separately on
`api`; deploy that backend and its call-center migrations first.

## Access and screens

After signing in, company users with the independent `call-center` module enabled see a
**Call Center** menu. Granted privileges determine which screens appear:

- `/call-center`: call log, passenger lookup, new calls, updates and callbacks.
- `/call-center/complaints`: open complaints, closing remarks and closed history.
- `/call-center/shifts`: agent/shift totals and in-charge reviews.
- `/call-center/daily`: daily totals, complaint/follow-up outcomes, recorded seat
  movements, operational remarks and next-day actions.

The existing configured `MIX_API_URL_ROUTE` prefix applies to these routes.
`MIX_API_URL` supplies the backend base; the existing shared client appends
`public/api/web/v1/`. No credentials or production URLs are embedded here.
Permission/validation errors are shown in the workspace. The backend enforces
company, terminal, route and agent scope for all actions.

**Users → Roles → Permissions → Call Center** is a separate top-level section;
Ticketing can remain disabled. Its 18 checkboxes have plain-language labels,
short explanations and five groups: Calls, Passenger assistance, Complaints,
Reports and audit, and Access scope. Definitions come from `role/get` so UI
labels and the API contract stay aligned. New roles default to no privileges.

The UI uses authoritative `/call-center/options` permissions. It hides actions
that are not granted and avoids loading forbidden resources. Create-only roles
can record calls without loading the call list. Read-only roles can open calls
without save controls. Complaint-only roles land directly on Complaints;
report-only roles land on their first permitted report. A direct URL to a denied
screen displays Access not granted and does not fetch that screen's data.

`call-center-supervise` extends granted actions to team records; report reviews
also require `call-center-review-reports` and the relevant report permission.
`call-center-all-terminals` enables broader terminal scope. Neither scope flag
grants actions. Agents normally see their own/assigned calls and own report totals.
The sidebar refreshes access with the API so refreshed pages pick up role changes.
Both company module availability and role grants are enforced by the backend.

Before deploying these views, apply API migrations
`2026_10_15_000005_add_call_center_role_privileges` and
`2026_10_15_000006_separate_call_center_permissions`. They preserve existing access
while moving Call Center out of Ticketing. No frontend/backend dependency is
pending in the local preview; production deployment is separate.

## Recording calls

Choose the shift once per browser tab/session; only that preference is stored,
keyed to the signed-in user. Agent and time are automatic. The shift date is
collapsed and can be changed to yesterday for overnight work.

One search box accepts a complete phone, ticket/booking/invoice number, or
13-digit CNIC. When an unformatted number could be a phone/CNIC or ticket,
choose its type before searching. Formatted input is detected directly.
Starting a phone search immediately copies that number into Caller details and
opens that section. This also works if lookup fails or finds no passenger.
Another phone search updates the caller number; ticket/CNIC searches do not.
Selecting a passenger preserves an existing caller phone, so a passenger's stored
contact does not replace the number used for this call. The number is persisted
with the call when **Save & finish** or **Save follow-up** succeeds. CNIC
results are masked. Passenger and booking results show only accessible records.

The six category buttons reveal the necessary fields:

- **General Information:** Fare, Timings, Terminal location or Other; notes and caller
  details are optional, and no passenger selection is required.
- **Confirmed Booking:** record the call with or without linking a passenger
  or ticket. Lookup and ticket selection are optional; no match still permits
  saving. When a ticket is linked, it must be an active confirmed booking. The
  backend counts linked seats; unlinked calls contribute zero to seat totals.
  Removing a ticket link clears its counted seats. Saving records
  the support call, not a sale or external payment verification. The same
  **Journey details (optional)** section as Complaint offers From city, To city,
  Bus time and Seat number. Selecting a ticket fills empty fields; each remains
  editable and can be cleared. Journey details are stored with the call and do
  not change the booking or the counted number of seats.
- **Complaint:** describe the issue and choose a responsible person, defaulting
  to yourself. **Journey details (optional)** provides From city, To city,
  Bus time (Pakistan time) and Seat number. Any or all can be left blank.
  City suggestions use permitted company cities; names can also be typed.
  Selecting a ticket fills empty journey fields while preserving manual entries.
  These details save with the complaint, remain editable when reopened and
  appear in work queues, caller history and the linked follow-up context.
  **Save complaint** keeps it open and shows a link to the **Complaints** tab.
- **Refund / Reschedule:** choose Agreed, Declined or No answer and Save call.
  Seats default to 1. Refund amount, new travel date/time, ticket linking and
  journey details are optional. Journey details start collapsed for these calls.
  The response records the conversation; Ticketing processes the actual change.
- **Follow-up:** choose an earlier call by its reference/phone, or use its
  **Follow up** action in a queue/history. An optional checkbox also resolves
  that earlier call when the follow-up is finished.

For other categories, **Save & finish** closes the call and clears its callback. **Save for follow-up**
first reveals callback details; enter the phone, responsible person and future
Pakistan time, then save. Success clears the form for the next caller and keeps
the shift. Errors retain the draft and show field-specific messages. Starting
another call or leaving the call log prompts **Keep editing / Discard changes**;
refresh/close uses the browser's unsaved-work protection. Drafts remain in memory
and are not persisted across a discarded page reload.

Selecting a passenger or entering a complete phone loads recent conversations
and unresolved complaints within the agent's permitted scope. Unresolved
complaints appear first and can be followed up directly.

## Complaints

With View complaints workspace, the **Complaints** header tab opens all accessible open complaints, including
older dates. The original description, caller, journey details and responsible
person appear beside a **Closing remarks (required)** box. Type what was done,
then click **Close complaint** once; no confirmation step is added. The button
is unavailable for blank remarks or while saving. A failed request retains the
remarks for retry. Leaving with unsaved remarks prompts Keep editing / Discard
changes. The **Closed** tab retains the description and closing remarks with
who closed it and when. Existing closed complaints without resolution records
remain visible as older records; no historical status is changed automatically.

Without Close complaints, this workspace is read-only. With that privilege,
closing requires remarks but does not require generic Edit calls. The Closed
list offers **Reopen complaint** only with both Reopen complaints and Edit calls.
Closing clears callbacks and updates reports. If a follow-up explicitly resolves
an earlier complaint, its notes are required and become that complaint's closing
remarks. The backend enforces the same permission scope and remarks requirement.
Deploy the new API and run `2026_10_15_000004_add_call_center_complaint_resolution`
before deploying these views. The local imported preview database is migrated.

## Work queues and quick answers

**Today's calls**, **Callbacks due**, **Open complaints** and **All calls** are
one-click queues. Due callbacks and open complaints include older dates. Search
is always available; date/shift/category/status/terminal options are under
**More filters**, as applicable to the selected queue.

The collapsed **Quick answers** panel uses existing city, schedule, fare and
terminal records. Select From, To and travel date to view permitted departures
and base fares. Every departure shows its available-seat count, including zero;
missing/unverifiable counts display **Seat count unavailable**. Counts apply to
the chosen journey and permitted seats, and refresh with **Check fares,
departures & seats**. Terminal addresses and phone numbers have a separate disclosure.
Results display the checked route/date and time so old results are identifiable
when inputs change. Availability is a snapshot at that check time. Final seat
confirmation, discounts and surcharges remain in the existing booking workflow. Recording a callback does not place a call or
send a message.

## Reports

Counts and ratios come from the backend. GI + BK + C + FU + RF + RS equals total calls.
The booking ratio is the share of calls in the Confirmed Booking category.
Seats attached to booking calls may recur across multiple calls; these are not
unique seats sold. Complaint/follow-up outcomes describe current statuses of
calls logged on the selected business date.

Seat changes use recorded ERP events; **Recorded refunds** does not certify
financial settlement. The backend returns the inclusion rules below the table.
Review saves include the snapshot token returned with the report actually
viewed. If report data changes before saving, the backend rejects the review;
select **View report** to reload and review the updated totals. A missing token
also blocks saving. Existing reviews become stale when report data changes.
Use **Print A4** for a portrait report with the ERP navigation and forms hidden.

## Verification

Run `node --test tests/frontend/call-center.test.mjs` for timezone, callback,
permission, ambiguous search detection, queue parameters and API-envelope/error checks. Run `npm run development` or
`npm run production` for the full existing Vue/Laravel Mix build. Existing Sass
deprecation and Browserslist warnings are unrelated to these screens.

Completed checks: all ten Node helper tests passed, and the full development
build compiled successfully. Browser verification against a local HTTP shell
with API fixtures covered phone/ticket/CNIC lookup, all four categories,
create/edit, linked follow-ups, callbacks across dates, backend-derived booking
seat payloads, shift/daily review saves with their displayed snapshot tokens,
changed-report HTTP 409 rejection followed by reload/save recovery, validation recovery, retry after an
outage and reload, responsive layout without page overflow, and A4 print layout
with navigation hidden. No uncaught browser errors were observed; desktop and
print screenshots were visually inspected.

The usability update was additionally checked in the browser for failed-save
draft retention, remembered shift, the in-page unsaved-changes dialog, ambiguous
phone lookup, booking selection, complaint validation, callback creation,
caller history, linked resolution, Quick Answers and all three search types.
The updated form was visually checked at desktop and 390px width, with no
horizontal page overflow. No uncaught browser errors were observed. The browser checks used
fixture responses, not production records. Read-only authenticated HTTP checks
against the local imported database confirmed history, both work queues and
Quick Answers return successful responses; live schedules and base fares were
returned for a permitted journey.
Backend business rules and access isolation belong to the separate API feature
tests. Deployment still requires the matching API and explicit role grants.

The seat-count update was checked in the browser with available (23), zero
and unknown values; each appeared beside the correct departure with no console
errors. The full Vue build passed. It requires the API response addition above,
with no new migration. The local preview uses the updated API and bundle.

Phone-copy verification: the Vue build and nine existing helper tests passed.
Browser fixture checks confirmed automatic caller details, copying after no
match or lookup failure, and retaining the latest searched phone in the saved
call payload after a failed save/retry. No backend change is required.

Complaint journey verification: API tests cover optional fields, independent
entry, validation, persistence, edits, clearing and audit history. Browser
fixture checks covered saving with all fields blank, ticket autofill, manual
journey entry, reopening and editing a seat number. The added Node helper test
covers converting ticket times to the time input format. The full Vue build
passed. Deploy the API and run
`2026_10_15_000003_add_call_center_complaint_journey` before these views; the local
imported preview database has already been migrated.

Confirmed Booking now uses the same optional journey section. Browser fixture
checks verified ticket autofill, editing a seat number, failed-save draft
retention and reopening the saved values. The ten helper tests and full Vue
build passed; no browser errors were observed. This requires the updated API
to retain journey details for BK, but no additional migration. Both the local
API and preview bundle are updated.

Ticket linking is now optional for Confirmed Booking. The search label and
no-match message reflect this. Browser fixture verification reproduced a phone
search with no match, saved the call after a simulated transient failure, and
reopened it with no passenger or ticket linked. Caller phone was retained and
all four journey fields could remain blank. The ten helper tests and Vue build
passed, with no browser errors. The API tests cover optional linking and report
seat counts. No new migration is required; the local API and bundle are updated.

Complaints tab verification: a browser fixture call saved with `outcome=record`
appeared automatically under Open. The close button remained disabled without
remarks; navigation protected a draft, and a simulated failed close retained
remarks for retry. One successful click moved it to Closed with original and
closing remarks, actor and time preserved. Desktop layout was inspected, with
no uncaught browser errors. The ten helper tests and full Vue build passed.
The matching API suite passed 46 tests / 390 assertions. The new complaint
resolution migration is applied only to the local imported preview database.

## Detailed privileges verification

Verified: 11 frontend helper tests, successful Laravel Mix build, and browser
checks of the independent role section with Ticketing disabled. Saving and
reloading a complaint-only role preserved exactly those choices, redirected to
Complaints, hid closing controls and blocked direct access to Daily report.
Enabling Close complaints revealed required remarks; a failed request retained
the draft and retry closed the test record. Closed records offered no reopen
action without the separate grant. Browser checks used disposable fixture data.
Authoritative API permission/migration tests passed separately on `api`.

Recording-only browser verification also passed: the form fills the workspace,
with lookup, history, follow-up, work queues and reports absent, and a general
information call saves successfully without fetching a call list.

Files for detailed access: `resources/js/pages/roles/Permissions.vue`,
`resources/js/pages/callCenter/{CallCenterPage.vue,CallLog.vue,Complaints.vue,callCenter.js,callCenter.css}`,
`tests/frontend/call-center.test.mjs`, and this document.

## Customer agreements for refund and reschedule calls

Deploy the matching API and apply
`2026_10_16_000001_add_call_center_customer_agreement` before these views.
The local preview has the migration and both branches connected.

Choose Refund or Reschedule, optionally enter the discussed amount or new journey,
choose the customer's response and Save call. Missing response is highlighted;
choosing one clears that error. Changing a proposal clears the selected response
so it can be recorded for the revised terms. The queue/history cards show the
response and proposal, and report category tables include RF and RS.

No answer stays in **Awaiting agreement**, including older dates. **Follow up**
opens a new call of the same request type, carries forward the proposal and asks
for a fresh response. It needs create, view-calls, follow-up and edit-calls.
The new record keeps its date/agent and links to the earlier attempt. The earlier
unanswered record is closed without changing its response, so the queue shows
only the latest outstanding attempt. Passenger/booking selection stays attached
to that earlier request. A failed save keeps the draft for retry.

Both reports show **Refund & reschedule calls**, grouped by response and seats
agreed. The daily report's separate **Completed seat changes in Ticketing** table
continues to count actual ERP events. Recording agreement never increases those
completed counts or changes a ticket/payment.

Verified with 13 helper tests, a successful Mix build and disposable browser
fixtures: required response, refund proposal, save failure/retry, unanswered
reschedule queue, proposal-carrying follow-up, clearing outstanding work and six
category report totals. The authoritative API tests separately verify permissions
and that Ticketing tables do not change.
