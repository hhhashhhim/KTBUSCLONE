# Play Console handoff — 6 November 2026

> See [the later 1.0.8+9 follow-up](BUILD_REPORT_1.0.8_2026-11-06.md) for the booking
> pagination fix, fresh live backend/account checks and remaining gates. The new
> build is not yet uploaded; the Console history below concerns 1.0.7 (8).

## Confirmed identity and signing

The owner confirmed a **new listing** for `com.kainattravels.kt_mobile_app`.
Internal release: **1.0.7, version code 8**, now **Active** with three authorized
tester accounts selected. The signed AAB was accepted by Google Play and Play App
Signing is active. Do not associate this bundle with the legacy
`com.techhord.kainattravels` listing.

- Console app ID: `4973980219327601890`; developer ID: `5051456537957291644`
  (**Tech Hord / Personal**).
- [Internal testing track](https://play.google.com/console/u/2/developers/5051456537957291644/app/4973980219327601890/tracks/4701697187779665820?tab=testers)
- [Tester opt-in](https://play.google.com/apps/internaltest/4701697187779665820)
- Uploaded AAB SHA-256: `274dbeeaffab7a69172e591255770cc312cdeee9179002ef2cf1f0386d6528b2`.

The privacy URL has been saved in Console. Public support details
(`info@kainattravels.com`, `+923111777333`, `https://www.kainattravels.com`) were
saved with a “Change published” confirmation and verified in Store settings.
Store listing text, a 512 × 512 icon, a 1024 × 500 feature graphic and two
1080 × 1920 phone screenshots are uploaded and saved in the review queue. The
feature graphic's AI label is set. Screenshots are faithful Flutter renders,
not physical-device captures. Travel and local category is saved, and Pakistan-only
production availability is saved and queued for review. Initial setup shows
**10 of 11 tasks complete**; only Data safety remains. Publishing overview keeps
**Send app for review** disabled until dashboard setup is complete. The listed
changes remain queued; no production release has been created or submitted.

An upload key is the private key used to sign bundles sent to Google. Under Play
App Signing, Google holds the app signing key used for APKs delivered to users.
The upload key and app signing key serve different purposes.

Generated locally for this new listing:

- Keystore: `android/upload-keystore.jks`; alias: `upload`.
- Private configuration/password: `android/key.properties`.
- Both files have owner-only permissions and are ignored by Git.

Back up **both** files to an encrypted vault or secure offline backup; backup
completion has not been verified. Do not attach them to a chat, commit them, or put
them on the API server. The public upload certificate can be shared with Play
Console. Play App Signing was enabled with the first internal release. If
Firebase/API-key restrictions depend on the Android signing certificate,
register the **Play app signing certificate** shown in App integrity as well;
the upload certificate alone does not identify Play-installed builds.

## Owner inputs still needed

1. Complete the outstanding trader-status declaration and any Console verification
   tasks. Chrome inspection confirmed **Tech Hord / Personal**; contact email,
   phone and developer email show verified. The production track has **Create new
   release** enabled, and no explicit 12-tester/14-day production-access gate was
   observed. The dashboard still locks the production workflow until initial
   setup is complete. Do not infer closed-test applicability from Personal
   account type alone.
2. Confirm actual provider handling, retained-data periods and production
   transport/log/backup settings described below before completing Data safety.
3. Complete independent reviewer-access and production acceptance checks with
   approved accounts and test data. The owner has confirmed reviewer access
   without OTP or real purchase; current production config still advertises live
   payments. A separate disposable account and approved payment/deletion test
   setup are needed for destructive acceptance scenarios.

## Console findings from Chrome

The new package is created and its internal track is active. The legacy
`com.techhord.kainattravels` app remains removed for a **missing Data safety form**;
additional warnings cover target API and 16 KB pages. `com.kt.kainattravels` is a
separate rejected draft. Creating the requested new listing does not resolve
these existing policy issues. The account also requests a trader declaration by
February 2027. The owner must provide the accurate business/trader declaration.
The owner confirmed the app-creation declarations before they were accepted.
These and the internal release do not complete public app-content declarations.

Saved app-content answers: Government apps **No**; Ads **No** (owner confirmed
only the company's own promotions); Health **no health features**; Financial
features **Rewards, points, frequent flier miles, and other incentives** for
Travel Points. Target audience is **18 and over**: adults book for passengers
of any age. Pakistan-only production availability is saved and queued for review.
Content rating is saved after explicit owner approval of the IARC terms:
**ESRB Everyone, PEGI 3 and IARC Generic 3+**, with no content descriptors.

App access sign-in details are saved. The owner supplied a verified reviewer
account, entered its password directly in Console and confirmed approved data,
no OTP and access to every feature without a real purchase. This has not been
independently tested. Optional Google credential reuse on trusted partner devices
was left off pending explicit authorization. Credentials are excluded from these
documents. See [REVIEWER_ACCESS.md](REVIEWER_ACCESS.md) for remaining checks.

## Saved store listing

- App name: **Kainat Travels**
- Short description: **Book Kainat Travels bus tickets and manage your journeys.**
- Category: **Travel & Local** (saved).
- Privacy URL: `https://www.kainattravels.com/privacy`
- Account deletion URL: `https://www.kainattravels.com/delete-account`

Full description:

Plan your next journey with Kainat Travels. Search available routes and departure
times, choose your seats, and review your fare before reserving.

Pay by card or choose Pay at Branch when offered for your booking. View your trips,
payment status and confirmed tickets in the app. Save passenger details for future
journeys and receive booking notifications when enabled.

Manage your profile and contact Kainat Travels support from the app. For booking
assistance, cancellation, refunds or rescheduling, contact the call centre on
03-111-777-333. Available services and payment options depend on your journey.

Release notes draft:

First Android release with route search, seat selection, passenger details,
booking, supported payment options, trip tickets, account management and alerts.

The saved phone screenshots use Flutter renders. Physical-device acceptance
remains separate; any later ticket screenshot must use approved synthetic data.
Do not include real passenger data, credentials or test payment details in artwork.

## Data safety preparation (owner review required)

The saved partial draft answers collection **Yes**, selects username/password/
other authentication and supplies the account-deletion URL. Transport encryption
and complete data-category mapping remain unanswered. This inventory is not a
submitted declaration. Include web checkout, SDKs, backend logs, hosting and
message/payment providers in the final answers.
Assess collected/shared, required/optional, purposes and retention for each type.

| Data | Observed use | Release decision to confirm |
| --- | --- | --- |
| Name, phone, email, CNIC, gender and account IDs | Registration, profile, saved travellers, booking and support | Personal info categories; email optional; identity/contact fields required for applicable flows; account management and app functionality |
| Journey, fare, ticket and payment records | Reservations, ticket issuance, payment verification and Travel Points where enabled | Purchase history/financial information and other applicable categories; retained tax/business records |
| Card/payment details in hosted checkout | Bank Alfalah page inside the app WebView | Include provider collection even though Flutter does not store card numbers; confirm provider's exact handling |
| Installation/FCM identifiers, platform and delivery information | Optional phone notifications; Firebase configured in the Android build | Device/other IDs and any SDK usage/diagnostic categories; verify Firebase version and configuration |
| Phone number and verification messages | SAR Zone WhatsApp intermediary | Account verification/security; confirm actual provider processing, retention and sharing classification |
| Sign-in events and request IP addresses | Backend login monitoring, booking/payment audit and rate limits | Assess app-activity/security categories and purposes; absence of an analytics SDK does not mean no activity collection |
| Support enquiries and server access/security logs | Email/phone support and backend infrastructure | Owner must inventory actual contents, IP-derived data and purposes; cannot be determined from Flutter alone |
| Biometric templates | Device OS authenticates locally; templates never sent by this app | Do not describe device-local templates as backend collection; review credential storage separately |

No analytics or advertising SDK is declared in the Flutter dependencies; Firebase
analytics collection is disabled in the manifest. The owner confirmed the app
shows only the company's own promotions and Ads **No** is saved. Provider-page
collection and tracking still need to be included in the Data safety assessment.
Service-provider processing may qualify for Google's sharing exceptions only if
the actual arrangement meets the policy; do not automatically answer "not shared."

Both public policy and deletion pages were reachable during the 6 November review.
The app provides in-app deletion and the web page describes email/phone requests.
Still verify actual fulfillment, session revocation and deletion of related data.
The disclosed financial-year retention must match a documented retention/purge
process; define customer/loyalty/audit/backup/provider retention and reasons.

### Backend audit findings blocking final declarations

Read-only source audit: `mobile-backend` commit
`cc2e21d298f194d25fa745b8f9a781a39663aa14`. These findings do not verify the live
deployment, provider contracts or operational retention.

- SAR Zone receives recipient phone, OTP/template fields and a purpose/account
  reference over HTTPS. Bank Alfalah receives transaction reference, amount,
  currency and return URL for hosted checkout; provider-side collection still
  needs confirmation. Sources: `mobile-backend:app/Services/Mobile/MobileOtpService.php:163`
  and `mobile-backend:app/Services/Mobile/MobilePaymentGateway.php:78`.
- Backend monitoring retains account-linked sign-in events; booking/payment audit
  stores request IP and transaction identifiers. Sources:
  `mobile-backend:app/Services/Mobile/MobileAppTelemetry.php:28`,
  `mobile-backend:app/Services/Mobile/MobileBookingService.php:392` and
  `mobile-backend:app/Services/Mobile/MobilePaymentService.php:145`.
- Account deletion retains ERP name/contact/CNIC, ticket/payment/loyalty records
  and a deletion audit. It also leaves notification/device/login records referring
  to the deleted account; their schemas have no cascading account deletion.
  Notification history has no automatic purge. Sources:
  `mobile-backend:app/Http/Controllers/Mobile/MobileAccountDeletionController.php:32`,
  `mobile-backend:database/migrations/2026_10_20_000008_create_mobile_notifications.php:17`,
  `mobile-backend:database/migrations/2026_10_20_000006_create_mobile_app_monitoring.php:11`
  and `mobile-backend:MOBILE_NOTIFICATIONS.md:143`.
- The privacy policy's full-financial-year ticket/payment retention has no matching
  purge process established by this audit. Backend documentation explicitly leaves
  shared ERP/audit retention for business definition. Sources:
  `lib/core/legal/privacy_policy.dart:129` and `mobile-backend:MOBILE_API.md:435`.
- Default logging is a single file without configured rotation; 14 days applies
  only to the optional daily channel. Confirm deployed logging, backup lifecycle,
  hosting identity, provider purposes/retention and transport beyond the app's
  HTTPS connections. Database TLS is configurable, not established by source.
  Sources: `mobile-backend:config/logging.php:51`,
  `mobile-backend:config/database.php:61` and `mobile-backend:MOBILE_DEPLOYMENT.md:225`.

## Testing and release sequence

1. Complete the outstanding trader/account tasks and initial Console setup.
   Package creation, signing, internal upload and
   three-person tester-list setup are complete.
2. Inspect device support and the pre-launch report when available. The release
   preview had no blocking errors; its only warning was the then-empty tester list,
   which has now been configured.
3. Install from Play and run the acceptance scenarios in PLAY_STORE_READINESS.md,
   including push delivery and update behavior with Play's actual signing key.
4. Complete Data safety after the remaining owner/operator inputs;
   check the queued listing and Pakistan-only availability changes. Verify the
   saved App access details independently. Never invent a rating or claim
   production testing is complete.
5. Personal accounts created after 13 November 2023 need a closed test with at
   least 12 testers continuously opted in for 14 days before applying for production
   access. Confirm applicability in Console; internal testing does not replace it.
6. Promote to production only after acceptance and owner approval. The existing
   publication is restricted to the internal test track.

## Separate backend/deployment follow-up

On `mobile-backend`, verify the deployed revision and migrations described in
`MOBILE_DEPLOYMENT.md`, including authentication, deletion, payments and security
controls. Set latest Android version to the accepted candidate and set the Android
store URL to `https://play.google.com/store/apps/details?id=com.kainattravels.kt_mobile_app`
only when that listing is accessible to the intended audience. Keep force-update
off until installation/update works. Choose minimum version from supported-client
policy; do not automatically raise it to the latest version. No server changes
were made in this frontend work unit.

## Primary references

- [Android signing and releases](https://docs.flutter.dev/deployment/android)
- [Play app setup](https://support.google.com/googleplay/android-developer/answer/9859152)
- [Data safety](https://support.google.com/googleplay/android-developer/answer/10787469)
- [Firebase data disclosure](https://firebase.google.com/docs/android/play-data-disclosure)
- [Personal-account testing](https://support.google.com/googleplay/android-developer/answer/14151465)
- [Account deletion](https://support.google.com/googleplay/android-developer/answer/13327111)
