# Passenger notifications

Source: `hhhashhhim/KTBUSCLONE`, Flutter branch `mobile-frontend`.
The mobile API lives on `mobile-backend`; ERP endpoints on `api` and screens on
`mix`. Deploy those layers separately against the same company database.

## Passenger experience

- Alerts shows the signed-in passenger's inbox, unread badge, pagination and
  pull-to-refresh. Opening an alert marks it read. Booking alerts link to My Trips.
- “Mark these and older as read” uses the newest ID on the current page; it does
  not mark messages that arrived after that page loaded.
- ERP can send announcements to selected passengers, a departure's passengers,
  or all active company accounts. Automatic alerts describe recorded booking,
  payment, cancellation, seat, departure and refund-record changes, plus a
  departure reminder. A refund-record update does not claim a money transfer.
- Phone alerts are optional. The passenger presses **Enable** in Alerts to grant
  phone permission. Denial or unavailable push leaves the inbox usable.
- Lock-screen text is generic. Tapping opens the private message only after
  authentication and any biometric/update/maintenance gate.
- While the app is open, a generic in-app alert offers **View alerts** and refreshes
  the inbox. A closed-app tap waits until splash/login navigation has finished,
  so it is not overwritten by the Home redirect. Switching passengers discards
  a pending tap for the previous passenger.
- Device registration errors return promptly, stalled SDK requests time out after
  30 seconds, and registration can be retried. A new access session rebinds the
  device even when the same passenger signs in again.
- Logout unregisters the installation. The server checks the account's controls
  and current access session before push delivery; the normal session expires
  after 10 hours unless renewed through the existing login flow.

## Firebase setup

Firebase Cloud Messaging (FCM) is Google's service for delivering phone alerts.
For iOS, it forwards them through Apple's APNs service. This integration uses
`firebase_core` 5 and `firebase_messaging` 17, with installation registration
(`register`, `onRegistered`) and the FCM HTTP v1 `fid` target.

1. Create or select the Kainat Travels Firebase project.
2. Register Android package `com.kainattravels.kt_mobile_app` and iOS bundle
   `com.kainattravels.ktMobileApp`. Verify Android's `applicationId` in
   `android/app/build.gradle.kts` before registering.
3. Enable Cloud Messaging and configure the iOS app's APNs authentication key
   using the correct Apple team and key ID. Keep the Apple private key out of Git.
4. Copy the **public app configuration** values into the existing ignored
   `dart_defines.production.json` or `dart_defines.local.json`, alongside the
   existing API URL/environment keys:

   ```json
   {
     "FIREBASE_PROJECT_ID": "your-project-id",
     "FIREBASE_MESSAGING_SENDER_ID": "your-project-number",
     "FIREBASE_ANDROID_API_KEY": "your-public-android-api-key",
     "FIREBASE_ANDROID_APP_ID": "your-android-firebase-app-id",
     "FIREBASE_IOS_API_KEY": "your-public-ios-api-key",
     "FIREBASE_IOS_APP_ID": "your-ios-firebase-app-id"
   }
   ```

5. Configure the private server service account as documented in
   `MOBILE_NOTIFICATIONS.md` on `mobile-backend`. **Never put a service-account
   JSON, private key, or server bearer token in the Flutter app.**
6. Rebuild with that settings file and the existing release-signing process.

For Android, select the client in the downloaded `google-services.json` whose
`client_info.android_client_info.package_name` matches the application ID. Import
these public values into the chosen Dart defines file:

| Dart define | Source field |
| --- | --- |
| `FIREBASE_PROJECT_ID` | `project_info.project_id` |
| `FIREBASE_MESSAGING_SENDER_ID` | `project_info.project_number` (as a string) |
| `FIREBASE_ANDROID_API_KEY` | Matching client's `api_key[0].current_key` |
| `FIREBASE_ANDROID_APP_ID` | Matching client's `client_info.mobilesdk_app_id` |

The Android Gradle build generates Firebase's native string resources from the
same four Dart defines. This lets `FirebaseInitProvider` initialize Firebase for
closed-app notification delivery before Flutter starts, without a separate copy
of `google-services.json` in the project. Builds without all four values omit
those resources and keep the inbox-only fallback. The Android manifest enables
Firebase installation-ID registration; messaging auto-init remains disabled.
For an Android test build with the production configuration, run:

```sh
flutter build apk --debug --dart-define-from-file=dart_defines.production.json
```

Importing Android configuration does not configure the server sender or iOS.
iOS additionally needs its Firebase app values and APNs key/signing setup.
`FirebaseMessagingInstallationIdEnabled=true` is included in `Runner/Info.plist`
for this registration API. Enable the backend sender separately on `mobile-backend`, then
verify delivery on a dedicated passenger's physical device.

No Firebase project is hard-coded. Missing/invalid initialization falls back to
the inbox. `features.notifications` enables the inbox; `features.push_notifications`
additionally reports that the backend has push configuration. These flags do not
prove a successful real-device delivery.

The current Firebase Apple SDK requires **iOS 15 or newer**. Push and Background
Modes capabilities and the `aps-environment` entitlement are present; the Apple
App ID and signing profile must also permit push. Android requests notification
permission where required. Native auto-init is disabled so installation
registration follows the passenger's permission and authenticated state. On iOS,
the app enables SDK auto-init only when registering after those checks, allowing
the plugin to request/forward the APNs token. It waits for that token before
explicit FCM registration. Logout disables auto-init and unregisters the device;
a logout during the APNs wait prevents the delayed explicit registration.

## Runtime contract

All requests use the configured `/api/mobile/v1` base and passenger bearer token.
The existing API envelope/error handling is unchanged.

| Method | Path | Body/query |
| --- | --- | --- |
| GET | `/notifications` | `paginated=1&page=1&per_page=20` |
| GET | `/notifications/{id}` | Owned message only |
| POST | `/notifications/{id}/read` | No body |
| POST | `/notifications/read-all` | `{"through_id":123}` |
| PUT | `/notifications/device` | `{"token":"firebase-installation-id","platform":"android"}` |
| DELETE | `/notifications/device` | `{"token":"firebase-installation-id"}` |

The paginated response contains `items`, `unread_count` and `pagination`
(`current_page`, `last_page`, `total`). Items have numeric `id`, `kind`, `title`,
`body`, nullable `booking_id`, nullable ISO `read_at`, and ISO `created_at`.
Old clients can still request `/notifications` without `paginated=1` for a list.
The device field retains the name `token`; it carries the Firebase installation ID.
Push data contains only `notification_id`, never a booking or passenger record.

## Verification before release

Automated tests use fake transport and disposable databases. Debug Android and
iOS simulator compilation verifies integration, not provider delivery.

On a configured real Android/iPhone, verify permission allow/deny, foreground
inbox refresh, background/closed-app delivery, tap after biometric lock, expiry,
logout/account switching, and revocation/blacklist. Send a test announcement only
to a dedicated test passenger. Confirm the inbox and ERP read count independently
of FCM acceptance. Do not broadcast for testing.

### Verification on 26 October 2026

- Repository `hhhashhhim/KTBUSCLONE`, branch `mobile-frontend`; changes are inside
  `kt_mobile_app/`. The separate `mobile-backend` checkout was inspected and tested
  without source changes.
- The live `GET /api/mobile/v1/app/config` returned HTTP 200 with
  `features.notifications=true` and `features.push_notifications=true`.
  Firebase Console showed Cloud Messaging API V1 enabled. Its Android package,
  app ID and project match this app's ignored production Dart defines.
- The earlier QA report describes an older configuration snapshot. The current
  production defines contain Android Firebase settings; local development defines
  and iOS Firebase values remain unconfigured. Firebase Console currently lists
  only the Android app.
- Reproduced and fixed dropped cold-start/gated notification taps, delayed SDK
  failures, and unhandled registration-stream errors. Added a visible foreground
  alert, session-aware inbox refresh/rebinding, and Apple registration prerequisites.
- `flutter test --no-pub --reporter expanded`: **213 passed**. The 22 focused
  notification checks include timeout/retry, SDK/stream failures, APNs readiness,
  logout during registration, account changes, cold start, login/update gates,
  and foreground presentation.
- `flutter analyze --no-pub`: no issues. Android debug APK built with production
  Dart defines; generated and packaged native Firebase resources match those
  defines. Packaged notification permission, Firebase initialization provider,
  messaging receiver and FID opt-in are present. APK signature verification passed
  using Android Studio's bundled JDK (the shell's default Java was unavailable).
- iOS simulator debug build passed with the same production defines. This verifies
  compilation, not iOS delivery; the iOS Firebase values/APNs setup remain pending.
- On `mobile-backend`, `php -d error_reporting=22527 vendor/bin/phpunit
  tests/Feature/MobileNotificationsTest.php`: **18 passed, 77 assertions** using
  disposable SQLite and fake provider HTTP. These are not real-device sends.
- Physical-device acceptance is still pending. The requested recipient is Hashim
  on Android. No Android phone was connected during these checks, and the cPanel
  session required sign-in. No test push, production mutation, deployment, commit,
  or push to Git was performed.

For the Android acceptance run, install the updated
`build/app/outputs/flutter-apk/app-debug.apk`, sign in as the agreed test passenger,
open **Alerts → Enable**, and allow Android notification permission. Confirm the
device binding on the server, send one announcement only to that passenger, and
check foreground, background/closed-app display and tap-through. Check the worker's
last run and provider acceptance separately; do not run the whole production
notification worker merely to test one passenger.

### Live follow-up on 27 October 2026

- The user selected an Android emulator for the targeted Hashim acceptance test.
  The existing `medium_phone` AVD uses an Android 36 Google Play system image.
  It booted successfully, has Google Play services installed, and accepted the
  updated debug APK (`1.0.5`, build `6`). Live FCM delivery and notification
  tap-through passed on this emulator.
- The signed-in cPanel terminal confirmed that `/home/wwwkaina/kt-mobile-api`
  uses `hhhashhhim/KTBUSCLONE` on `mobile-backend`. Existing server changes were
  left intact. Firebase sender configuration is enabled, the service-account
  project matches the Android app, and its private key parses successfully.
  No credential values were copied into this report.
- At the initial server check, the agreed passenger account was verified but had
  no registered push device. The company's device count was zero and its
  notification-runtime row was absent.
- The initial cron ran the ERP scheduler in `api.kainattravels.net`; that
  scheduler does not invoke `mobile:notifications`. There was no cron for the
  mobile API checkout. The deployed mobile notification command exists and
  `/usr/local/bin/php` is PHP 8.2.35.
- With the user's explicit approval, the existing crontab was backed up and a
  once-per-minute `mobile:notifications` job was added for the mobile checkout.
  The ERP cron was preserved. A second complete Laravel scheduler was avoided
  because it would duplicate unrelated ERP jobs. The first mobile run completed
  at 16:55:11 PKT and subsequent scheduled runs advanced `last_run_at`.
- Hashim was already signed in on the emulator. **Alerts → Enable** and Android
  permission consent registered one Android device for that account. No account
  credentials were changed and no session was minted through the server.
- One idempotent test inbox message was recorded through the existing
  `MobileNotificationInbox` service after checking the exact account, account
  controls, verification, device count/platform and current access session. Its
  event key is `qa:android:2026-10-27:hashim:one`, notification ID `1`. The normal
  scheduled worker submitted it to Firebase, which accepted it on attempt 1 at
  16:57:06 PKT. No broad test announcement was created.
- The emulator had resumed an old saved state whose logs contained Google
  messaging connection timeouts. Delivery was delayed until after a reboot and
  Google messaging reconnection. The same queued message reached the app receiver
  at 17:01:10 PKT; no second test was sent. Android displayed the generic preview
  while the app UI was closed. Tapping it launched the app and opened the correct
  private test message. The server recorded `read_at=2026-10-27 17:02:21` and the
  delivery remained accepted with one attempt. This verifies the native delivery,
  cold-start routing, authenticated detail fetch and read-receipt path together.
- Screenshot evidence is in the ignored `build/notification-qa/` directory:
  `cron-enabled.png`, `android-enabled.png`, `android-delivery.png`,
  `android-opened.png`, `provider-accepted.png`, `server-read-confirmed.png`.
- Physical-phone acceptance remains pending. Foreground, account-switching and
  gate behavior are covered by automated tests, not additional live sends in this
  run. iOS Firebase app/APNs configuration and device acceptance remain pending.
  No source was deployed, committed or pushed; the approved production changes
  were the worker cron, emulator device binding and single test inbox message.

Files changed for this verification/fix (relative to `kt_mobile_app/`):

- `lib/app/app.dart`
- `lib/features/notifications/notification_push.dart`
- `lib/features/notifications/notification_providers.dart`
- `ios/Runner/Info.plist`
- `test/notifications_test.dart`
- `test/notification_delivery_test.dart`
- `test/firebase_push_client_test.dart`
- `docs/NOTIFICATIONS.md`

Official references:
[Flutter FCM setup](https://firebase.google.com/docs/cloud-messaging/flutter/get-started),
[Android native configuration](https://firebase.google.com/docs/projects/multiprojects#support_multiple_environments_in_your_android_application),
[receiving messages](https://firebase.google.com/docs/cloud-messaging/flutter/receive-messages),
[HTTP v1 message fields](https://firebase.google.com/docs/reference/fcm/rest/v1/projects.messages).
