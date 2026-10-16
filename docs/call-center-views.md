# Call Center ERP Views

Implementation branch: `mix`. The matching backend is implemented separately on
`api`; deploy that backend and its call-center migrations first.

## Access and screens

After signing in, company users with the independent `call-center` module enabled see a
**Call Center** menu. Granted privileges determine which screens appear:

- `/call-center`: call log, passenger lookup, new calls, updates and callbacks.
- `/call-center/complaints`: open complaints, progress updates, closing remarks and history.
- `/call-center/shifts`: agent/shift totals and in-charge reviews.
- `/call-center/daily`: daily totals, complaint/follow-up outcomes, recorded seat
  movements, operational remarks and next-day actions.

The existing configured `MIX_API_URL_ROUTE` prefix applies to these routes.
`MIX_API_URL` supplies the backend base; the existing shared client appends
`public/api/web/v1/`. No credentials or production URLs are embedded here.
Permission/validation errors are shown in the workspace. The backend enforces
company, terminal, route and agent scope for all actions.

**Users → Roles → Permissions → Call Center** is a separate top-level section;
Ticketing can remain disabled. Its 19 checkboxes have plain-language labels,
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

With View complaints workspace, the **Complaints** header tab opens all accessible
open complaints, including older dates. The original description, caller, journey
details and responsible person appear beside one **Update / closing remarks
(required)** box. It offers independent actions according to role privileges:

- **Save update** (Add complaint updates): saves a progress note and keeps the
  complaint in Open, preserving its exact status, assignee and callback.
- **Close complaint** (Close complaints): saves the text as closing remarks,
  clears the callback and moves the complaint to Closed with one click.

Both require nonblank remarks. The box clears only after a successful save;
failed requests retain the draft. Updates carry a UUID per draft so retrying an
uncertain response cannot duplicate the note. Editing the text starts a new
request. Both buttons are disabled during a save. The default form submission
saves an update when available; closing remains an explicit action. Leaving
with unsaved remarks prompts Keep editing / Discard changes.

The latest progress update appears below the original complaint, with the agent
and Pakistan date/time. **View history** expands progress updates, linked follow-up
calls and previous closing remarks, newest event first, 25 at a time, with More
history and a retry option. A **Follow-up call · CC-number** entry shows the
conversation notes, recording staff member, call date/time and next callback if
still scheduled. Older linked calls and follow-ups to those calls appear too.
The notes are read from the call itself, so staff do not need to copy them into
Save update. Editing the follow-up updates its entry without adding a duplicate.
A complaint with only linked follow-ups also offers View history.
The original description and earlier notes are never replaced by progress notes.
Readers can see this curated history without the separate audit privilege;
unrelated changes and retry IDs are not exposed. Closing remarks and prior notes
remain visible under Closed, and previous closing events survive reopening.
Existing older complaints without resolution/audit records remain visible.

Without Add complaint updates or Close complaints, this workspace is read-only.
Neither action requires generic Edit calls. The Closed list offers **Reopen
complaint** only with both Reopen complaints and Edit calls. A follow-up that
explicitly resolves an earlier complaint still requires notes and records them
as the closing remarks. Access to the complaint retains normal company,
terminal, route and agent/assignee scope. Its related FU summaries can include
other staff's linked calls within the viewer's company/terminal/route boundaries;
this does not grant direct access to those staff's full or unrelated calls.
Reading history requires View complaints workspace; View calls and the separate
audit privilege are not needed for these conversation summaries.

Deploy the API and run
`2026_10_16_000002_add_call_center_complaint_updates_permission` before these
views. This uses the existing audit table and adds one independent role grant.
Existing complaint closers receive it unless already explicitly denied; new
roles and other roles start denied. Configure it under Users → Roles →
Permissions → Call Center → Add complaint updates.

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


## Website complaint notification bell

A green bell beside the user menu appears across company/admin website screens
after the notification API confirms Call Center + View complaints workspace
access for the signed-in user. Login does not include company modules, so the bell
does not depend on cached browser permission data. It displays the unread assignment count and a dropdown of dated
notifications. The website checks every 30 seconds while visible and the dropdown
is closed, on returning to the tab, and when opening/refreshing the dropdown.
Older notifications can be loaded with More notifications. Network errors offer
Retry; revoked access clears and hides the inbox. Subsequent checks can restore
the bell if access is granted again, without requiring a new login.

Selecting an assignment opens `/call-center/complaints?complaint=<call_id>&notification=<id>`.
The workspace fetches that exact complaint, even if it was closed or lies outside
the first list page, then marks the notification read and refreshes the bell.
Failed/blocked navigation does not mark read. Existing unsaved-remarks protection
also applies between complaint links; All complaints returns to the normal queue.
Reading an alert does not close the complaint. A read-state failure offers retry
without blocking work on the opened complaint.

Backend dependency: the notification endpoints and
`2026_10_16_000003_create_call_center_notifications_table` migration on `api` must
be deployed before the new `mix` bundle. No browser notification permission,
mobile app, email configuration or background worker is required.


The linked-follow-up display also needs the combined-history API and
`2026_10_16_000004_add_call_center_parent_lookup_index` migration. That migration
adds only an index, with no new notes storage or permission changes. Saving a
follow-up counts one call and keeps the original complaint open unless the agent
explicitly chooses to resolve it. No extra Save update step is needed.


### Changing complaint responsibility

On an open complaint, use **Change** beside Responsible, choose the next person,
and click **Assign**. This needs View complaints workspace, Edit calls and Assign
calls. The list loads eligible recipients for that specific complaint from
`GET calls/{id}/complaint-assignees`, respecting company, terminal and route access.
`POST calls/{id}/assign-complaint` sends only the selected person and the expected
previous person. A stale assignment or closed complaint is rejected; Refresh
loads the latest details. Failed saves retain the selected person for retry.
Save any drafted remarks before handing over; other complaints' drafts remain.

A successful handover opens **View history**, showing Responsibility changed,
previous/new person, actor and Pakistan time alongside updates and follow-up
calls. Existing assignment audits from the call log appear too. Original notes,
status and callbacks are preserved. The new recipient gets the existing bell
notification (self-assignment does not notify); the previous alert is retired.
If handing over removes the user's access, the card leaves their list with a
confirmation message. Closed complaints must be reopened before assigning here.
Deploy with the matching API endpoints/history response; this feature adds no
new migration or role privilege. Existing complaint notification migrations
still apply.


### Production build — 16 October 2026

The committed `public/js/app.js` was regenerated with `npm run production`, using
`MIX_MAIN_URL=https://portal.kainattravels.net/`,
`MIX_API_URL=https://api.kainattravels.net/` and `MIX_API_URL_ROUTE=/`, matching the
previous production bundle. Rebuild with the target deployment's own base URLs
when installing on another host. Environment files and local preview settings
are not part of the release. Existing Sass deprecation warnings do not prevent
compilation.

The 24 frontend tests and production build passed. Deploy the matching `api`
changes and its three pending complaint-workflow migrations (000002–000004 on
16 October 2026) before serving this bundle. The release includes progress notes,
follow-up and assignment history, the notification bell, and responsibility
changes directly from Complaints.
