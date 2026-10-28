# Mobile build — 28 October 2026

Repository: `hhhashhhim/KTBUSCLONE`. Flutter branch: `mobile-frontend`.
Version: **1.0.6+7**. Backend reference: `mobile-backend` at `cc2e21d2`.

## Included changes

The pending Flutter changes improve notification registration errors, timeout and
retry handling, session rebinding, foreground alerts, and notification navigation
through startup/login/update gates. Apple registration waits for APNs readiness.
The search screen now shows only the supported one-way journey option.

Build preparation increments the app from `1.0.5+6` to `1.0.6+7` and documents the
new test build. No backend source or API contract changed in this build task.

## Files in the Flutter commit

All paths are relative to `kt_mobile_app/`:

- `docs/ANDROID_RELEASE.md`
- `docs/BUILD_REPORT_2026-10-28.md`
- `docs/NOTIFICATIONS.md`
- `ios/Runner/Info.plist`
- `lib/app/app.dart`
- `lib/features/notifications/notification_providers.dart`
- `lib/features/notifications/notification_push.dart`
- `lib/features/travel/presentation/lovable_search_flow.dart`
- `pubspec.yaml`
- `test/firebase_push_client_test.dart`
- `test/notification_delivery_test.dart`
- `test/notifications_test.dart`

Root-level untracked files and duplicate Laravel/web files remain untouched and
excluded. Local settings, private signing material, and generated binaries remain
excluded from Git. The existing ignored `build` symlink points to
`/Users/malik/development/kt_mobile_app_build`.

## Checks actually run

| Check | Result |
| --- | --- |
| `flutter pub get` | Passed; no dependency lock changes |
| `flutter analyze --no-pub` | Passed, no issues |
| `flutter test --no-pub --reporter expanded` | 213 passed |
| `flutter build apk --debug --no-pub --dart-define-from-file=dart_defines.production.json` | Passed |
| `flutter build ios --simulator --debug --no-pub --dart-define-from-file=dart_defines.production.json` | Passed |
| Android `apksigner verify --print-certs` | Passed; Android Debug certificate |
| Android package metadata and iOS `Info.plist` | Both report version 1.0.6, build 7 |
| Backend `php -d error_reporting=22527 vendor/bin/phpunit tests/Feature/MobileNotificationsTest.php` | 18 passed, 77 assertions |
| `git diff --check` | Passed |

Backend tests ran in the separate clean `mobile-backend` worktree using disposable
SQLite and fake provider responses. No production mutation or deployment occurred.

## Build artifacts

Both builds use the existing ignored production settings, with API base URL
`https://mobile-api.kainattravels.com/api/mobile/v1`.

- Android: `build/app/outputs/flutter-apk/Kainat-Travels-1.0.6-7-debug.apk`
  (also available as `app-debug.apk` in the same directory).
  Package: `com.kainattravels.kt_mobile_app`.
  SHA-256: `e1d7d1b88fc80deaea1fa0d9bd31adf9edd53711ff55e83b8b9661d88d1d9bea`.
- iOS: `build/ios/Kainat-Travels-1.0.6-7-simulator.zip`, containing `Runner.app`.
  Bundle: `com.kainattravels.ktMobileApp`.
  SHA-256: `c36473b762f0370018c346fd8c188cb330bf948b02accff1859345524ecd4577`.

The Android APK is debug-signed for testing. The iOS archive runs in Simulator;
it is not an installable iPhone IPA. Android upload signing remains unconfigured,
so no store release APK/AAB was produced. Android Firebase settings are present;
iOS Firebase/APNs configuration and physical-device acceptance remain pending.
Existing payment/provider and store-readiness checks remain separate from this
build verification.

## Git and backend deployment

After fetching GitHub, both mobile branches matched their remote branches before
the new Flutter commit. The clean backend checkout required no new commit;
`git push origin mobile-backend:mobile-backend` returned `Everything up-to-date`
at `cc2e21d2`. The Flutter commit is limited to the twelve files listed above.

The existing cPanel checkout is `/home/wwwkaina/kt-mobile-api`. Follow
`MOBILE_DEPLOYMENT.md` on `mobile-backend`: verify the remote, active branch and
working tree before pulling with `--ff-only`, preserve server-local changes and
private configuration, and run commands separately, stopping on any error.
This build introduces no new backend migration or dependency change. Pushing Git
does not deploy the backend or apply any previously pending migrations.
