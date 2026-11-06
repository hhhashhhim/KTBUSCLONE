# Play Data safety evidence — 6 November 2026

Review worksheet for `com.kainattravels.kt_mobile_app`, Android **1.0.8 (9)**.
Repository: `hhhashhhim/KTBUSCLONE`; documentation branch: `mobile-frontend`.
This file prepares answers; it does not submit or certify a Console declaration.

## Release and Console facts

- Frontend source **9fbe332e** was pushed to `mobile-frontend`. Backend **8abbda840b4e4b171a0c8eeccb20b25e6b9fe584** was pushed to `mobile-backend` and deployed by a fast-forward-only update. The mobile API is up and `/api/mobile/v1/app/config` returned HTTP 200 after deployment. See [the current build/rollout report](BUILD_REPORT_1.0.8_2026-11-06.md) and `mobile-backend:MOBILE_RELEASE_2026-11-06.md`.
- **Historical preflight baseline:** `b78d1cc1` had snapshot-linking, schema-hardening and first-login migrations in batches **103, 104 and 105**. The follow-up quote-index repair is now recorded in **batch 106**; the existing index already matched `used_at, expires_at, id`, so no index DDL was needed.
- `MOBILE_SCHEDULER_ONLY=true` is deployed and the schedule contains exactly **six mobile commands**. Only the mobile notification-only cron was replaced with `schedule:run`; the separate ERP cron was preserved byte for byte. Four normal launches each of reconciliation and notifications were observed, with zero generic scheduler error markers and zero active mobile PHP jobs at the final observation. Maintenance was false and approved cron bytes matched. No manual scheduler was invoked; redirected command output means these observations do **not** prove provider outcomes. Force-update remains false. Sanitized proof: `build/play-release/1.0.8-9/backend-rollout-proof.jpg`.
- **Scheduled personal-data retention remains disabled.** Configured 90-day periods are not evidence of active expiry. Immediate account deletion and scheduled retention are separate operations.
- The protected rollout backup at `/home/wwwkaina/private-release-backups/mobile-20261106-em5e8J` requires manual deletion after **2026-11-13T00:00:00Z**. This seven-day rollout copy is separate from the owner-confirmed **30-day hosting backups**; neither establishes expiry for every export, replica or provider copy. No automatic deletion of the rollout copy is claimed.
- The deployed ERP notification writer still has the deletion race documented below. The owner explicitly requested **Keep ERP read-only**; no ERP fix was applied.
- Play internal release **1.0.8 (9)** is uploaded and published: the track shows **Active**, latest release **1.0.8**, and **Available to internal testers (not reviewed)**. The exact previously verified AAB was used; the existing three testers and support details are unchanged. Release review showed min API 24, target API 36, mapping file/native symbols attached, and no blocking errors or warnings. Proof: `build/play-release/1.0.8-9/play-internal-1.0.8-active.jpg`. [Tester opt-in](https://play.google.com/apps/internaltest/4701697187779665820). Public production and account/trader declarations remain pending; internal publication does not complete Data safety or functional acceptance.
- The Data safety form remains a **step-2 draft**: collection **Yes**, username/password/other authentication selected, deletion URL supplied; the all-data-in-transit answer and complete category mapping remain unanswered. This worksheet changes no form fields.
- The owner states that SAR Zone and Bank Alfalah retain no data. Record this as an **owner assertion about those providers**, not independently verified provider evidence and not an assertion about Firebase, hosting, Kainat's database or backups.

## Answers supported for review

| Console item | Proposed answer / exact limit |
| --- | --- |
| Does the app collect user data? | **Yes.** Registration, booking, account operations and push registration transmit data off the device. |
| Account creation | **Username, password and other authentication**, the combined option already saved in the draft for password and verification-code/biometric-assisted flows. Mobile number is the login identifier; biometric templates stay on the device. No Google/Facebook/OAuth sign-in is implemented. |
| Account-deletion URL | **https://www.kainattravels.com/delete-account**. In-app path: Account → Delete account. The mechanism exists; do not state that all financial, provider and backup data is immediately erased. |
| Encryption in transit | **Yes is supported for the reviewed Android app's device-to-server paths:** production API requires HTTPS, the release manifest disables cleartext, payment WebView navigation requires HTTPS, and Firebase documents HTTPS. This is a proposed answer based on those controls, not a certificate of provider/hosting operations. Confirm no additional live collection destination bypasses them. Database-at-rest/backup encryption is a different question. |
| Ephemeral processing | **No** for stored identity, account IDs, booking/payment history, login/read events and FIDs. Search-request-only processing requires the logging check below; do not label it ephemeral based solely on absence of a dedicated search-history table. |
| Data shared with third parties? | **Resolve per destination below.** Neither “providers retain none” nor “we do not sell data” determines Google's sharing classification. Do not fill every category as not shared by default. |
| Independent security review / certification | No completed independent assessment is evidenced by this work. Local tests and signed builds do not establish one. |

Google's declaration covers off-device SDK collection. Its service-provider exception depends on processing solely on the developer's behalf/instructions. Optional collection must be optional for every applicable user; booking identity cannot be called optional just because browsing is possible. Google's transit question concerns the flow from the user's device to servers. [Google Play guidance](https://support.google.com/googleplay/android-developer/answer/10787469).

Transport evidence: [environment validation](../lib/core/config/app_environment.dart), [API origin guard](../lib/core/network/api_client.dart), [release manifest](../android/app/src/release/AndroidManifest.xml), [payment navigation](../lib/features/payments/presentation/payment_checkout_screen.dart), and the checked 1.0.8 artifact manifest. Backend outbound OTP/payment/push paths also use HTTPS; this does not resolve all host-managed transfers.

## Category worksheet

“Required” below describes the primary account/booking service. “Shared” remains subject to the destination assessment; it is not silently answered No. These are source-based mappings for owner review, rather than an assertion that code reveals every provider practice.

| Play category | Collected / required or optional | Supported purposes | Data and destination evidence |
| --- | --- | --- | --- |
| Personal info → Name | **Yes; required** for registration and each booked traveller. Saving an additional traveller is optional. | App functionality; Account management. | Full name → mobile API/shared ERP on hosting. Registration and quote validators [B1, B2]. |
| Personal info → Email address | **Yes; optional.** Registration/profile allow null. | Account management. Add Advertising or marketing only if the promotional-email use described in the policy is actually performed. | Email → API/ERP; any operational email/marketing processor requires owner identification [B1, F1]. |
| Personal info → Phone number | **Yes; required** for signup/login and traveller booking details. | App functionality; Account management; Fraud prevention, security, and compliance for verification. | Mobile number → API/ERP; OTP destination → SAR Zone/WhatsApp. Bank checkout may separately ask for contact details [B1–B3]. |
| Personal info → User IDs | **Yes; required.** | App functionality; Account management; Fraud prevention, security, and compliance. **Analytics** also applies to account-linked login reporting. | Passenger/account IDs, CNIC identity number, authentication/session identifiers → API/ERP; OTP purpose/subject reference → SAR Zone; account IDs link login events [B1–B4]. CNIC belongs in the identity inventory even if Console wording differs. |
| Personal info → Other info | **Yes; required for booking gender; optional for profile-only gender.** The required booking use governs the category answer. | App functionality; Account management for profile/saved traveller fields. | Gender → API/ERP. Profile accepts null; quote requires a supported value [B2]. |
| Financial info → Purchase history | **Yes; required** for a reservation/payment. | App functionality; Fraud prevention, security, and compliance. | Route/date/seats/fare, ticket/invoice, payment method/reference/status and related loyalty activity → API/ERP. Transaction reference, amount/currency and return URL → Bank Alfalah [B5]. |
| Financial info → User payment info | **Provider-dependent; do not answer from “WebView” alone.** Card entry occurs only when card payment is chosen. | App functionality if this category must be declared. | No Flutter card field reader or JavaScript bridge; hosted form goes to Bank Alfalah. Check the payment exception below before selecting or omitting this category [F2, B5]. |
| App activity → App interactions | **Yes; required for login events and inbox read-state behavior** when those functions are used; no separate telemetry opt-out is implemented. | App functionality; **Analytics** for login/engagement and read-count reports; Account management for login history. | Login kind/time/first-login marker and notification read time → API/ERP [B4, B6]. No Analytics SDK is needed for this backend collection. |
| App activity → In-app search history | **Yes, search inputs leave the device; required for schedule search.** Persistence/ephemeral answer remains open. | App functionality. | Origin, destination, travel date and schedule/seat queries → API; authenticated requests can carry an account token. Determine whether host logs retain query strings [F3]. Selected journey cities are not proof of device location collection. |
| Device or other IDs | **Yes.** Explicit account-to-FID binding follows notification eligibility/permission; do **not** yet mark the entire SDK category optional. | App functionality for alerts; Developer communications for company/app announcements. Add Advertising or marketing if notifications promote services. | FID/platform → API; FID and generic notification/notice ID → Firebase. Fresh-install behavior before permission and on older Android must establish SDK-wide optionality [F4, B7]. |
| Other financial info | **Review loyalty semantics.** Purchase-linked points belong in the financial inventory; select this additional category only if the service handles another financial balance/value not adequately described by purchase history. | App functionality where applicable. | Travel Points/wallet screen is API-backed; no bank balance or credit-score collection is established [F5]. |

No source evidence establishes collection of device GPS, address-book contacts, calendar, camera/photos, microphone recordings, biometric templates, sexual orientation, race/religion or credit scores. Do not select them merely because passenger names, gender, bus images or biometric login exist. Likewise, do not infer “no diagnostics anywhere”: host/security logs and provider checkout behavior still need the targeted checks below. The Android manifest and [dependencies](../pubspec.yaml) have no declared Analytics, Crashlytics or Performance Monitoring SDK; Firebase analytics collection is disabled.

## Provider handling and remaining decisions

| Destination | Confirmed transmitted data / present conclusion | Exact remaining input |
| --- | --- | --- |
| Kainat API/ERP on hosting | Identity, booking/payment, login/read history and device bindings. Hosting is a candidate service-provider exception; the code cannot prove its contractual scope. Retention is currently disabled. | Confirm host processing is only on Kainat's instructions, effective access/security-log retention and query-string contents. Record the approved shared-record retention periods and backup restoration/deletion procedure. |
| SAR Zone / WhatsApp delivery chain | Recipient mobile, verification code/template/language and purpose/subject reference over HTTPS. Owner asserts zero provider retention. Collection by Kainat remains Yes because identity/account data is stored. | Does that assertion cover SAR Zone logs/backups and the downstream WhatsApp provider? Is processing solely on Kainat's instructions, or is there any independent use? This decides the sharing exception; no-retention alone does not. |
| Bank Alfalah | Transaction reference, amount/currency, return URL and merchant checkout fields. The app does not access card-entry fields. Owner asserts zero provider retention; this has not been reconciled with bank terms/transaction obligations. | Confirm provider terms govern card collection directly, and whether any card/account/fraud details return to Kainat or its logs. Identify independent bank uses versus processing on instructions. Do not apply a blanket exception to purchase history. |
| Firebase Cloud Messaging / Installations | FIDs are collected; backend pushes contain a generic preview and notification ID, not traveller or booking details. Firebase's published retention contradicts a general zero-retention statement. | Confirm enabled Firebase products, linked services, BigQuery/export and service-data settings. Verify the effective FID deletion path and no-permission initialization behavior. Sharing classification needs the actual configured use/terms. |
| Support/email providers | App links open support channels; the privacy policy also mentions promotional email. The current source does not inventory the support inbox/CRM or marketing system. | Identify systems actually receiving app-related support/contact data and whether any promotional communication runs. Add corresponding data/purposes only for real processing. |

For hosted payment data, Google permits exclusion where the app never accesses it and the payment provider collects directly under its own terms. The code supports the first condition; provider-term confirmation is still needed. This exception does not remove purchase-history collection. [Google payment-service guidance](https://support.google.com/googleplay/android-developer/answer/10787469#zippy=%2Cdata-collection).

Firebase states that FCM installation IDs remain until an API deletion request; removal from live and backup systems can then take **up to 180 days**. This is incompatible with “Firebase retains nothing.” [Firebase privacy](https://firebase.google.com/support/privacy). Installation deletion is a specific API operation; deleting Kainat's database binding or unregistering messaging is not, by itself, proof it occurred. FID-generating services can create a new ID. [Installation deletion](https://firebase.google.com/docs/projects/manage-installations#delete_a_firebase_installation).

The app calls messaging `unregister()` and deletes its API device binding [F4]; no explicit Firebase Installations `delete()` call was found in application code. Confirm the pinned SDK's behavior before promising provider erasure. Firebase's Android disclosure describes FID/user-agent collection, HTTPS transport, and configuration-dependent delivery metrics. Its SDK page covers current versions, so check the resolved build version/configuration rather than assuming every optional feature runs. [Firebase Android disclosure](https://firebase.google.com/docs/android/play-data-disclosure).

## Deletion and retention: claims that remain blocked

The deployed account-deletion code locks the account and removes mobile private rows, credentials, inbox/device/delivery records and login history. It retains shared ERP customer/ticket/payment/loyalty records and a limited deletion ledger [B8]. That is narrower than erasing all personal data everywhere.

The **unchanged live ERP campaign writer** can insert an inbox row using a recipient selected before deletion. A reviewed patch exists outside the repositories but was not applied under the owner's read-only instruction. Therefore, do not claim all writers prevent re-creation immediately after deletion. Scheduled cleanup also cannot be represented as a current guarantee while retention is disabled.

Before final submission, resolve these five decisions:

1. Approve accurate per-destination sharing classifications, covering the provider handling above and actual marketing use.
2. Establish FID collection optionality and provider deletion behavior; retain the published Firebase retention qualification.
3. Decide how to close the ERP writer race while honoring the current read-only instruction, and test deletion using a disposable approved account.
4. Approve and activate the applicable retention policy, or disclose current retention truthfully. The mobile-only scheduler is deployed and normal cron launches are observed, but retention remains disabled. Do not turn configured 90-day values into an active deletion promise. Reconcile the policy's financial-year language with actual ERP retention and record deletion of the separate seven-day rollout copy when performed.
5. Verify the final release's real payment/provider path and the existence of no additional data destination before confirming the transit answer; determine search-log persistence and support/email processor coverage.

These are precise declaration/operation gaps. Local analysis/tests, migration batches and successful signed builds do not answer them, and this worksheet supplies no invented retention period or certification.

## Source index

Frontend paths are relative to `kt_mobile_app/`. The source audit's backend line
citations below refer to historical baseline `mobile-backend` commit
**b78d1cc18b4580853f224dc43ec1d8ef489b65b4**, rather than claiming that revision is
still deployed. The current deployed follow-up is
**8abbda840b4e4b171a0c8eeccb20b25e6b9fe584**; use the current rollout report for
migration, configuration and cron evidence. No credentials or passenger records
are included.

- **F1:** [privacy policy](../lib/core/legal/privacy_policy.dart), lines 44–48 and 127–139; [authentication payload](../lib/features/auth/data/auth_repository.dart), lines 35–52.
- **F2:** [hosted checkout](../lib/features/payments/presentation/payment_checkout_screen.dart), lines 7–8 and 30–84.
- **F3:** [travel repository](../lib/features/travel/data/travel_repository.dart), lines 71–108.
- **F4:** [push lifecycle](../lib/features/notifications/notification_push.dart), lines 32–58, 87–125, 180–212 and 253–290; [Android manifest](../android/app/src/main/AndroidManifest.xml), lines 10–12.
- **F5:** [wallet presentation](../lib/features/profile/wallet_screen.dart).
- **B1:** `app/Http/Requests/Mobile/RegisterPassengerRequest.php:25`, `LoginPassengerRequest.php:17`, `UpdatePassengerProfileRequest.php:19`.
- **B2:** `app/Http/Requests/Mobile/QuoteBookingRequest.php:29`, `SavePassengerRequest.php:20`.
- **B3:** `app/Services/Mobile/MobileOtpService.php:163`.
- **B4:** `app/Services/Mobile/MobileAppTelemetry.php:28`; ERP reporting: `api:app/Services/MobileAppEngagement.php:28`.
- **B5:** `app/Services/Mobile/MobilePaymentGateway.php:78`, `MobilePaymentService.php:145`, `MobileBookingService.php:409`; `app/Http/Controllers/Mobile/MobilePaymentController.php:45`.
- **B6:** `app/Http/Controllers/Mobile/MobileNotificationController.php:43`; ERP reporting: `api:app/Http/Controllers/Api/MobileAppNotificationController.php:125`.
- **B7:** `app/Services/Mobile/MobilePushGateway.php:37`; `app/Http/Controllers/Mobile/MobileNotificationController.php:57`.
- **B8:** `app/Http/Controllers/Mobile/MobileAccountDeletionController.php:19`; `app/Services/Mobile/MobileAccountDataDeletion.php:12`; `MOBILE_RELEASE_2026-11-06.md` for current live revision, batches and disabled retention.
- **ERP race:** deployed `efb99dcc09a94adb815d947c8c4e1205fdc732e9`, `app/Http/Controllers/Api/MobileAppNotificationController.php:95`; controller SHA-256 `5e038e410a5252f6e7d9aa6533d9c7b51e2018d6c9f4637b050f0c84f4de329a`. Exact source and external regression patch reviewed; ERP kept read-only.
