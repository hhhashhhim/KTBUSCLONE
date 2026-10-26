# Mobile build — 26 October 2026

Repository: `hhhashhhim/KTBUSCLONE`. Branch: `mobile-frontend`.
Application: Flutter, `kt_mobile_app/`, version **1.0.5+6**.

## Included changes

The pending Flutter work includes refreshed passenger screens and shared widgets,
session-scoped account data and navigation, the bundled native privacy policy,
notification presentation and Android Firebase initialization, and associated
regression tests and QA documentation.

Build preparation also resolves the two existing layout failures:

- `lib/features/shell/app_shell.dart`: the bottom bar sizes itself to its labels,
  including enlarged text, while retaining the raised search button's hit area.
- `lib/core/widgets/app_widgets.dart`: allow callers to set sticky-action padding.
- `lib/features/travel/presentation/lovable_search_flow.dart`: reduce decorative
  seat-footer padding so the 42-seat map fits a 375 × 812 phone with system insets.
  Seat touch-target sizes and existing acceptance assertions are unchanged.

Only files inside `kt_mobile_app/` belong to this commit. Existing root-level files
and duplicate Laravel/web files are excluded. No backend source changed.

## Checks actually run

| Check | Result |
| --- | --- |
| `flutter analyze --no-pub` | Passed, no issues |
| `flutter test --no-pub test/seat_layout_test.dart --reporter expanded` | 10 passed |
| `flutter test --no-pub --reporter expanded` | 201 passed, zero failures |
| `flutter build apk --debug --no-pub --dart-define-from-file=dart_defines.production.json` | Passed |
| `flutter build ios --simulator --debug --no-pub --dart-define-from-file=dart_defines.production.json` | Passed |
| `git diff --check` | Passed |
| Android `apksigner verify --print-certs` | Passed; Android Debug certificate |

The initial full suite reproduced the two historical layout failures (199 passed,
2 failed); the final results above follow their correction.

Both builds use the existing local production API configuration:
`https://mobile-api.kainattravels.com/api/mobile/v1`. Local settings and generated
build outputs remain excluded from Git. No live booking, payment, deletion or
other production mutation was performed.

## Local artifacts

Paths are relative to `kt_mobile_app/`:

- Android debug APK: `build/app/outputs/flutter-apk/app-debug.apk`.
  Package `com.kainattravels.kt_mobile_app`, version name `1.0.5`, code `6`.
  SHA-256: `1f76189a05a4b6c08213b254d33bed4eeb327f9978e33325bc7af394a5f2990e`.
- iOS simulator app: `build/ios/iphonesimulator/Runner.app`.
  Bundle `com.kainattravels.ktMobileApp`, version `1.0.5`, build `6`.
- Packaged simulator app: `build/ios/Kainat-Travels-1.0.5-6-simulator.zip`.
  SHA-256: `b4a7213e3e2d2e31a43f52a6956957dc8f4fc467e24b151e87cd8680483e174e`.

These artifacts are for testing. Android upload signing is absent, so no signed
release APK/AAB was produced. The iOS simulator app cannot be installed on an
iPhone; no distribution IPA was produced.

## Branches and remaining dependencies

After fetching GitHub, `mobile-backend`, `api`, and `mix` already matched their
remote branches and their separate worktrees were clean. They need no new commit.
Only the Flutter changes are selected for the `mobile-frontend` commit and push.
Pushing Git does not deploy the backend or apply migrations.

The existing booking-gender contract mismatch and production/provider acceptance
remain pending, as described in `QA_REPORT_2026-10-26.md`. Store signing and identity,
device testing, and the remaining Play readiness steps also remain pending.
No backend contract or server-authoritative business rules were changed here.
