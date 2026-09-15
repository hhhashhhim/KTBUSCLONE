# Mobile WhatsApp verification

Signup, resend and password reset use the existing `/api/mobile/v1/auth` endpoints. WhatsApp is delivered by the Laravel backend; no provider keys are stored in Flutter.

The `mobile-backend` worktree documents environment configuration in `MOBILE_API.md`. It must be deployed with these Flutter changes: registration returns `verification_delivery_sent` and nullable `verification_message`, and successful `POST /auth/verify-otp` returns `data.passenger` with `mobile_verified: true`. The app displays registration/resend errors, preserves the signed-in account for retry, and refreshes passenger state from the verification response. Login and restored unverified sessions return to verification.

Backend WhatsApp mode uses the existing SAR Zone `otp` template. Local log mode is available only for development/testing. No real WhatsApp message is sent by the automated tests.
