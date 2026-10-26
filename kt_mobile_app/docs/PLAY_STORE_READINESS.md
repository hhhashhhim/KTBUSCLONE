# Play Store readiness — 26 October 2026

Repository: `hhhashhhim/KTBUSCLONE`, branch `mobile-frontend`.

This is a release checklist, not a claim of Google Play approval. Preserve the
separate frontend, backend and public-website work units.

## Step 1: native privacy policy

Implemented in the current Flutter source. Every `LegalDocument.privacy` action
(signup, Account and account deletion) now opens bundled Flutter text. Reading
does not require authentication, a browser, a WebView, a network or a permission.
Text is selectable and scrollable, with semantic headings and a bounded reading
width. Terms retain their existing behavior.

Content is adapted from <https://www.kainattravels.com/privacy>, retrieved on
26 October 2026. It preserves the website's data categories, uses, sharing,
security, updates and contact information, and expands them for the mobile app.
`lib/core/legal/privacy_policy.dart` is the canonical in-app text. Update its date
when the policy changes. This is bundled content; website changes will not
automatically change installed apps.

Evidence for mobile disclosures:

- Account/profile fields: `lib/features/auth/data/auth_repository.dart`.
- Booking/payment/loyalty records: travel models and `docs/PAYMENTS.md`.
- WhatsApp verification: `docs/WHATSAPP_OTP.md`.
- Optional device registration: `lib/features/notifications/notification_push.dart`.
- Device-local biometric authentication: `docs/BIOMETRIC_LOGIN.md` and native vaults.
- Mobile deletion and retained ERP records: `docs/ACCOUNT_DELETION.md` and the
  existing account-deletion screen. Live deletion has not been verified here.
- Owner confirmation in this chat on 26 October 2026: mobile-account deletion
  is immediate; ticket/payment records are kept for the full financial year for
  tax reporting; call-centre and `info@kainattravels.com` requests result in account
  deletion. These statements are now included in the native policy. The call-centre
  number is the published `03-111-777-333`.

Privacy is **not fully cleared for submission** yet:

1. Ticket/payment retention purpose and period are owner-confirmed. Verify the
   deployed retention/purge process and clarify the exact financial-year boundary
   before promising a specific deletion date. Customer details, loyalty records,
   deletion audit, backups and service-provider copies still need defined retention
   periods/criteria and legitimate reasons. The app accurately discloses the
   existing deletion boundary. Implement any necessary changes separately on
   `mobile-backend`.
2. Call/email deletion is owner-confirmed and described in the app. Publish the
   same readily discoverable instructions on an external page and verify their
   fulfillment. No real support request or production deletion was sent here.
3. Publish matching text at the public policy URL in the approved website work
   unit and enter it in Play Console. A native policy does not replace that URL.
4. Reconcile the release's enabled services and actual backend/provider handling
   with the policy and Data safety form before declaring compliance.

Google policy: <https://support.google.com/googleplay/android-developer/answer/10144311>

### Changed files and verification

- `lib/core/legal/privacy_policy.dart`: bundled policy text and version date.
- `lib/core/widgets/privacy_policy_screen.dart`: native reading screen.
- `lib/core/widgets/legal_links.dart`: route all privacy actions to native text.
- `test/privacy_policy_test.dart`: offline reading, responsive text and signup
  form/consent preservation.
- `test/account_deletion_test.dart`: reading privacy preserves the deletion form
  and sends no deletion request.
- `docs/PLAY_STORE_READINESS.md`: this release checklist.

Checks run for this change:

- `flutter analyze --no-pub`: passed, no issues.
- `flutter test --no-pub test/privacy_policy_test.dart test/account_deletion_test.dart test/auth_layout_test.dart --reporter expanded`:
  **12 passed**. Includes small phone and landscape at 200% text size, and tablet.
- `git diff --check`: passed.

No new APK/AAB was built or installed for this change. The existing two seat-layout
failures were reproduced during the preceding review, not rerun or fixed by this
privacy-only work. No live account deletion or payment was performed.

## Remaining steps

| Order | Work | Status / owner |
| --- | --- | --- |
| 2 | External account-deletion request page and fulfilled deletion/retention process | Call/email route owner-confirmed; public page and production acceptance unverified. Publish these instructions on the approved website and enter the URL in Play Console. Verify backend retention/deletion separately on `mobile-backend`. |
| 3 | Data safety, content rating, audience, ads and reviewer access | Play Console owner; audit data handling including providers, and supply a working review account/access instructions. |
| 4 | Resolve application identity and upload signing; build current release AAB | App currently uses `com.kainattravels.kt_mobile_app`; intended new vs existing listing unresolved. `android/key.properties` absent at review. Existing APK 1.0.2 is debug-signed and not the current 1.0.5+6 source. |
| 5 | Release quality and device compatibility | Both layout failures were fixed during build preparation; all 201 Flutter tests now pass. Resolve the booking gender contract mismatch from `QA_REPORT_2026-10-26.md`; verify complete deployed booking/payment/deletion flows and 16 KB native-library/bundle compatibility. API 36 target is already configured. |
| 6 | Developer verification, package registration, listing and required testing | Confirm in Play Console. Personal accounts created after 13 November 2023 require at least 12 testers continuously opted in for 14 days before applying for production access. |

References:

- Account deletion: <https://support.google.com/googleplay/android-developer/answer/13327111>
- Review setup: <https://support.google.com/googleplay/android-developer/answer/9859455>
- Release: <https://support.google.com/googleplay/android-developer/answer/9859348>
- API target: <https://support.google.com/googleplay/android-developer/answer/11926878>
- 16 KB compatibility: <https://developer.android.com/guide/practices/page-sizes>
- Registration: <https://support.google.com/googleplay/android-developer/answer/16984799>
- Personal-account testing: <https://support.google.com/googleplay/android-developer/answer/14151465>

No backend, public website, Play Console, signing key, commit or deployment is
changed by the native privacy-screen implementation.
