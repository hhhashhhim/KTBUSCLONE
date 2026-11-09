# Mobile 1.0.16 (17) — build and Git handoff, 9 November 2026

Repository: `hhhashhhim/KTBUSCLONE`; branch: `mobile-frontend`.

## Contents

This build includes the pending server-calendar search and city refresh changes,
special-fare seat presentation and detailed checkout totals, manual Travel Points
entry, pending-reservation recovery, and offline terms and conditions. API totals
remain server-authoritative. Existing source changes and historical release
reports from 1.0.9 through 1.0.15 are included in the frontend commit.
`pubspec.yaml` advances from 1.0.15+16 to 1.0.16+17. Root `AGENTS.md` records the
repository and branch rules supplied by the owner.

## Validation performed

- Flutter 3.35.7 / Dart 3.9.2: analysis passed with no issues; all 280 tests passed.
- Production Android AAB and APK release builds passed with the existing ignored
  signing configuration and production Dart defines.
- APK signature verification, AAB signature verification, bundletool validation,
  and APK 16 KB ZIP alignment passed. All eight arm64/x86_64 native libraries
  passed 16 KB ELF LOAD alignment checks.
- Android manifest verified: `com.kainattravels.kt_mobile_app`, version 1.0.16,
  build 17, minimum SDK 24, target SDK 36. Debugging, backup, and cleartext traffic
  are disabled. Bundle alignment is `PAGE_ALIGNMENT_16K`.
- Unsigned iOS release compile passed using Xcode 26.6. Bundle ID:
  `com.kainattravels.ktMobileApp`; version 1.0.16/build 17. This `.app` requires
  Apple signing before device distribution; no signed IPA was produced.
- Source hashes were recorded for the Android build. No application source was
  changed during compilation. `git diff --check` passed before commit.

No physical-device acceptance, live booking/payment, Play upload, App Store
upload, or minimum-version policy change was performed in this task.

## Artifacts

Directory: `/Users/malik/development/kt_mobile_app_build/play-release/1.0.16-17/`.

| Artifact | SHA-256 |
| --- | --- |
| `Kainat-Travels-1.0.16-17.aab` | `d3c05b7270a1c02d3f46bbe8b1e345213f72f6bc7be52b67f64f3950e7afe8e5` |
| `Kainat-Travels-1.0.16-17.apk` | `ce91760dcaa9792b2d833769aa3e8b44fb0f13313142cbb76d4625b361b99e00` |

Unsigned iOS output: `ios-unsigned/Runner.app`. The directory also contains test,
build, signature, alignment, manifest, and bundletool logs plus
`verification-summary.json` and `source-manifest.json`. Generated binaries,
production settings, credentials, and signing keys remain outside Git.

## Separate backend and reference repository

Backend changes were committed and pushed as `55583d40` to `mobile-backend`:
registration fixes, schedule/quota behavior, and loyalty earning/cancellation.
Its isolated Composer-locked validation passed 460 tests with 3 optional MySQL
cases skipped (3,016 assertions), plus syntax checks for 110 files. The mobile
route collection loaded all 40 endpoints. The optional global `artisan route:list`
command is affected by an existing duplicate inventory `MYPDF` class; see
`MOBILE_DEPLOYMENT.md` on `mobile-backend`.

The new loyalty ledger migration and associated payment/cancellation hooks still
require coordinated backend deployment. Source pushes do not apply migrations
or deploy the API. Existing API fields used by this Flutter build were confirmed
in the backend source.

The separate `hhhashhhim/bus-ui-lovable` production Vite build passed using its
frozen Bun lockfile. Its instruction changes and regenerated route definitions
were committed and pushed as `f5ab488` to its existing synced `main` branch.

No branches were switched or merged. `KTBUSCLONE/main`, `api`, and `mix` were not
changed. Untracked duplicate Laravel/Vue files and `sync-sar-kt-erp.sh` in the
frontend checkout were left untouched and excluded from the frontend commit.

## Frontend files included

- `AGENTS.md`
- `kt_mobile_app/docs/BUILD_REPORT_1.0.10_2026-11-07.md`
- `kt_mobile_app/docs/BUILD_REPORT_1.0.11_2026-11-07.md`
- `kt_mobile_app/docs/BUILD_REPORT_1.0.12_2026-11-07.md`
- `kt_mobile_app/docs/BUILD_REPORT_1.0.13_2026-11-08.md`
- `kt_mobile_app/docs/BUILD_REPORT_1.0.14_2026-11-08.md`
- `kt_mobile_app/docs/BUILD_REPORT_1.0.15_2026-11-08.md`
- `kt_mobile_app/docs/BUILD_REPORT_1.0.9_2026-11-06.md`
- `kt_mobile_app/docs/FARES.md`
- `kt_mobile_app/docs/SCHEDULE_RESULTS.md`
- `kt_mobile_app/docs/UPDATES.md`
- `kt_mobile_app/lib/app/app.dart`
- `kt_mobile_app/lib/core/legal/terms_and_conditions.dart`
- `kt_mobile_app/lib/core/widgets/legal_links.dart`
- `kt_mobile_app/lib/core/widgets/terms_and_conditions_screen.dart`
- `kt_mobile_app/lib/features/startup/domain/app_config.dart`
- `kt_mobile_app/lib/features/travel/application/travel_calendar.dart`
- `kt_mobile_app/lib/features/travel/application/travel_providers.dart`
- `kt_mobile_app/lib/features/travel/domain/travel_models.dart`
- `kt_mobile_app/lib/features/travel/presentation/checkout_widgets.dart`
- `kt_mobile_app/lib/features/travel/presentation/lovable_search_flow.dart`
- `kt_mobile_app/lib/features/travel/presentation/points_entry_dialog.dart`
- `kt_mobile_app/lib/features/travel/presentation/schedule_result_card.dart`
- `kt_mobile_app/lib/features/travel/presentation/search_flow.dart`
- `kt_mobile_app/lib/features/travel/presentation/special_fare_widgets.dart`
- `kt_mobile_app/pubspec.yaml`
- `kt_mobile_app/test/account_session_navigation_test.dart`
- `kt_mobile_app/test/checkout_points_test.dart`
- `kt_mobile_app/test/city_catalog_test.dart`
- `kt_mobile_app/test/fare_checkout_test.dart`
- `kt_mobile_app/test/schedule_results_test.dart`
- `kt_mobile_app/test/seat_layout_test.dart`
- `kt_mobile_app/test/terms_and_conditions_test.dart`
- `kt_mobile_app/test/travel_calendar_test.dart`
- `kt_mobile_app/test/travel_models_test.dart`
- `kt_mobile_app/docs/BUILD_REPORT_1.0.16_2026-11-09.md`
