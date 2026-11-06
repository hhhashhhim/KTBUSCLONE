# Android 1.0.8+9 follow-up — 6 November 2026

Repository: `hhhashhhim/KTBUSCLONE`, branch `mobile-frontend`.
Package: `com.kainattravels.kt_mobile_app`.

**Status:** Android **1.0.8 (9)** is uploaded and published to the Play internal
track, which shows **Active**, latest release **1.0.8**, and **Available to internal
testers (not reviewed)**. The existing three authorized testers and support
details are unchanged. Frontend source commit `9fbe332e` was pushed to
`mobile-frontend`. Public production, Data safety and account/trader declarations
remain pending.

## Implemented

My Trips now requests every booking page before displaying complete Upcoming,
Past and Cancelled lists. It uses the confirmed backend pagination contract,
rejects incomplete/inconsistent responses with a retryable error, and cancels
in-flight loading when the session changes. Previously only the first 20 bookings
were loaded. Ten new regressions cover multiple pages, later-page upcoming trips,
empty/legacy responses, failures, changed results and account isolation.

Changed frontend source: `lib/features/travel/data/travel_repository.dart`,
`lib/features/travel/application/travel_providers.dart`, `pubspec.yaml`,
`test/booking_pagination_test.dart`, and test doubles in
`test/account_isolation_test.dart`, `test/media_refresh_test.dart`,
`test/payment_test.dart`, `test/trips_loading_test.dart`.

## Verification actually run

| Check | Result |
| --- | --- |
| `bash tool/build_android_release.sh` | Completed; dependencies, analysis, full tests, signed AAB and APK |
| Flutter static analysis | No issues |
| Flutter full suite | 235 passed |
| APK signature and 16 KB ZIP alignment | Passed |
| AAB signature | `jar verified`; existing self-signed/no timestamp/ZIP stream-order warnings retained |
| Official bundletool 1.18.3 validation | Passed; tool digest checked against official release asset |
| Android manifest | Correct package/version; min API 24, target API 36; debugging, backups and cleartext disabled |
| Bundle/native alignment | `PAGE_ALIGNMENT_16K`; all eight arm64/x86_64 libraries have aligned LOAD segments |
| 16 KB Android runtime startup | Signed APK installed and cold-launched on a fresh API 36 emulator; actual page size 16384; activity resumed and process stable with no app entry in the crash buffer |
| Play release review | Bundle 9 (1.0.8), min API 24, target API 36; mapping file and native debug symbols attached; no blocking errors or warnings shown |
| Play internal publication | Save and publish confirmed; track Active, latest release 1.0.8, available to internal testers (not reviewed) |
| `git diff --check` | Passed |

The build uses existing ignored production settings and upload signing material.
No dependency upgrade, new signing identity or minimum-version change is part of
the Android artifact. The separate mobile server rollout is recorded below.

Artifacts are under `build/play-release/1.0.8-9/` (the existing build directory
resolves to `/Users/malik/development/kt_mobile_app_build/`).

| Artifact | SHA-256 |
| --- | --- |
| `Kainat-Travels-1.0.8-9.aab` | `b42ba743a27c421d5ed3147dd2986254c738f3b5635f22bad1abf5b7b9b58c8c` |
| `Kainat-Travels-1.0.8-9.apk` | `e37b3ffe7f8b07f10c2a449111cb71d6cff88de3481b589fd0b25e55b2b7f344` |

The uploaded AAB is the exact verified artifact above; no rebuild was needed.
The native file picker worked after selecting the correct Chrome app/tab, without
changing the extension's file-URL permission. Publication proof:
`build/play-release/1.0.8-9/play-internal-1.0.8-active.jpg`.
[Internal tester opt-in](https://play.google.com/apps/internaltest/4701697187779665820).

The directory includes the build log, hashes, artifact checks and signature,
alignment, manifest and bundletool verification evidence, plus `runtime-16kb.json`
and app/crash logs. Private signing files are not copied into this directory.
The runtime check used a fresh AVD `ktbus_release_16k_20261106` with Google's
official API 36 Play Store 16 KB ARM64 image. It covered installation and stable
process/activity startup; it did not establish visual, Play-installed, payment,
notification or physical-phone acceptance. Existing emulator data was not changed.

## Live account and server checks

With the owner's supplied account, normal password login succeeded and returned
a verified passenger session. GET session, cities, booking pages, saved passengers
and notifications all returned successful HTTP 200 responses. There were 21
bookings: 20 on page one and one on page two, confirming real impact of the old
client limit. This checks the API contract; it is not a physical-device or payment
acceptance run. The diagnostic logged out its new token and confirmed HTTP 401
when that token was reused. Login follows the existing single-session policy and
revokes older access sessions. No credentials or passenger details are saved in
the sanitized report at `build/qa/live-account-smoke-2026-11-06.json`.

No booking, charge, account deletion, OTP send, device registration or notification
read-state change was made during those checks.

Historical preflight baseline: the earlier cPanel inspection found
`mobile-backend` at `b78d1cc1`, with snapshot linking, schema hardening and
first-login migrations in batches 103, 104 and 105. At that point the mobile cron
invoked only `mobile:notifications`. These are pre-rollout observations, not the
current source or scheduler state.

The approved follow-up commit `8abbda840b4e4b171a0c8eeccb20b25e6b9fe584` was pushed
to `mobile-backend` and deployed by a fast-forward-only update in
`/home/wwwkaina/kt-mobile-api`, on branch `mobile-backend`. The forward
quote-index repair is recorded in migration batch 106. Its preflight found the
existing index already had the exact `used_at, expires_at, id` columns, so the
repair required no index DDL. The deployed configuration has
`MOBILE_SCHEDULER_ONLY=true`, with exactly six mobile scheduled commands and no
duplicate ERP jobs. Only the mobile notification-only cron was replaced with
`schedule:run`; the separate ERP cron was preserved byte for byte and ERP remained
read-only. Retention remains disabled and force-update remains false.

The mobile API was brought back up, and `/api/mobile/v1/app/config` returned
HTTP 200 after deployment. The dated scheduler log recorded four normal launches
each of payment reconciliation and notifications, with zero generic scheduler
error markers. No manual scheduler was invoked. At the final observation there
were zero active mobile PHP jobs, maintenance was false, and the approved cron
bytes matched with ERP preserved. Laravel redirects individual command output,
so these checks establish cron launches, not provider outcomes or end-to-end
acceptance. The sanitized proof is
`build/play-release/1.0.8-9/backend-rollout-proof.jpg`.

The backend follow-up's isolated suite passed
393 tests with two optional MySQL cases skipped (2,563 assertions). Its
`MOBILE_RELEASE_2026-11-06.md` contains the scoped rollout checks.

The protected rollback copy is
`/home/wwwkaina/private-release-backups/mobile-20261106-em5e8J`. It requires manual
deletion after `2026-11-13T00:00:00Z`; no automatic expiry is claimed. This
seven-day rollout copy is separate from the owner-confirmed 30-day hosting
backups. Retention execution and the inventory of all other restore copies remain
separate acceptance requirements.

The owner explicitly requested ERP remain read-only. A scoped notification/account
locking patch was reproduced and tested outside the ERP worktree, but is not
applied. The live writer can still recreate an inbox row during concurrent account
deletion. `review-only-erp-notification-account-locks.patch` in the artifact
directory is review material, not an installed fix.

## Remaining acceptance and publication gates

- The 16 KB runtime startup smoke passed. Full functional acceptance on that
  runtime and Play-installed physical-phone checks remain pending.
- Real-provider payment/ticket, failure/callback/reconciliation, concurrent seat
  claims, OTP, biometric, push and disposable-account deletion checks remain
  pending. The supplied account and database are real; no financial transaction
  or deletion was performed.
- Complete truthful Data safety using `DATA_SAFETY_EVIDENCE_2026-11-06.md` and
  reconcile public/in-app retention wording with actual approved handling. The
  owner asserted providers retain no data, but Firebase's published retention
  confirms installation IDs persist; a blanket zero-retention statement is not
  supported. Hosting backup expiry within 30 days is already owner-confirmed.
- The live Play dashboard has 10 of 11 setup tasks complete, internal testing
  active and production inactive. Data safety remains unfinished. Outstanding
  account declarations and independent reviewer-access acceptance still apply.
- Confirm reconciliation/notification provider outcomes; four normal cron
  launches of each command are observed, but provider acceptance is unproven.
  Keep ERP read-only unless the owner
  changes that instruction; its deletion race remains a release dependency.
  Do not deploy an entire unrelated ERP branch.
- Verify installation of 1.0.8 from the active internal track, then submit
  production only after the remaining gates are resolved. Configure latest
  version/store URL when the intended listing is accessible; keep force-update
  off until installation/update works. The current public config still reports
  1.0.0 with empty store URLs and force-update false.

The owner-authorized frontend/backend commits and pushes, scoped mobile server
rollout, and Play internal publication of 1.0.8 are complete. No public production
submission or completed functional acceptance is claimed. ERP source and cron
were not changed.

References: [Google Data safety](https://support.google.com/googleplay/android-developer/answer/10787469),
[Firebase retention](https://firebase.google.com/support/privacy),
[Android 16 KB testing](https://developer.android.com/guide/practices/page-sizes).
