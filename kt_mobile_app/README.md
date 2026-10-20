# Kainat Travels Passenger App

Flutter passenger application for Android and iOS. Runtime settings are supplied with Dart defines; no service credentials belong in the app.

## Run locally

Copy `dart_defines.example.json` to a local, ignored settings file and adjust `API_BASE_URL` for your device. Android emulators normally reach the host at `http://10.0.2.2:8000`; iOS simulators can normally use `http://127.0.0.1:8000`.

```bash
flutter pub get
flutter run --dart-define-from-file=dart_defines.local.json
```

The API base URL must include `/api/mobile/v1`. Production builds should use HTTPS.

After uploading the backend, test it locally with a debug-signed app using the
production API settings (no Play upload key is needed):

Create the ignored `dart_defines.production.json` locally using the settings in
[Android release setup](docs/ANDROID_RELEASE.md#production-build).

```bash
flutter run --debug --dart-define-from-file=dart_defines.production.json
```

## Checks

```bash
flutter analyze
flutter test
flutter build apk --debug --dart-define-from-file=dart_defines.local.json
flutter build ios --debug --no-codesign --dart-define-from-file=dart_defines.local.json
```

## Android release

Configure the private upload key in `android/key.properties` using
`android/key.properties.example`. See [Android release setup](docs/ANDROID_RELEASE.md)
for the existing upload key, application identity, deployment checks, and upload instructions.

```bash
flutter build appbundle --release --dart-define-from-file=dart_defines.production.json
flutter build apk --release --dart-define-from-file=dart_defines.production.json
```

Upload `build/app/outputs/bundle/release/app-release.aab` to Google Play.
The APK is written to `build/app/outputs/flutter-apk/app-release.apk`.
Release builds require upload signing, `APP_ENV=production`, and an HTTPS API URL.
Keep local settings, signing keys, and generated build folders out of Git.

On 19 October 2026 the production `/app/config` endpoint returned HTTP 200.
The separate `mobile-backend` changes still need deployment: mobile authentication
limits, public support contact defaults, and exclusion of JazzCash from new
bookings. New app checkouts offer only API-authorized card or branch payments;
existing JazzCash payments retain status/reconciliation support.

Session restoration preserves saved tokens on connectivity, rate-limit and server
errors and lets startup retry; only HTTP 401 clears an invalid session. Local
sign-out works while the backend is unavailable. Blank optional configuration
values no longer create empty contact actions. Help uses the official contact
website when a phone is unavailable. Mandatory update screens keep their gate and
offer policy refresh and support if the store URL is missing. The iOS display name
is Kainat Travels.

Before passenger release, deploy the backend changes and confirm valid Play/App
Store links for this application's identity, the matching signing credentials,
real OTP delivery, a Bank Alfalah payment/callback/confirmed ticket, and scheduler
reconciliation. Debug builds and fake-gateway tests do not establish live provider
acceptance. No production setup or deployment is performed by a local build.

## Local readiness change files — 19 October 2026

All paths below are inside `kt_mobile_app/` on `mobile-frontend`; pre-existing
branding, fare and payment-navigation changes were preserved.

- Session recovery: `lib/features/auth/data/auth_repository.dart`,
  `lib/features/auth/application/auth_controller.dart`.
- New payment choices: `lib/features/payments/domain/booking_payment.dart`,
  `lib/features/payments/presentation/payment_method_selector.dart`,
  `lib/features/travel/domain/travel_models.dart`,
  `lib/features/travel/data/travel_repository.dart`.
- Configuration, support and update recovery:
  `lib/features/startup/domain/app_config.dart`,
  `lib/features/startup/presentation/startup_screens.dart`,
  `lib/features/profile/profile_screen.dart`,
  `lib/core/widgets/support_links.dart`.
- Native iOS: `ios/Runner/Info.plist`; `ios/Podfile.lock` records the payment
  WebView dependency already present in `pubspec.yaml`.
- Regression tests: `test/session_recovery_test.dart`, `test/payment_test.dart`,
  `test/travel_models_test.dart`, `test/update_test.dart`.
- Documentation: this `README.md`, `docs/ANDROID_RELEASE.md`, `docs/PAYMENTS.md`.

Backend changes are separate in `KTBUSCLONE-mobile-backend` on `mobile-backend`;
its `MOBILE_API.md` lists the files and deployment contract. Pushing these branches
does not deploy the API or apply its database migrations.

## Biometric quick login — 20 October 2026

Optional Face ID / Touch ID / supported Android biometrics are available from
Account after password confirmation and mobile verification. The server renews
the existing 10-hour access session for up to 30 days from enrollment by default.
See [BIOMETRIC_LOGIN.md](docs/BIOMETRIC_LOGIN.md) for platform protection, behavior,
API rollout and real-device verification.
