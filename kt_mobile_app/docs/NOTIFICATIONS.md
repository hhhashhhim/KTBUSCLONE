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
iOS additionally needs its Firebase app values, APNs key/signing setup, and
`FirebaseMessagingInstallationIdEnabled=true` in `Runner/Info.plist` for this
registration API. Enable the backend sender separately on `mobile-backend`, then
verify delivery on a dedicated passenger's physical device.

No Firebase project is hard-coded. Missing/invalid initialization falls back to
the inbox. `features.notifications` enables the inbox; `features.push_notifications`
additionally reports that the backend has push configuration. These flags do not
prove a successful real-device delivery.

The current Firebase Apple SDK requires **iOS 15 or newer**. Push and Background
Modes capabilities and the `aps-environment` entitlement are present; the Apple
App ID and signing profile must also permit push. Android requests notification
permission where required. Native auto-init is disabled so installation
registration follows the passenger's permission and authenticated state.

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

Official references:
[Flutter FCM setup](https://firebase.google.com/docs/cloud-messaging/flutter/get-started),
[Android native configuration](https://firebase.google.com/docs/projects/multiprojects#support_multiple_environments_in_your_android_application),
[receiving messages](https://firebase.google.com/docs/cloud-messaging/flutter/receive-messages),
[HTTP v1 message fields](https://firebase.google.com/docs/reference/fcm/rest/v1/projects.messages).
