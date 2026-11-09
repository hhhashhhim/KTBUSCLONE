# Android 1.0.11 (12) — departed schedules and mandatory updates

Repository: `hhhashhhim/KTBUSCLONE`. Flutter changes stay on `mobile-frontend`
under `kt_mobile_app/`; backend changes and deployment use the separate
`mobile-backend` worktree. Existing pending work was preserved. Nothing was
committed, pushed, merged or switched between branches.

## Behavior

The backend previously accepted any departure date from today onward, including
times already passed, and its booking-opening-window switch could return true
without a departure cutoff. The shared mobile schedule policy now requires the
selected boarding segment to depart strictly after the current Pakistan time.
It applies to city catalogs, schedule search, seat lookup, quotes and booking
revalidation, including the exact departure second. A bus that has left its
starting city remains eligible from a later, upcoming boarding stop. Overnight
quota exemptions and reuse behavior remain intact.

The app disposes search caches after leaving the results page, refetches on
resume and once a minute while foregrounded, and hides stale selectable rows
while refreshing. Eligibility remains server-authoritative. Results can age
between refreshes, so seat/quote/booking requests always recheck the cutoff.

The user explicitly chose **Require the latest version before continuing**.
The existing update gate already checks at startup, resume and once a minute;
Android immediately attempts Play's update flow, with store-link fallback.
Google Play still asks the user to accept installation. Reopening alone does
not silently replace the installed binary. The previous live policy advertised
1.0.0, force-update false, and a blank store URL.

## Backend deployment and validation

Deployed only `MobileSchedulePolicy.php` and `MobileTravelService.php` to
`/home/wwwkaina/kt-mobile-api` on `mobile-backend`; their original SHA-256 values
matched the previously verified quota deployment. Base Git revision remains
`8abbda840b4e4b171a0c8eeccb20b25e6b9fe584`, with the authorized deployed overlays.
No new migration was needed.

- Live visible departed segments today: **122 before, 0 after**.
- Post-deploy read-only future search returned one schedule and 45 seats with
  the existing quota contract. No production booking/payment was created.
- Private rollback backup:
  `/home/wwwkaina/private-release-backups/departure-20261107-v12-161731/`.
- Private staging: `/home/wwwkaina/private-release-staging/departure-20261107-v12/`.
- Deployed archive SHA-256:
  `16ad89a26f511c23431dca65b8c971f4f15cb58ed7fcb72329f0ae0cbddef56b`.
- Both target checksums and PHP syntax passed. Exact scheduler contents were
  restored and `.env` remained unchanged; the ERP scheduler was preserved.
- Backend scheduling/API/payment regression tests: **236 tests, 1,785 assertions**.
  Coverage adds cutoff boundaries, booking switch bypass, stale quotes and
  Pakistan timezone; overnight midway tests remain passing.

## Android validation

- Full `flutter test --no-pub`: **253 tests passed**.
- `flutter analyze --no-pub`: no issues.
- Signed production AAB/APK builds passed, with existing API/Firebase/signing settings.
- AAB signature, APK signature and expected upload certificate passed.
- Official bundletool validation, package/version metadata and min SDK 24 /
  target SDK 36 passed; debug, backups and cleartext remain disabled.
- `PAGE_ALIGNMENT_16K`, all eight 64-bit native-library ELF checks and APK
  `zipalign -c -P 16 -v 4` passed.
- No physical-device, Play installation, real-payment or 16 KB runtime acceptance
  test is claimed. Existing SDK/JAR tooling warnings remain in artifact logs.

## Artifacts and publication

Directory: `/Users/malik/development/kt_mobile_app_build/play-release/1.0.11-12/`.

| File | SHA-256 |
| --- | --- |
| `Kainat-Travels-1.0.11-12.aab` | `343e29f227c9dbc3155eb96caba896d72896676489c7e01109bb361b6370205e` |
| `Kainat-Travels-1.0.11-12.apk` | `7942a6392a345cc1b77a44ac132608da22ab488c496a1067b611d185efaac6f3` |

Google Play internal testing is **Active**, with latest release **1.0.11 (12)**
shown as **Available to internal testers**, released on **7 November 2026 at
21:24 Asia/Karachi**. The release includes the ReTrace mapping file and native
debug symbols. No public production release, tester-access or store-declaration
change was included.

- [Release details](https://play.google.com/console/u/2/developers/5051456537957291644/app/4973980219327601890/tracks/4701697187779665820/releases/5/details)
- [Internal tester link](https://play.google.com/apps/internaltest/4701697187779665820)
- Screenshot evidence: `play-internal-1.0.11-active.jpg` in the artifact directory.

After verifying Play availability, company 1's existing update configuration was
changed to `latest_version: 1.0.11`, `minimum_supported_version: 1.0.11`,
`force_update: true`, and
`android_store_url: https://play.google.com/store/apps/details?id=com.kainattravels.kt_mobile_app`.
The public app/config endpoint returned HTTP 200 with those exact values at
21:25 Asia/Karachi. Other configuration fields were preserved. The previous row
was saved privately to
`/home/wwwkaina/private-release-backups/departure-update-policy-20261107-v12.json`.
Public verification and publication records are saved as `update-policy.json`
and `play-release.json`; policy screenshot evidence is `update-policy-enabled.jpg`.
No release/deployment work remains pending. Google Play propagation and the
tester's installation consent still determine when a particular phone updates.

## Changed files for this follow-up

Frontend: `lib/app/app.dart`,
`lib/features/travel/application/travel_providers.dart`,
`lib/features/travel/presentation/{lovable_search_flow,search_flow}.dart`,
`test/{account_session_navigation,city_catalog}_test.dart`, `pubspec.yaml`,
`docs/{SCHEDULE_RESULTS,UPDATES}.md` and this report.

Backend: `app/Services/Mobile/{MobileSchedulePolicy,MobileTravelService}.php`,
`tests/Feature/MobileSchedulingRulesTest.php`, `MOBILE_API.md`,
`MOBILE_DEPLOYMENT.md`. The production update-policy change uses the existing
company configuration row; no new API fields or schema were introduced.
