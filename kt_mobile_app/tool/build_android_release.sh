#!/usr/bin/env bash
# Build locally; uploading and publishing are separate actions.
set -euo pipefail
cd "$(dirname "$0")/.."

if [[ ! -f android/key.properties ]]; then
  echo 'Missing android/key.properties. Follow docs/ANDROID_RELEASE.md.' >&2
  exit 1
fi
if [[ ! -f dart_defines.production.json ]]; then
  echo 'Missing dart_defines.production.json. Follow docs/ANDROID_RELEASE.md.' >&2
  exit 1
fi

flutter pub get
flutter analyze --no-pub
flutter test --no-pub
flutter build appbundle --release --no-pub --dart-define-from-file=dart_defines.production.json
flutter build apk --release --no-pub --dart-define-from-file=dart_defines.production.json

echo 'Built AAB: build/app/outputs/bundle/release/app-release.aab'
echo 'Built APK: build/app/outputs/flutter-apk/app-release.apk'
echo 'Complete the artifact and device checks in docs/PLAY_STORE_READINESS.md before upload.'
