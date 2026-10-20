# Android release setup

Application ID: `com.kainattravels.kt_mobile_app`. Keep this ID for future updates.
The release variant uses a private upload key and rejects missing signing settings,
development environments, and HTTP API URLs. Debug builds remain available without
an upload key. Release network traffic must use HTTPS.

## Upload key

Confirm the Play Console application ID and its registered upload key before
preparing an update. On 19 October 2026 the public website linked to
`com.techhord.kainattravels`, which differs from this Flutter project's
`com.kainattravels.kt_mobile_app`. That public link returned 404 during review.
Do not use the legacy listing as this app's update URL or silently change the
Flutter application ID. The owner must confirm whether this is a new listing or
an update to a registered application. Use the matching existing key; do not
generate a replacement for an existing listing. Configure it from `kt_mobile_app/`:

```bash
cp android/key.properties.example android/key.properties
```

Set the existing keystore path, alias, and passwords in `android/key.properties`.
`storeFile` resolves from `android/`, or accepts an absolute path. Both this file
and keystores under `android/` are ignored by Git. Back them up securely. Never put
them in the backend upload or share passwords in chat. Signing can wait until
local testing against the uploaded backend is complete.

## Production build

Create the ignored `dart_defines.production.json` locally with the deployment's
approved API URL. The current configuration is:

```json
{
  "APP_ENV": "production",
  "API_BASE_URL": "https://mobile-api.kainattravels.com/api/mobile/v1"
}
```

Do not add credentials to this file. Production disables development network logs.

```bash
flutter pub get
flutter analyze
flutter test
flutter build appbundle --release --dart-define-from-file=dart_defines.production.json
flutter build apk --release --dart-define-from-file=dart_defines.production.json
```

- Google Play: `build/app/outputs/bundle/release/app-release.aab`.
- Device testing: `build/app/outputs/flutter-apk/app-release.apk`.
- Increase the build number in `pubspec.yaml` or pass `--build-number=N` for each
  Play upload. It must exceed the last uploaded version code.
- Previously installed debug-signed APKs cannot be upgraded with the new key;
  uninstall the test app first (this removes its local session/preferences).

## Test build

Version 1.0.3 (build 4) includes optional biometric quick login. Without a configured
upload key, produce an installable debug-signed Android APK and an iOS simulator
app using the same API configuration:

```bash
flutter build apk --debug --dart-define-from-file=dart_defines.production.json
flutter build ios --simulator --debug --dart-define-from-file=dart_defines.production.json
```

Outputs: `build/app/outputs/flutter-apk/app-debug.apk` and
`build/ios/iphonesimulator/Runner.app`. These are test builds, not store releases.
Face ID and fingerprint behavior still require verification on physical devices.

## Backend deployment dependency

Deploy the separate `mobile-backend` worktree. Follow its `MOBILE_DEPLOYMENT.md`.
Verify `/api/mobile/v1/app/config` returns JSON, then test passenger login, a quote,
provider checkout, return to the app, and backend-confirmed ticket status.
Payments are controlled by the API's `payment_methods` and `payment_preview` fields;
there is no Flutter switch that bypasses backend payment verification.

See [Flutter's Android release guide](https://docs.flutter.dev/deployment/android)
for Play App Signing and upload instructions.
