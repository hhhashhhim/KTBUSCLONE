# Android 1.0.12 (13) — server-based departure calendar

Repository: `hhhashhhim/KTBUSCLONE`. Frontend changes are in `mobile-frontend`
under `kt_mobile_app/`; backend changes are in the separate `mobile-backend`
worktree. Existing pending work was preserved. No Git commit, push, branch
switch, merge or history rewrite was performed.

## Behavior

The departure calendar now uses `server_date` from the existing public
`GET /api/mobile/v1/app/config` response. Both search layouts use that day for
the default date, highlighted today, first selectable date and existing 90-day
calendar range. They do not use the phone clock or convert the server day to
the phone timezone. The API remains authoritative for advance-booking limits,
schedule eligibility and booking validation.

The app refreshes the calendar on entry, resume, every foreground minute, before
opening the picker and before search submission. Future selections survive
refreshes; past selections move forward to the current server day. Missing or
invalid server dates and offline refreshes show retry UI instead of guessing a
phone date. A search is abandoned if the cities change during its date refresh.

The backend adds `server_time`, `server_date` and `timezone` from one consistent
server-clock snapshot, with `Cache-Control: no-store, private`. No new endpoint,
authentication change, database migration or booking rule was needed.

## Server time verification and deployment

At **21:38:23 Asia/Karachi on 7 November 2026**, the hosting server was compared
directly with two independent NTP sources:

| Source | Reference minus server | Network round trip |
| --- | --- | --- |
| `time.google.com` | +0.000426 seconds | 0.002929 seconds |
| `time.cloudflare.com` | +0.001048 seconds | 0.001185 seconds |

The measured offsets were about one millisecond or less. No clock adjustment
was needed or made. `timedatectl` and `chronyc` are unavailable to the hosting
account, so this verifies current accuracy rather than its ongoing time-service
configuration. Evidence: `server-clock-verified.jpg` and
`server-clock-check.json` in the release artifact directory; the original
machine-generated JSON is in private remote staging.

Only `app/Http/Controllers/Mobile/MobileAppController.php` was deployed, after
branch, base revision, original SHA-256 and PHP syntax checks. Base revision
remains `8abbda840b4e4b171a0c8eeccb20b25e6b9fe584`, with authorized deployed
overlays. The replacement was atomic. Environment and cron contents stayed
unchanged, and earlier quota/departure/authentication changes were preserved.

- Backup: `/home/wwwkaina/private-release-backups/calendar-20261107-v13-163851/`.
- Staging/log: `/home/wwwkaina/private-release-staging/calendar-20261107-v13/`.
- Deployment archive SHA-256:
  `c51d79d5aceab7d9643cd638d4ab9842bdf07beddf207e0be969417ab70e17b1`.
- Public HTTP verification returned 200 with consistent server time/date,
  `Asia/Karachi`, `+05:00`, and the no-store header.

## Validation

- Backend: **237 tests, 1,793 assertions passed** across contract, scheduling
  and payment suites. New contract coverage checks Pakistan midnight rollover
  against a UTC clock, cache headers and client dates not overriding server dates.
- Flutter: **260 tests passed**; analyzer reported no issues.
- Seven new Flutter tests cover strict date parsing, midnight selections, both
  search layouts' picker properties, loading/retry/offline behavior and fresh
  dates before search submission. The existing city refresh test was updated
  with a server-calendar fixture.
- Signed AAB and APK builds passed. Signature and expected upload certificate,
  bundletool validation, package/version, min SDK 24 and target SDK 36 passed.
- Debug, backups and cleartext remain disabled. 16 KB bundle alignment, all
  eight 64-bit native ELF checks and APK zip alignment passed.
- No physical-device install, real payment or 16 KB runtime acceptance is claimed.

## Artifacts and publication

Directory: `/Users/malik/development/kt_mobile_app_build/play-release/1.0.12-13/`.

| Artifact | SHA-256 |
| --- | --- |
| `Kainat-Travels-1.0.12-13.aab` | `1992394871806d743e090f681557f3ac1ff2b5adf595765727d50584b40d633c` |
| `Kainat-Travels-1.0.12-13.apk` | `418588e21c8791acbef5037d635d2c533e50c8d9569238b59a1490a8d77fd0f4` |

Google Play internal release 6 is **Active**, latest version **1.0.12 (13)**,
and **Available to internal testers**, released **7 November 2026 at 21:44
Asia/Karachi**. Play confirms the mapping file and native debug symbols are
attached, with no supported devices lost. Existing testers and public
production distribution are unchanged.

- [Release details](https://play.google.com/console/u/2/developers/5051456537957291644/app/4973980219327601890/tracks/4701697187779665820/releases/6/details)
- [Tester download](https://play.google.com/apps/internaltest/4701697187779665820)
- Evidence: `play-internal-1.0.12-active.jpg` and `play-release.json`.

After Play availability was verified, company 1's existing configuration was
updated transactionally to `latest_version: 1.0.12`,
`minimum_supported_version: 1.0.12`, `force_update: true`, preserving its Android
store URL and other fields. Private rollback snapshot:
`/home/wwwkaina/private-release-backups/calendar-update-policy-20261107-v13.json`.
The public API returned HTTP 200 with these values and consistent server date/time
at **21:45:24 Asia/Karachi**. Evidence: `update-policy.json` and
`update-policy-enabled.jpg`.

No backend/frontend release dependency remains pending. Testers must still
accept Google Play's installation prompt, and availability on an individual
device can lag publication while Play propagates the release.

## Files changed for this follow-up

Frontend:

- `lib/features/startup/domain/app_config.dart`
- `lib/features/travel/application/travel_calendar.dart` (new)
- `lib/features/travel/presentation/lovable_search_flow.dart`
- `lib/features/travel/presentation/search_flow.dart`
- `lib/app/app.dart`
- `test/travel_calendar_test.dart` (new), `test/city_catalog_test.dart`
- `pubspec.yaml`
- `docs/SCHEDULE_RESULTS.md`, `docs/UPDATES.md`, this report

Backend:

- `app/Http/Controllers/Mobile/MobileAppController.php`
- `tests/Feature/MobileApiContractTest.php`
- `MOBILE_API.md`, `MOBILE_DEPLOYMENT.md`
