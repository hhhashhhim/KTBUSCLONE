# Android 1.0.8+9 follow-up — 6 November 2026

Repository: `hhhashhhim/KTBUSCLONE`, branch `mobile-frontend`.
Package: `com.kainattravels.kt_mobile_app`.

**Status:** signed release candidate built and verified locally. The existing
Play internal release remains 1.0.7 (8); this candidate has not been uploaded or
submitted. Production remains inactive. No source commit or push was performed.

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
| `git diff --check` | Passed |

The build uses existing ignored production settings and upload signing material.
No dependency upgrade, new signing identity, production setting or minimum-version
change is part of this candidate.

Artifacts are under `build/play-release/1.0.8-9/` (the existing build directory
resolves to `/Users/malik/development/kt_mobile_app_build/`).

| Artifact | SHA-256 |
| --- | --- |
| `Kainat-Travels-1.0.8-9.aab` | `b42ba743a27c421d5ed3147dd2986254c738f3b5635f22bad1abf5b7b9b58c8c` |
| `Kainat-Travels-1.0.8-9.apk` | `e37b3ffe7f8b07f10c2a449111cb71d6cff88de3481b589fd0b25e55b2b7f344` |

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

A fresh cPanel inspection supersedes the older backend rollout observation:
the live mobile checkout is now `mobile-backend` at `b78d1cc1`. Snapshot linking,
schema hardening and first-login migrations are applied in batches 103, 104 and
105. Retention is still disabled; the maximum age of all restore copies is unset.
The mobile cron still invokes only `mobile:notifications`; the separate ERP cron
invokes its own `schedule:run`.

The separate `mobile-backend` work unit prepared a forward quote-index repair and
`MOBILE_SCHEDULER_ONLY` setting, with 393 tests passed and two optional MySQL cases
skipped (2,563 assertions). These new changes are not deployed. Its
`MOBILE_RELEASE_2026-11-06.md` contains the scoped rollout and cron replacement
checks. The scheduler setting prevents a second copy of legacy ERP jobs running
on the mobile host.

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
- Deploy the separate backend scheduler/index follow-up. Keep ERP read-only
  unless the owner changes that instruction; its deletion race remains a release
  dependency. Do not deploy an entire unrelated ERP branch.
- Upload this new candidate to the intended test track, verify installation, then
  submit production only after the remaining gates are resolved. Configure latest
  version/store URL when the intended listing is accessible; keep force-update
  off until installation/update works. The current public config still reports
  1.0.0 with empty store URLs and force-update false.

The repository's AGENTS.md requires explicit permission for source commits and
pushes. No commits, pushes, server source/configuration changes or Play changes
were made in this work.

References: [Google Data safety](https://support.google.com/googleplay/android-developer/answer/10787469),
[Firebase retention](https://firebase.google.com/support/privacy),
[Android 16 KB testing](https://developer.android.com/guide/practices/page-sizes).
