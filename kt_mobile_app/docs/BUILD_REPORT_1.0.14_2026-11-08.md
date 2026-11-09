# Android 1.0.14 (15) — manual Kainat Points entry

Repository: `hhhashhhim/KTBUSCLONE`; Flutter branch: `mobile-frontend` at
`/Users/malik/Desktop/Codes/KTBUSGIT/kt_mobile_app/`. The separate backend
worktree and production checkout were verified on `mobile-backend` for the
configuration-only release-policy update. No backend application code was
changed or deployed. Existing pending changes were preserved. Nothing was
committed or pushed, and no branches were switched.

## Released behavior

At checkout, tap the points amount/edit icon to open a numeric entry form.
Apply accepts whole numbers from zero through the available balance; empty,
unparseable and over-limit amounts cannot be applied. The existing +/− controls
remain synchronized. Applying a changed amount requests one fresh server quote;
typing, cancellation and unchanged amounts do not request additional quotes.
The backend remains authoritative for redemption and fare calculation.

The release snapshot contains the previously published 1.0.13 app plus the
points UI and version increment. The unpublished account-deletion paragraph
in the working `lib/core/legal/terms_and_conditions.dart` was left pending and
excluded from this release; the previously published terms were retained in
the isolated build snapshot. The working terms file was not modified.

## Verification

- Full Flutter suite: **276 tests passed**, including four new points tests.
- `flutter analyze --no-pub`: no issues; `git diff --check` passed.
- Signed production AAB and APK builds passed with the existing upload key.
- APK/AAB signatures and expected upload certificate passed.
- Bundletool validation, package `com.kainattravels.kt_mobile_app`, version
  1.0.14/code 15, minimum SDK 24, target SDK 36 and release flags passed.
- Bundle 16 KB alignment, all eight 64-bit native ELF checks and APK ZIP
  alignment passed. The bundle file list matches the previous release.
- Source hashes stayed unchanged during the successful clean build.
- Play accepted the ReTrace mapping and native debug symbols, reported no loss
  of supported devices, and showed the release as ready to publish.

An initial build was stopped when the unrelated pending terms change was
identified. Artifact verification subsequently rejected an incremental bundle
with missing `libapp.so` libraries. A clean rebuild reran the full suite and
passed all checks before upload. Only the final verified bundle was uploaded.
No physical-device installation, real payment or 16 KB runtime test is claimed.

## Publication and update policy

Google Play internal release **8**, **1.0.14 (15)**, is **Active** and
**Available to internal testers**, released on **8 November 2026 at 01:36 PKT**.
The existing tester audience and public production distribution were unchanged.

- [Release details](https://play.google.com/console/u/2/developers/5051456537957291644/app/4973980219327601890/tracks/4701697187779665820/releases/8/details)
- [Tester download](https://play.google.com/apps/internaltest/4701697187779665820)

After tester availability was verified, company 1's latest and minimum supported
versions were set transactionally to **1.0.14**. The prior version was checked,
a private rollback snapshot was written, and unrelated settings were checked
for preservation. Existing `force_update: true` and the Android store URL remain.
Public HTTP 200 at **01:37:19 PKT** verified the new policy, maintenance disabled,
and `Cache-Control: no-store, private`.

- Private rollback: `/home/wwwkaina/private-release-backups/points-update-policy-20261108-v15.json`
- Private staging: `/home/wwwkaina/private-release-staging/points-20261108-v15/`

No release dependency remains pending. Testers must accept Play's installation
prompt; individual account/device availability can lag publication.

## Artifacts

Directory: `/Users/malik/development/kt_mobile_app_build/play-release/1.0.14-15/`.

| Artifact | SHA-256 |
| --- | --- |
| `Kainat-Travels-1.0.14-15.aab` | `9986fdce6b0f162b1b9a47724a3059af7ca19508a61fcf6b6f76e21a5eed7323` |
| `Kainat-Travels-1.0.14-15.apk` | `93e44824fe5a5d005af040bf712d70cd788b355716383f7689c49600e006308f` |

Evidence includes `source-manifest.json`, `verification-summary.json`,
`build.log`, `play-internal-1.0.14-active.jpg`, `play-release.json`,
`update-policy.json`, and `update-policy-enabled.jpg`.

## Files changed for this task

Relative to `kt_mobile_app/`:

- `lib/features/travel/presentation/lovable_search_flow.dart`
- `lib/features/travel/presentation/points_entry_dialog.dart` (new)
- `test/checkout_points_test.dart` (new)
- `pubspec.yaml`
- `docs/UPDATES.md`
- `docs/BUILD_REPORT_1.0.14_2026-11-08.md` (this report)
