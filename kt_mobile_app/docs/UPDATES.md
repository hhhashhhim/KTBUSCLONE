# App updates

## Development emulator

Run `python3 tool/dev_autoupdate.py` from `kt_mobile_app/` on `mobile-frontend` with the Android emulator running. The runner uses the existing `dart_defines.local.json`, builds and installs the newest app, and watches saved changes. Dart and asset changes trigger an automatic hot restart; dependency, native, or build-configuration changes rebuild and reinstall. A hot restart resets navigation but preserves stored sign-in. Compilation errors do not count as a successful update. Keep the runner open; stop it with Ctrl+C. Only one runner may own an emulator.

This workflow uses Flutter's documented machine protocol (`app.started`, `app.restart`, `app.detach`). It does not publish builds or change Git history.

## Passenger releases

The app checks `GET /api/mobile/v1/app/config` at startup, on resume, and once a minute while foregrounded. This reuses the existing backend contract:

- `minimum_supported_version`: versions below this are blocked.
- `force_update: true`: versions below `latest_version` are blocked as well. The latest version remains usable.
- `android_store_url`, `ios_store_url`: approved published update destinations.

Mandatory updates cannot be skipped through Back, login, or another app route. A failed background check retains the last known requirement. Returning from the store rechecks the policy. Opening the store does not mark the application updated; the installed package version is authoritative.

For every release, increase both the semantic version and build number in `pubspec.yaml`, then publish the signed builds to their distribution channels. Keep the previous required version until an enrolled target device/account actually offers and can install the update. Play Console's “Active / Available to internal testers” status alone does not establish device availability. Only after that device check, set `latest_version` and `minimum_supported_version` to the published semantic version with `force_update: true` in the company's `mobile_app_configs` row. Set real store URLs before requiring an update. The environment defaults apply only when no company override exists. Do not raise the minimum above a version users can actually install. Build-only changes with the same semantic version cannot be distinguished by this API contract.

For Android staging/production builds installed through Google Play, the mandatory-update screen automatically attempts Google's immediate update flow using `in_app_update` 4.2.5 (compatible with this Flutter SDK). The user accepts Play's prompt; Play handles installation and restart. Canceling or failing the flow leaves the update requirement in place. The Update now button retries or opens the configured store link. iOS uses the configured App Store link. Development builds skip Play's flow and use the emulator runner.

Source edits are not distribution releases. Google Play/App Store handle installation and platform consent; the app enforces the requirement to update before continuing. The development runner handles the local emulator independently. Publishing, store registration, and production configuration are still required before testing this with passengers. See [Google's immediate update documentation](https://developer.android.com/guide/playcore/in-app-updates/kotlin-java) and [plugin setup and testing requirements](https://pub.dev/packages/in_app_update/versions/4.2.5).

## Verification and changed files

`flutter test test/update_test.dart` covers numerical version comparison, mandatory versus optional releases, allowing the latest version, route enforcement, and retaining an update requirement when offline.

- `lib/features/startup/domain/update_policy.dart`
- `lib/features/startup/application/startup_controller.dart`
- `lib/features/startup/application/immediate_update.dart`
- `lib/features/startup/presentation/startup_screens.dart`
- `lib/app/app.dart`
- `lib/app/router.dart`
- `pubspec.yaml`
- `pubspec.lock`
- `tool/dev_autoupdate.py`
- `test/update_test.dart`

The original local development verification used company 1 with `latest_version: 1.0.1`, `minimum_supported_version: 1.0.1`, and `force_update: true`, matching Android build `1.0.1+2`, with both store URLs unset. That original implementation did not change the production policy; the published configuration below supersedes it for production.

## Production policy — 7 November 2026

The owner chose to require the latest version before continuing. Android **1.0.11 (12)** was published to Google Play internal testing and verified **Active / Available to internal testers** at 21:24 Asia/Karachi. Only afterward, production company 1 was set to:

- `latest_version: 1.0.11`
- `minimum_supported_version: 1.0.11`
- `force_update: true`
- `android_store_url: https://play.google.com/store/apps/details?id=com.kainattravels.kt_mobile_app`

The public config endpoint returned HTTP 200 with these values at 21:25. The prior configuration was backed up privately, and other fields were preserved. This is an internal Android release; tester access and public production distribution were unchanged. See [the release report](BUILD_REPORT_1.0.11_2026-11-07.md) for evidence and rollback details.

Reopening an older app checks this policy and requires an update. Google Play still requires installation consent; reopening does not silently install a new binary. Once Play makes the release available to a tester's account/device, the immediate update flow or store link can install it. Continue to publish and verify each future release before raising the required version.

## Preserving navigation during update checks

Routine policy checks refresh configuration without refreshing the router unless the mandatory-update or maintenance gate changes. This preserves a passenger's active search, selected journey, and checkout route. A navigation regression test covers an unchanged policy and a newly required update.

## Previous production policy — 1.0.12 (13)

The server-calendar release supersedes the 1.0.11 policy above. Google Play
internal testing confirmed **1.0.12 (13) Active / Available to internal testers**
on **7 November 2026 at 21:44 Asia/Karachi**. Only afterward, the latest and minimum
supported versions were raised to **1.0.12**, retaining `force_update: true` and
the existing Android store URL. Public config verification passed at 21:45:24.
See [the release report](BUILD_REPORT_1.0.12_2026-11-07.md) for test results,
publication evidence, server-clock verification and private rollback paths.

## Previous production policy — 1.0.13 (14)

On 8 November 2026 at 01:03 Asia/Karachi, Google Play internal testing confirmed
**1.0.13 (14) Active / Available to internal testers**. The release contains the
available special-discount-seat count and native offline terms-and-conditions.
After availability was confirmed, company 1's latest and minimum supported
versions were set to **1.0.13**, retaining `force_update: true` and the store URL.
Public HTTP verification at 01:03:48 returned these values, maintenance disabled,
and `Cache-Control: no-store, private`. See
[the release report](BUILD_REPORT_1.0.13_2026-11-08.md).

## Previous production policy — 1.0.14 (15)

Google Play internal testing confirmed **1.0.14 (15) Active / Available to
internal testers** on 8 November 2026 at 01:36 Asia/Karachi. This release adds
manual Kainat Points entry at checkout, restricted to the available balance,
while retaining the increment/decrement controls and server fare calculation.

After publication was verified, company 1's latest and minimum supported
versions were advanced to **1.0.14**. The existing `force_update: true`, store
URL and other configuration were preserved. Public HTTP 200 verification at
01:37:19 returned the new policy with maintenance disabled and
`Cache-Control: no-store, private`. See
[the release report](BUILD_REPORT_1.0.14_2026-11-08.md).

## Initial 1.0.15 (16) activation — superseded below

Google Play internal testing confirmed **1.0.15 (16) Active / Available to
internal testers** on **8 November 2026 at 02:18 Asia/Karachi**. The release
replaces the misleading fare-error heading for pending-reservation limits
with clear guidance and a direct My Trips action.

After publication, company 1's latest and minimum supported versions were
advanced to **1.0.15**. The existing `force_update: true`, wallet availability,
store URL and unrelated configuration were preserved. Public HTTP 200 at
02:19:08 verified the policy and maintenance disabled. See
[the release report](BUILD_REPORT_1.0.15_2026-11-08.md) for artifacts, checks and
the private rollback location. No Git commit or push was performed.

## Current requirement — restored to 1.0.14 at 02:23 PKT

The user reported that the required update was not offered by Google Play.
On 8 November 2026 the backend requirement was restored transactionally to
`latest_version: 1.0.14`, `minimum_supported_version: 1.0.14`, retaining
`force_update: true`. Both version fields must be restored because the force
flag also blocks versions below `latest_version`. The published 1.0.15 (16)
Play release remains active; this rollback only restores access to 1.0.14.

Public HTTP 200 at **02:23:51 PKT** verified the policy, wallet enabled,
maintenance disabled and `Cache-Control: no-store, private`. Wallet, payment
and unrelated settings were preserved. Users on 1.0.14 can select Check again
or restart the app online. No requirement for 1.0.15 should be reactivated
until the affected device/account can install it. The exact device-side reason
for Play not offering the update remains unverified.

The user confirmed that the app opens after this correction. The existing
update-policy suite passed all 9 tests; no app rebuild was needed for recovery.

Private backup of the pre-rollback policy:
`/home/wwwkaina/private-release-backups/checkout-update-delay-20261108-v16.json`.
See `MOBILE_DEPLOYMENT.md` on `mobile-backend` for the server work unit.

## Local build 1.0.16 (17) — 9 November 2026

Signed Android AAB/APK and an unsigned iOS release app were built for the Git
handoff. All 280 Flutter tests and static analysis passed. This build was not
uploaded to either app store and does not change the server update policy.
See [the build report](BUILD_REPORT_1.0.16_2026-11-09.md) for source files,
artifact hashes, validation, and the pending backend deployment dependency.
