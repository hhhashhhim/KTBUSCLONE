# Android 1.0.10 (11) — 7 November 2026

Repository: `hhhashhhim/KTBUSCLONE`. Flutter work remains on `mobile-frontend`
under `kt_mobile_app/`; backend deployment uses the separate `mobile-backend`
worktree. The user explicitly authorized backend deployment/migration and a Google
Play internal testing release. No Git commit, push, merge or branch switch was made.

## Included behavior

- Eligible discounted quota/reused seats use an emerald outline and `%` marker,
  with a Special fare legend and accessible saving/price labels. The marker
  remains visible after selection. Checkout separates Special seat discount from
  ordinary savings while retaining the backend total.
- The backend now supplies per-seat discount metadata, enforces newer midway
  quotas and directional route exemptions, rechecks stale quotes and payment
  confirmation, and records quota savings in the shared ERP audit column.
- Previously pending frontend fare breakdown and city-cache fixes were preserved
  and included in this build, along with their tests.

## Backend deployment

The eight quota runtime/migration files were deployed to
`/home/wwwkaina/kt-mobile-api`, branch `mobile-backend`, base revision
`8abbda840b4e4b171a0c8eeccb20b25e6b9fe584`. Before/after target checksums were
verified. Only the new compatibility migration was run; existing quota schema
was already installed, so the migration preserved it and recorded completion.

Private rollback backup:
`/home/wwwkaina/private-release-backups/quota-20261107-v11-155700/`.
The app returned online, exact cron contents were restored and `.env` was
unchanged. A live read-only check returned one future schedule and 40 seats with
the new contract. HTTPS app/config returned 200/success and unauthenticated
cities remained protected with 401. No real booking or payment was created.

**Existing configuration still controls eligibility:** mobile terminal 24,
company 1, currently has zero active positive terminal-quota discount assignments.
The feature is deployed, but special markers appear only where the ERP has an
eligible discount assignment. No discount percentage was enabled during release.

## Verification actually run

| Check | Result |
| --- | --- |
| Backend scheduling/API/payment regression suites | 229 tests, 1,747 assertions passed |
| Server PHP 8.2.35 syntax checks | All eight deployment files passed |
| `flutter analyze --no-pub` | No issues |
| Full `flutter test --no-pub` | 251 tests passed |
| Production release AAB/APK builds | Passed |
| APK `apksigner verify --verbose --print-certs` | Passed; expected upload certificate |
| AAB `jarsigner -verify` and certificate check | Passed; expected upload certificate |
| Official bundletool 1.18.3 validation | Passed |
| Manifest | Correct package/version; min SDK 24, target 36; no debug, backup or cleartext |
| AAB config | `PAGE_ALIGNMENT_16K` |
| All eight 64-bit native libraries | PT_LOAD alignment and offset/address congruence passed |
| APK `zipalign -c -P 16 -v 4` | Passed |
| Both worktree `git diff --check` | Passed |

Backend tests used unchanged locked dependencies installed in an isolated
temporary vendor directory because the repository's existing vendor files were
incompatible. Neither dependency manifests nor the deployed vendor tree changed.
The existing SDK XML tooling warning and self-signed/no-timestamp JAR warnings
are retained in logs. No new license agreement or browser permission was accepted.
No Android device was connected; this release does not claim a physical-device,
Play-install, real-payment or 16 KB runtime acceptance test.

## Artifacts

Directory: `/Users/malik/development/kt_mobile_app_build/play-release/1.0.10-11/`
(the existing ignored `kt_mobile_app/build` symlink points to this build root).

| Artifact | SHA-256 |
| --- | --- |
| `Kainat-Travels-1.0.10-11.aab` | `670ebe6d3da043560aa2979cca98f36e7a6068bd999e214dc987bdc483042ba7` |
| `Kainat-Travels-1.0.10-11.apk` | `aa9a00c63d606b81cd33d2c5d9f0f0eda72b4d90a38303de120e16095164c919` |

The directory contains build/verification logs, `verification-summary.json`,
`SHA256SUMS.txt`, and deployment evidence. Existing production API/Firebase
settings and upload key were reused. Private signing files were not copied.

## Google Play status

**Active — available to internal testers**, released 7 November 2026 at 21:02
Asia/Karachi. Play shows latest release **1.0.10**, one version code **11**, and
**Not reviewed**. The preview reported Ready to release, with no blocking errors
and no loss of supported devices. ReTrace mapping and native debug symbols were
attached. The existing temporary unreviewed app-name notice remains.

- Package: `com.kainattravels.kt_mobile_app`.
- [Release details](https://play.google.com/console/u/2/developers/5051456537957291644/app/4973980219327601890/tracks/4701697187779665820/releases/4/details).
- [Internal tester opt-in](https://play.google.com/apps/internaltest/4701697187779665820).
- Evidence: `play-internal-1.0.10-active.jpg` in the artifact directory.

The release did not change public production, tester access or store declarations.
Play notes updates usually appear within an hour, but can occasionally take longer.

## Files changed

Feature files: `lib/features/travel/domain/travel_models.dart`,
`lib/features/travel/presentation/{special_fare_widgets,lovable_search_flow,search_flow,checkout_widgets}.dart`,
`test/{travel_models,seat_layout,fare_checkout}_test.dart`, and `docs/FARES.md`.
This release also changes `pubspec.yaml` to `1.0.10+11` and adds this report.
Previously pending `travel_providers.dart`, `city_catalog_test.dart`, fare changes
and the 1.0.9 report remain intact. Backend runtime/test/API-documentation changes
and the deployment record remain exclusively on `mobile-backend`.
