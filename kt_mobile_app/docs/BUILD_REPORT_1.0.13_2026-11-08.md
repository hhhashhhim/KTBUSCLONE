# Android 1.0.13 (14) — special discount seat counts and terms

Repository: `hhhashhhim/KTBUSCLONE`. Frontend work is on `mobile-frontend` in
`/Users/malik/Desktop/Codes/KTBUSGIT/kt_mobile_app/`; backend work is separately
on `mobile-backend` in `/Users/malik/Desktop/Codes/KTBUSCLONE-mobile-backend/`.
Existing pending changes were preserved. Nothing was committed or pushed; no
branch switch, merge or history rewrite was performed.

## Released behavior

Schedule cards show **N special discount seats** when the server reports a
positive count and the trip has available seats and a known fare. Zero/missing
counts, sold-out trips and unpriced trips hide the label. Both result layouts
use the same API count; all discount eligibility remains server-authoritative.
The backend uses existing paid-reuse, availability, permissions and rounding
rules and caps the count by remaining online quota. It reuses batched search
loads and the existing quota fare adjustment rather than adding per-seat queries.

Terms & conditions now opens a native, selectable, scrollable page from the
existing registration and profile links. The bundled public terms and booking
help are available offline and before login. Consent state and navigation are
preserved. The owner explicitly requested including this existing terms work
in the release; the release snapshot was refreshed and fully rebuilt afterward.

## Backend deployment and live test

Only these two production files were replaced, with original-checksum guards,
PHP syntax checks, private backups and automatic rollback on verification failure:

- `app/Services/Mobile/MobileTravelService.php`
- `app/Http/Resources/Mobile/ScheduleResource.php`

The resource defaults missing `available_special_seats` to zero for compatibility.
Base revision remains `8abbda840b4e4b171a0c8eeccb20b25e6b9fe584` with authorized
release overlays. No migration was required; environment and cron were unchanged.
No booking, payment, customer account or token was created by live verification.

The first diagnostic used an explicit read-only transaction, which caused the
existing quota reader to request a row lock; MySQL rejected that diagnostic and
the deploy script restored both original files. After removing that diagnostic
transaction, the normal read-only search/seat-map path passed. Final backup:
`/home/wwwkaina/private-release-backups/special-count-20261108-v14-200158/`.
Private staging: `/home/wwwkaina/private-release-staging/special-count-20261108-v14/`.

At **01:02:02 Asia/Karachi on 8 November 2026**, live Multan (4) → Rawalpindi (1)
results matched the seat maps and serialized the new API field:

| Schedule | Detail | Departure | Available | Special discount seats |
| --- | --- | --- | --- | --- |
| 1469 | 2435113 | 03:45 | 8 | 0 |
| 1536 | 2392297 | 05:45 | 18 | 0 |
| 1563 | 2391802 | 13:44 | 20 | 20 |

These counts are a point-in-time observation and can change with bookings.
Sample eligible fares were PKR 2,850 before quota → PKR 2,600 payable, a PKR 250
saving after the existing rounding rules. Public `app/config` returned HTTP 200;
unauthenticated `/cities` and `/schedules` correctly returned HTTP 401.

## Validation

- Full Flutter suite: **272 tests passed**, including six terms tests, both
  schedule layouts, count visibility, accessibility and responsive layouts.
- `flutter analyze --no-pub`: no issues. Both worktrees passed `git diff --check`.
- Backend scheduling, API contract and payment suites: **238 passed, 2 skipped**,
  **1,853 assertions** (240 total). The two optional MySQL expiry-schema tests
  require `MOBILE_MYSQL_TEST_DATABASE`; no MySQL test database was configured.
- The backend used the existing isolated Composer-locked runtime because the
  worktree's vendor directory is incompatible. An existing dependency emits a
  PHP 8.4 deprecation; dependencies were not changed.
- Signed AAB/APK builds, expected upload certificate, signatures, bundletool
  validation, package/version, min SDK 24, target SDK 36 and release flags passed.
- 16 KB bundle and APK alignment and all eight 64-bit ELF library checks passed.
- Snapshot hashes match the working app source, including all terms files.
- No physical-device installation, payment or 16 KB runtime test is claimed.

## Publication and update policy

Google Play internal release **7** is **Active**, latest **1.0.13 (14)**, and
**Available to internal testers**, released **8 November 2026 at 01:03 PKT**.
Play confirms ReTrace mapping and native debug symbols attached, with no loss
of supported devices. The release notes include both features.

- [Release details](https://play.google.com/console/u/2/developers/5051456537957291644/app/4973980219327601890/tracks/4701697187779665820/releases/7/details)
- [Tester download](https://play.google.com/apps/internaltest/4701697187779665820)

After availability was verified, the existing mandatory-update policy was
advanced transactionally to latest/minimum **1.0.13**, `force_update: true`,
with other configuration retained. Private policy rollback snapshot:
`/home/wwwkaina/private-release-backups/special-count-update-policy-20261108-v14.json`.
Public HTTP 200 at **01:03:48 PKT** verified the policy and maintenance disabled.
No backend/frontend release dependency remains pending. Testers must install
the update; Google Play may take time to propagate it to individual devices.

## Artifacts

Directory: `/Users/malik/development/kt_mobile_app_build/play-release/1.0.13-14/`.

| Artifact | SHA-256 |
| --- | --- |
| `Kainat-Travels-1.0.13-14.aab` | `817a86ea21eaba677f9b190695588b3081ed93902bace7e254e0183ba914d704` |
| `Kainat-Travels-1.0.13-14.apk` | `03476c1686d1d910675f11a727c5ba5f6a628c0a9f8bfa5d0bdefeb73856f86c` |

Evidence: `source-manifest.json`, `verification-summary.json`, `build.log`,
`backend-tests.log`, `backend-count-verified.jpg`, `http-auth-smoke.json`,
`play-internal-1.0.13-active.jpg`, `update-policy.json`, `update-policy-enabled.jpg`.
The superseded build excluding terms was never uploaded; its log is retained as
`initial-build-without-terms.log`.

## Files changed or included for this release

Frontend, relative to `kt_mobile_app/`:

- `lib/features/travel/domain/travel_models.dart`
- `lib/features/travel/presentation/schedule_result_card.dart`, `search_flow.dart`,
  `special_fare_widgets.dart`
- `lib/core/legal/terms_and_conditions.dart`
- `lib/core/widgets/terms_and_conditions_screen.dart`, `legal_links.dart`
- `test/schedule_results_test.dart`, `travel_models_test.dart`,
  `terms_and_conditions_test.dart`
- `pubspec.yaml`
- `docs/SCHEDULE_RESULTS.md`, `FARES.md`, `UPDATES.md`, this report

Backend: the two deployed files above, `tests/Feature/MobileSchedulingRulesTest.php`,
`tests/Feature/MobileApiContractTest.php`, `MOBILE_API.md`, `MOBILE_DEPLOYMENT.md`.
