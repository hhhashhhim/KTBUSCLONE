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

For every release, increase both the semantic version and build number in `pubspec.yaml`, publish the signed builds to their distribution channels, verify availability for the affected users, and then set `latest_version` and `minimum_supported_version` to the published semantic version with `force_update: true` in the company's `mobile_app_configs` row. Set real store URLs before requiring an update. The environment defaults apply only when no company override exists. Do not raise the minimum above a version users can actually install. Build-only changes with the same semantic version cannot be distinguished by this API contract.

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

Local development company 1 is configured with `latest_version: 1.0.1`, `minimum_supported_version: 1.0.1`, and `force_update: true`, matching the installed Android build `1.0.1+2`. Both store URLs remain unset. No store publication or production update policy was changed by this implementation.
## Preserving navigation during update checks

Routine policy checks refresh configuration without refreshing the router unless the mandatory-update or maintenance gate changes. This preserves a passenger's active search, selected journey, and checkout route. A navigation regression test covers an unchanged policy and a newly required update.
