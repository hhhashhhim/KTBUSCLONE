# Play Store readiness — 6 November 2026

> Later follow-up: [Android 1.0.8+9 fixes and fresh checks](BUILD_REPORT_1.0.8_2026-11-06.md).
> Frontend source is pushed, the mobile backend follow-up is deployed, and Play
> internal release 1.0.8 (9) is Active and available to the unchanged three testers
> (not reviewed). Public production and declaration/acceptance gates remain pending.
> The 1.0.7 preparation snapshot below is historical; use the follow-up for current
> deployment, commit and publication status.

Repository: `hhhashhhim/KTBUSCLONE`. Branch: `mobile-frontend`.
Internal release: **1.0.7+8**, package **com.kainattravels.kt_mobile_app**.
The owner confirmed this is a **new Play listing**.

## Decision

The signed AAB is uploaded and the internal test track is **Active**, with three
authorized tester accounts selected. Play App Signing is active. Public release
still requires reviewer-access verification, production acceptance and Play Console
completion below. No public production release, backend deployment, commit or
push was performed.
This report supersedes the readiness checklist dated 26 October; historical QA
and build reports remain available under their original filenames.

## Implemented and checked

- Booking input now explains the confirmed backend gender limitation before
  checkout, offers call-centre assistance and blocks unsupported values before
  saving passengers or navigating. Saved/profile values are preserved rather than
  silently changed. No backend business rule or contract was changed.
- Six regression tests cover profile Other/unknown, a manually selected Other
  with saving enabled, saved Other, and valid male/female continuation.
- Included the pre-existing working-tree API URL/token-origin security guards.
- Generated a new local RSA upload key for the confirmed new listing; private
  signing files have permissions 0600 and remain ignored by Git.
- Built signed AAB and APK with the existing production HTTPS API and Android
  Firebase settings. Production configuration disables development network logs.
- Added a repeatable build command: `bash tool/build_android_release.sh`.

| Check actually run | Result |
| --- | --- |
| `flutter analyze --no-pub` | Passed; no issues |
| Full `flutter test --no-pub --reporter expanded` | **225 passed** |
| New passenger regression suite | **6 passed**; included in full suite |
| Release AAB build | Passed |
| Release APK build | Passed |
| AAB `jarsigner -verify` | `jar verified`; self-signed certificate/no timestamp and ZIP stream-order warnings retained in artifact log |
| APK `apksigner verify --verbose --print-certs` | Passed; v2 signature, new upload certificate |
| Official bundletool 1.18.3 `validate` | Passed |
| Bundle manifest | Correct package, 1.0.7/build 8, target API 36, min API 24; non-debuggable; cleartext and backups disabled |
| Bundle config | `PAGE_ALIGNMENT_16K` |
| All eight arm64-v8a/x86_64 native libraries in AAB | PT_LOAD alignment at least 16 KB and matching offset/address alignment |
| Release APK `zipalign -c -P 16 -v 4` | Passed |
| Build script `bash -n` and `git diff --check` | Passed |

Toolchain: Flutter 3.35.7, Dart 3.9.2, Android build-tools 36.0.0,
Android Studio bundled JDK 21. Bundletool was downloaded from google/bundletool's
GitHub release; its download SHA-256 matched the published asset digest.
Flutter doctor reports some Android SDK licenses unaccepted; installed SDKs were
sufficient for these successful builds. No new license terms were accepted here.

## Artifacts

Files are in `build/play-release/1.0.7-8/`. The existing build symlink resolves to
`/Users/malik/development/kt_mobile_app_build/play-release/1.0.7-8/`.

| File | SHA-256 |
| --- | --- |
| Kainat-Travels-1.0.7-8.aab | 274dbeeaffab7a69172e591255770cc312cdeee9179002ef2cf1f0386d6528b2 |
| Kainat-Travels-1.0.7-8.apk | 5fe4edd71b1b5674ec347a70d1917896fb59bd22421bf200dc6f25da3a8ff272 |

The same directory contains public `upload-certificate.pem`, certificate details,
verification logs and SHA256SUMS.txt. Private key/password files are NOT copied
into this artifact directory. Upload certificate SHA-256:
`2C:3E:E4:67:12:40:1A:67:20:72:CC:8E:47:85:44:06:BB:C9:99:E9:12:3E:76:12:C3:14:AE:B4:A6:08:AB:38`.

The AAB is the Play upload. The APK is for direct device testing. A debug-signed
installation cannot be updated with this upload-signed APK. Use a spare device
or an approved disposable emulator; do not delete an existing installation's data
without its owner's approval. Play-installed APKs use the Play app signing key.

## Observed live and Console state

- Public app/config responded HTTP 200; latest/minimum versions were 1.0.0 and
  Android store URL was empty. Payment environment was live, preview false,
  and push enabled. This does not prove provider acceptance or correct deployment.
- Both public privacy and account-deletion pages responded HTTP 200. The deletion
  page describes in-app, email and call-centre requests. Their fulfillment and
  retained-data handling have not been tested against production.
- Chrome confirmed **Tech Hord / Personal**. Contact verification indicators are
  present; trader status is unset and the account requests a declaration by
  February 2027. Production has **Create new release** enabled and no explicit
  12-tester/14-day production-access gate was observed; the dashboard still locks
  the workflow until initial setup is complete.
- The displayed app list contains the legacy removed app and a separate rejected
  draft alongside the new package. The legacy removal reason is missing Data safety;
  API/16 KB warnings also remain on that old artifact/listing.
- New Console app ID: `4973980219327601890`, in developer account
  `5051456537957291644` (**Tech Hord / Personal**). Internal release 1.0.7 (8) is
  active. [Tester opt-in](https://play.google.com/apps/internaltest/4701697187779665820).
  Play accepted the AAB with no blocking release errors. Three authorized testers
  are selected; tester installation access is separate from passenger login.
- Privacy URL is saved in Console. Public support email, phone and website were
  saved and verified in Store settings. Listing text, 512 × 512 icon, 1024 × 500
  feature graphic and two 1080 × 1920 phone screenshots are uploaded and saved
  in the review queue. The graphic's AI label is set; screenshots are faithful
  Flutter renders, not physical-device captures. Travel and local category is saved.
- Saved declarations: Government apps No; Ads No (owner-confirmed own promotions
  only); no health features; financial rewards/points for Travel Points; target
  audience 18 and over because adults book for passengers of any age.
- App access sign-in details are saved. The owner entered the password directly
  in Console and confirmed a verified account, approved data, no OTP and full
  feature access without a real purchase. These claims await independent testing.
  Optional Google credential reuse on trusted partner devices remains off.
- Pakistan-only production availability is saved and queued for review. Initial
  setup shows **10 of 11 tasks complete**, with only Data safety pending. Content
  rating is saved under explicit owner IARC approval: ESRB Everyone, PEGI 3 and
  IARC Generic 3+, no content descriptors.
- The Data safety partial draft saves collection Yes, username/password/other
  authentication and the deletion URL; transport and full category mapping are
  unanswered. **Send app for review** remains disabled until dashboard setup is
  complete. Listed changes are queued; no production release is created/submitted.
- Read-only audit of `mobile-backend` commit
  `cc2e21d298f194d25fa745b8f9a781a39663aa14` identifies SAR Zone OTP processing,
  backend sign-in/IP collection and retained ERP identities after account deletion.
  Notification/device/login records also remain, without an established purge
  period. The policy's financial-year statement lacks a verified matching purge
  process. Live deployment, logging, backups, transport and provider purposes/
  retention require operator confirmation. See the source citations in
  [PLAY_CONSOLE_HANDOFF.md](PLAY_CONSOLE_HANDOFF.md#backend-audit-findings-blocking-final-declarations).

## Remaining gates and owners

1. Owner: securely back up `android/upload-keystore.jks` and `android/key.properties`.
2. Console owner: complete trader/account tasks and accurate Data safety after
   resolving the backend/operator findings, then review queued listing/country changes
   and any remaining Console requirements. Do not infer closed-testing gates from
   account type alone. See [PLAY_CONSOLE_HANDOFF.md](PLAY_CONSOLE_HANDOFF.md).
3. Owner/backend operator: verify the saved reviewer access and approved journeys;
   supply a separate disposable account and approved payment/deletion acceptance
   setup. See [REVIEWER_ACCESS.md](REVIEWER_ACCESS.md). No production test data
   or authentication bypass was created by this work.
4. QA: accept OTP → booking → payment/branch confirmation → ticket, failure and
   recovery cases, notifications, biometrics, deletion, account isolation and
   Play-installed behavior on physical Android devices. Binary 16 KB checks pass;
   actual 16 KB runtime testing remains pending. The available emulator reports
   4096-byte pages; no runtime acceptance is claimed.
5. Backend deployment work unit (`mobile-backend`): verify deployed security/auth/
   payment/deletion migrations, provider callbacks and reconciliation, retention
   and purge rules. Align latest version and new listing URL once available;
   choose the minimum supported version deliberately and keep force-update off
   until the destination is usable. No Laravel or server configuration was edited.

## Files changed in this preparation

- `lib/features/travel/presentation/lovable_search_flow.dart`
- `lib/features/travel/presentation/passenger_details_widgets.dart`
- `test/passenger_booking_gender_test.dart`
- `pubspec.yaml`
- `tool/build_android_release.sh`
- `docs/ANDROID_RELEASE.md`
- `docs/PLAY_STORE_READINESS.md`
- `docs/PLAY_CONSOLE_HANDOFF.md`
- `docs/REVIEWER_ACCESS.md`

Generated signing files and release outputs are ignored. Existing edits in
`lib/core/config/app_environment.dart`, `lib/core/network/api_client.dart`,
`test/api_security_test.dart` and `docs/SECURITY.md` were preserved and included in
verification; this work did not author those earlier changes. Unrelated root,
Laravel and web files were left untouched. Nothing was committed or pushed.

## Primary references

- [API target](https://support.google.com/googleplay/android-developer/answer/11926878)
- [16 KB compatibility](https://developer.android.com/guide/practices/page-sizes)
- [Android signing](https://docs.flutter.dev/deployment/android)
- [Data safety](https://support.google.com/googleplay/android-developer/answer/10787469)
- [Personal-account testing](https://support.google.com/googleplay/android-developer/answer/14151465)
