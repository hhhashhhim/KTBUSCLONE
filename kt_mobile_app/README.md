# Kainat Travels Passenger App

Flutter passenger application for Android and iOS. Runtime settings are supplied with Dart defines; no service credentials belong in the app.

## Run locally

Copy `dart_defines.example.json` to a local, ignored settings file and adjust `API_BASE_URL` for your device. Android emulators normally reach the host at `http://10.0.2.2:8000`; iOS simulators can normally use `http://127.0.0.1:8000`.

```bash
flutter pub get
flutter run --dart-define-from-file=dart_defines.local.json
```

The API base URL must include `/api/mobile/v1`. Production builds should use HTTPS.

## Checks

```bash
flutter analyze
flutter test
flutter build apk --debug --dart-define-from-file=dart_defines.local.json
flutter build ios --debug --no-codesign --dart-define-from-file=dart_defines.local.json
```

## Android prerelease APK

```bash
flutter build apk --release --dart-define-from-file=dart_defines.local.json
```

The APK is written to `build/app/outputs/flutter-apk/app-release.apk`. Android
release builds currently use the development signing key and are for testing;
configure a protected release key before distributing through Google Play.
Keep local Dart defines, signing keys, and generated build folders out of Git.
Upload APKs as GitHub release assets alongside the source commit.

Build `1.0.2+3` uses the existing local API configuration. At packaging time,
`https://mobile-api.kainattravels.com/api/mobile/v1/app/config` returned HTTP 404.
The matching mobile backend must be deployed and reachable for API features to
work. OTP, payments, and account deletion also require the backend setup described
in `docs/`.
