# Reviewer and acceptance setup

Status on 6 November 2026: the internal Play release **1.0.7 (8)** is active and
three Google accounts have tester access. Their access permits app installation;
it is separate from Kainat Travels passenger login. App access sign-in details
are now saved in Console. The owner supplied a verified reviewer account, entered
its password directly in Console and explicitly confirmed approved test data,
no OTP and access to every feature without a real purchase. These are owner
confirmations, not independent test results. No live booking, payment or deletion
was performed by this work. No credentials are recorded here.

Optional Google credential reuse on trusted partner devices remains off pending
explicit authorization. Verify the saved sign-in instructions on a fresh
installation before public submission.

Authorized testers can [join the internal test](https://play.google.com/apps/internaltest/4701697187779665820)
using their listed Google account. They should run the acceptance record below
using approved passenger accounts and journeys.

## Remaining verification and operator work

- Keep the owner-provided verified reviewer account and approved test data
  available throughout review. Keep its credentials in the team's secret vault
  and Console App access, outside Git and chat.
- Choose an approved isolated backend with provider sandbox credentials for
  payment/deletion/concurrency acceptance. Current production config says live
  payments; a test label on an account does not make a live payment safe.
- Verify fresh-install password login
  with biometric login disabled. The existing login flow uses mobile + password;
  signup/reset are separate OTP flows.
- Have the backend operator prepare synthetic routes/seats/bookings and sandbox
  payment outcomes in that isolated environment, using the verified backend
  workflow. Do not add a fixed OTP, universal password, fake payment-success
  flag, role escalation or review-only authentication bypass.
- Independently verify the owner-provided account against the production-configured
  bundle, including the confirmed booking/payment review access without real
  purchase. A staging-only account cannot log in to this build. Avoid consuming
  real capacity while checking the approved path.
- Verify the account is usable from another device without personal-device OTP
  handoff and can access every restricted feature. If a screen still needs OTP,
  provide a compliant usable review path before submission; do not claim a code
  will be supplied later. Supply a separate disposable account for deletion.

## Private App access maintenance template

Console already contains saved sign-in details. The template below is a reference
for maintaining complete review instructions; it is not a copy of the saved
credentials. Complete any relevant placeholders privately before using it.

Instruction name: Kainat Travels passenger review

1. Open the app and choose Log in.
2. Mobile number: [dedicated verified reviewer number]
3. Password: [review password entered privately in Console]
4. Open Search, select [approved origin/destination/date], choose [test departure]
   and a seat, enter [approved test traveller details], then continue to payment.
5. Follow [approved payment/branch-confirmation review steps]. Do not use a real
   card unless the owner has explicitly approved the transaction and amount.
6. Open My Trips to view [test confirmed ticket reference] and its boarding QR.
7. Open Account to inspect profile, saved passengers, privacy and deletion.
   Deletion account: [separate disposable review credentials].

Record any geography, schedule-date, language or device restrictions and how the
reviewer can complete the flow. Keep accounts and test journeys available while
the submission is under review. This template is not valid with placeholders.

## Acceptance record to complete

For each run record build hash, device/OS/page size, API deployment, test account
identifier (not credentials), expected result, observed result, and evidence.

- Signup → WhatsApp OTP → verified login; resend and expired/wrong code; password reset.
- Search → schedules → seats → passengers → server quote → reservation.
- Unsupported profile/saved gender explains the limitation and offers support;
  the app never substitutes identity or requests an invalid quote.
- Card success/failure/cancel → server-confirmed payment → ticket/QR; Pay at Branch
  reservation → branch confirmation and expiry; callback/reconciliation recovery.
- Sold-seat race, expired quote, duplicate taps, lost connection, app restart and
  account switching. Confirm no duplicate charge/booking and no cross-account data.
- Physical Android biometrics, notification grant/deny, foreground/background/
  terminated delivery, tap routing, logout token detachment, and Play-installed build.
- In-app deletion removes the dedicated account and revokes sessions; public
  support deletion is fulfilled with retained-data boundaries documented.
- API 36, supported minimum Android version, large text, keyboard, back navigation,
  and 16 KB Android runtime. Binary alignment checks do not replace runtime tests.

Console initial setup is 10 of 11 complete; only Data safety remains. Content
rating is saved, but no production release has been created or submitted.
Public release also remains gated on the applicable completed acceptance record
and the unresolved backend retention/operational findings in
[PLAY_CONSOLE_HANDOFF.md](PLAY_CONSOLE_HANDOFF.md#backend-audit-findings-blocking-final-declarations).
