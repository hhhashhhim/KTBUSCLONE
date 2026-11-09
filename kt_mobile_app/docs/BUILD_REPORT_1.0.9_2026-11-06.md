# Android 1.0.9+10 — fare display and destination refresh

Repository: `hhhashhhim/KTBUSCLONE`, branch `mobile-frontend`.
Package: `com.kainattravels.kt_mobile_app`.

Status: **1.0.9 (10) is uploaded and published to Google Play Internal testing**.
The track shows **Active**, **Latest release: 1.0.9**, and **Available to internal
testers (not reviewed)**. Console records release on 6 November 2026 at 22:49.
The existing tester access was not changed. Public production was not submitted.

[Play release details](https://play.google.com/console/u/2/developers/5051456537957291644/app/4973980219327601890/tracks/4701697187779665820/releases/3/details)
and [internal tester opt-in](https://play.google.com/apps/internaltest/4701697187779665820).
Publication proof: `build/play-release/1.0.9-10/play-internal-1.0.9-active.jpg`.
Google Play states that the update usually becomes visible within one hour,
though it can take longer.

## Included changes

- Selected seats show the API's original fare crossed out above the applied fare
  when the applied fare is lower.
- Checkout displays the original fare, applied discount and nonzero adjustments,
  followed by the server's payable total. Wallet redemption alone does not mark
  the original fare as discounted.
- City and destination providers reload after the search screen is disposed.
  **Refresh cities** reloads the catalog and clears old selections without
  restarting the app.
- `pubspec.yaml` advances from `1.0.8+9` to `1.0.9+10`.

Read-only production checks are recorded in [FARES.md](FARES.md). The existing
backend returns Faisalabad from Rawalpindi and supplies the original/discounted
fare fields. No backend code or production configuration change is required.

## Checks actually run

| Check | Result |
| --- | --- |
| `bash tool/build_android_release.sh` | Completed: dependency resolution, analysis, full tests, signed AAB and APK |
| Flutter analysis | No issues |
| Full Flutter suite | 243 tests passed |
| APK signature | Verified; matches the existing upload certificate |
| AAB signature | `jar verified`; existing self-signed/no timestamp and ZIP stream-order warnings remain in the log |
| Official bundletool 1.18.3 validation | Passed; local tool digest matches the recorded official release digest |
| Bundle manifest | Package and 1.0.9/build 10 correct; min API 24, target API 36; debugging, backups and cleartext disabled |
| APK ZIP alignment | 16 KB check passed |
| AAB native libraries | All eight arm64/x86_64 libraries have compatible 16 KB LOAD segments; bundle config is `PAGE_ALIGNMENT_16K` |
| `git diff --check` | Passed |
| Play release review | Ready to release; bundle 10 (1.0.9), mapping and native symbols attached; no blocking error shown; supported-device counts unchanged |
| Play publication | Save and publish completed; Active and available to internal testers |

These are build, automated-test and artifact checks. No new physical-device,
Play-installed, payment or OTP acceptance test was performed for this update.
The existing production HTTPS API, Firebase settings and upload key were reused.
No dependencies were upgraded and no signing identity was changed.

## Artifacts

`build/play-release/1.0.9-10/` resolves to
`/Users/malik/development/kt_mobile_app_build/play-release/1.0.9-10/`.

| File | SHA-256 |
| --- | --- |
| `Kainat-Travels-1.0.9-10.aab` | `069ac3fa1e5528b053a286a192dfcc1a0228a9cb11367ab290b7f9bf802022d6` |
| `Kainat-Travels-1.0.9-10.apk` | `509b7e0ee4bb590040e624318c77cbc8bf2763b50956b29fa1763a9a24ffb874` |

Build logs, validation output, hashes and `verification-summary.json` are in the
same ignored directory. Signing passwords and private keys are not included.
The uploaded AAB is the exact verified artifact listed above. The native file
picker completed the upload without changing browser-extension permissions.

## Files changed

All paths below are relative to `kt_mobile_app/`:

- `lib/features/travel/application/travel_providers.dart`
- `lib/features/travel/domain/travel_models.dart`
- `lib/features/travel/presentation/lovable_search_flow.dart`
- `lib/features/travel/presentation/checkout_widgets.dart`
- `test/city_catalog_test.dart` (new)
- `test/travel_models_test.dart`
- `test/fare_checkout_test.dart`
- `test/seat_layout_test.dart`
- `pubspec.yaml`
- `docs/FARES.md`
- `docs/BUILD_REPORT_1.0.9_2026-11-06.md` (new)

No Git commit or push was requested or performed. Existing unrelated files were
left untouched. Public production publication remains a separate workflow from
this internal-track update.
