# Android Git handoff — 6 November 2026

Repository: `hhhashhhim/KTBUSCLONE`, branch `mobile-frontend`.
Version: `1.0.7+8`; package: `com.kainattravels.kt_mobile_app`.

Rebuilt the pending frontend source with `bash tool/build_android_release.sh`
using the existing ignored production configuration and upload key. No version
change, Play upload, server deployment or production migration was performed.

## Verification actually run

- `flutter pub get`: completed without changing the dependency lock.
- `flutter analyze --no-pub`: no issues.
- `flutter test --no-pub`: all 225 tests passed.
- Signed release AAB and APK builds: passed.
- APK `apksigner verify --verbose --print-certs`: passed, v2 signature.
- APK `zipalign -c -P 16 4`: passed.
- AAB `jarsigner -verify`: `jar verified`; existing self-signed certificate,
  missing timestamp and ZIP stream-order warnings remain.
- `bash -n tool/build_android_release.sh` and `git diff --check`: passed.
- Signing credentials, production defines and build outputs remain Git-ignored.

## Outputs

The build folder resolves to `/Users/malik/development/kt_mobile_app_build/`.
The rebuilt hashes match the previously prepared internal-release artifacts.

| File under build folder | SHA-256 |
| --- | --- |
| `app/outputs/bundle/release/app-release.aab` | `274dbeeaffab7a69172e591255770cc312cdeee9179002ef2cf1f0386d6528b2` |
| `app/outputs/flutter-apk/app-release.apk` | `5fe4edd71b1b5674ec347a70d1917896fb59bd22421bf200dc6f25da3a8ff272` |

Source handoff includes API-origin guards, passenger gender validation and tests,
the repeatable build command, and existing release/security documentation.
Laravel/web duplicate files outside `kt_mobile_app/` are excluded.

## Pending acceptance

Backend rollout and required migrations remain a separate `mobile-backend` work
unit. Physical-device, real-provider, reviewer-access and 16 KB runtime acceptance
remain pending as recorded in `PLAY_STORE_READINESS.md`. Play declarations and
public production release are not completed by this build. iOS signing and
Firebase/APNs setup remain separate requirements; no iOS release was built here.
