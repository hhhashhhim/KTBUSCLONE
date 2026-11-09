# Android 1.0.15 (16) — checkout reservation guidance

Repository: `hhhashhhim/KTBUSCLONE`; branch: `mobile-frontend`.

> Current policy correction: 1.0.15 remains published, but the requirement was
> restored to **1.0.14** at 02:23 PKT after the user reported no Play update on
> their device. See [the current update policy](UPDATES.md). The 02:19 activation
> recorded below is historical and must not be treated as the current setting.

## Release contents

Checkout now identifies the existing pending-reservation-limit response and
shows “Complete your existing reservations” with a “View my trips” action.
That action refreshes bookings and opens My Trips. Other quote failures keep
their existing error and retry behavior, and failed quotes hide the payment
button. Manual points entry, balance limits, and server-returned payable totals
from the previous release are retained.

The clean build snapshot starts from the published 1.0.14 source and includes
the checkout change, current checkout regression tests and version increment.
The unrelated pending terms paragraph remains in the working tree and is not
included. No backend application code or booking limit was changed.

## Verification

- Full Flutter suite: **280 tests passed**.
- Full Flutter analysis: no issues.
- Signed production AAB and APK builds passed.
- APK/AAB signatures and the existing upload certificate passed.
- Bundletool validation passed: package `com.kainattravels.kt_mobile_app`,
  version 1.0.15/code 16, minimum SDK 24 and target SDK 36.
- Debugging, backups and cleartext traffic are disabled in the release manifest.
- Bundle 16 KB configuration, all eight 64-bit native ELF checks and APK
  ZIP alignment passed.
- The source manifest remained unchanged through the build.

No physical-device installation, real payment or 16 KB runtime test is claimed.

## Distribution

Google Play internal release **9**, **1.0.15 (16)**, is **Active / Available to
internal testers**, released on **8 November 2026 at 02:18 Asia/Karachi**.
Play accepted the ReTrace mapping and native debug symbols, reported no loss
of supported devices, and showed the release ready to publish. The existing
tester audience and public production distribution were unchanged.

- [Release details](https://play.google.com/console/u/2/developers/5051456537957291644/app/4973980219327601890/tracks/4701697187779665820/releases/9/details)
- [Tester download](https://play.google.com/apps/internaltest/4701697187779665820)

After availability was verified, company 1's latest and minimum versions were
set transactionally to **1.0.15**, retaining the existing mandatory-update
policy, wallet availability and store URL. A private rollback snapshot was
created, and unrelated configuration fields were verified unchanged. Public
HTTP 200 at **02:19:08 PKT** confirmed the new policy, wallet enabled,
maintenance disabled and `Cache-Control: no-store, private`.

The configuration-only server work was performed on the verified
`mobile-backend` deployment; no backend application files were changed.
Private rollback: `/home/wwwkaina/private-release-backups/checkout-update-policy-20261108-v16.json`.
Private staging: `/home/wwwkaina/private-release-staging/checkout-20261108-v16/`.
Publication completed. Following the device-availability report, the requirement
was restored to 1.0.14; reactivation for 1.0.15 is deferred until the affected
device/account can install it. Installation still requires user consent.

## Artifacts

Directory: `/Users/malik/development/kt_mobile_app_build/play-release/1.0.15-16/`.

| Artifact | SHA-256 |
| --- | --- |
| `Kainat-Travels-1.0.15-16.aab` | `ade072ca7ad547013b6bcb53aae0d99f0bac224062ae773c740d6ebabfa0e20a` |
| `Kainat-Travels-1.0.15-16.apk` | `d302b7cf8db283ae72c0d3656e18694174ba642bccfb85dd83a857c6866f9918` |

Evidence includes `build.log`, `source-manifest.json`, signature/alignment logs,
`verification-summary.json`, `play-release.json`, `play-internal-1.0.15-active.jpg`,
`update-policy.json` and `update-policy-enabled.jpg`. Signing files remain private
and ignored.

## Files and Git state

The checkout fix changes `lib/features/travel/presentation/lovable_search_flow.dart`
and `test/checkout_points_test.dart`; its diagnosis is recorded in `docs/FARES.md`.
Release preparation changes `pubspec.yaml`, `docs/UPDATES.md`, `docs/FARES.md`
and this report. Existing unrelated changes were preserved. Nothing was committed
or pushed; no branch was switched.
