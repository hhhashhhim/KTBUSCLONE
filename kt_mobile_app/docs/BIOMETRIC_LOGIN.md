# Biometric quick login

Enable it in **Account → Face ID / Touch ID / Face / fingerprint** after verifying
the passenger's mobile number. Enter the account password and complete the phone's
biometric prompt. The sign-in screen then offers a quick-login button. Cancel,
lockout, unsupported hardware, or missing enrollment leaves password login available.
Any authorized biometric enrolled on that phone can unlock this account; this is
device authentication, not CNIC identity verification.

On Android, the Account card explains why enrollment is unavailable: missing
setup, unsupported sensors, temporary unavailability, or required security updates.
When setup is possible, **Open biometric settings** opens Android enrollment for
the strong biometrics this app accepts. Returning to Account refreshes the status;
**Check again** also retries it. An enabled device lock or camera face unlock alone
does not guarantee support for secure app sign-in. Devices with only unsupported
face unlock must use password sign-in.

The ordinary access session remains 10 hours. Quick login can renew it for 30 days
from enrollment by default; the backend can configure this duration. It needs an
internet connection and cannot bypass the ERP's blocked accounts, blacklist,
password-reset requirement, login switch, or revoked sessions.

With quick login enabled:

- Access tokens remain only in process memory, never in ordinary persistent storage.
- Cold launch goes to sign-in. Returning after at least one minute in the background
  clears the in-memory session and requires sign-in again. Brief app switches,
  including normal payment checkout, do not immediately lock the app.
- A new access token and renewal credential are issued after each successful
  quick login. The 30-day enrollment expiry does not slide.
- Explicit sign-out or password login removes this phone's quick-login credential.
- A password login on another device, ERP revocation, password reset, or account
  deletion also invalidates renewal at the server.
- Changes to enrolled biometrics invalidate the protected credential. Sign in with
  the password and enable quick login again.

## Platform protection

Android uses a native `BiometricPrompt` with `BIOMETRIC_STRONG` and a per-use
Android Keystore private key. The renewal secret is RSA-OAEP encrypted. Only a
successful cryptographic biometric operation can decrypt it; no plain success
boolean unlocks ordinary storage. Android 9/API 28 or newer with enrolled Class 3
biometrics is required. Weak camera-only face unlock is unsupported. Android backup
is disabled for this app. The public key encrypts rotated secrets without a second
biometric prompt.

iOS uses a Keychain item with `biometryCurrentSet` and
`kSecAttrAccessibleWhenPasscodeSetThisDeviceOnly`. It does not sync to iCloud or
transfer to another device. A fresh `LAContext` is used for each unlock. Its context
can authorize only the immediate rotation write, then is invalidated. Reinstall
clears stale Keychain quick-login data. `NSFaceIDUsageDescription` supplies the
system permission text.

Passwords and biometric images/templates are never saved for quick login.
The API receives only the rotating credential. API debug logging excludes request
and response bodies and Authorization headers.

## API dependency and rollout

Implemented separately in `hhhashhhim/KTBUSCLONE` on `mobile-backend`:

- `POST /api/mobile/v1/auth/biometric` — valid passenger Bearer token plus password.
- `POST /api/mobile/v1/auth/biometric/renew` — single-use renewal secret.
- `DELETE /api/mobile/v1/auth/biometric` — disable renewal for the current session.
- Migration `2026_10_20_000007_create_mobile_biometric_sessions.php`.

See that checkout's `MOBILE_API.md` and `MOBILE_DEPLOYMENT.md` for exact contracts,
expiry configuration, replay handling and daily cleanup.

Deploy the API/migration and release a new Android/iOS build before passengers can
use this feature. Existing builds and password sessions continue to work. This
change does not publish an app or deploy the backend.

## Verification

Automated tests cover enrollment, secure-memory token behavior, cancellation,
unavailable devices, credential rotation, invalidated enrollment, offline errors,
server revocation, password fallback, sign-out, settings return, unsupported-device
messages and availability retries. API tests cover expiry, replay,
tenant isolation, verification, restrictions, blacklist, global controls, logout,
password reset, alternate-device login and cleanup.

Before a production release, complete real-device checks for:

1. Face ID, Touch ID and supported Android fingerprint/face hardware.
2. Cancel, failed matches, lockout, missing biometrics and denied Face ID permission.
3. Cold launch, one-minute background lock and expiry renewal.
4. Biometric enrollment changes, phone passcode removal and reinstall.
5. Interrupted network/storage during rotation, then password recovery.
6. ERP block/revoke and password reset while a device holds an expired access token.

Simulator/build checks cannot prove physical biometric hardware behavior.
