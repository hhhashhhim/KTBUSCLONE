# Mobile WhatsApp verification

Signup, resend and password reset use the existing `/api/mobile/v1/auth` endpoints. WhatsApp is delivered by the Laravel backend; no provider keys are stored in Flutter.

The `mobile-backend` worktree documents environment configuration in `MOBILE_API.md`. Deploy its pending-registration migration and authentication changes with this app version. Registration returns a verification-only `data.token`, a summary of submitted details, `verification_delivery_sent` and nullable `verification_message`. No customer is linked or passenger account created before successful verification.

Successful `POST /auth/verify-otp` returns a verified `data.passenger` and a replacement `data.token`. The app saves the replacement token before refreshing passenger state or navigating home; failure leaves the pending token intact. Session restoration uses `GET /auth/session` and its `data.passenger` rather than `/profile`, because pending tokens cannot read profiles. Login and restored unverified sessions return to verification. Expired sessions require starting again; the backend defaults to a 24-hour registration deadline and 10-minute OTP deadline, while Sanctum access tokens may expire sooner. The backend offers `DELETE /auth/registration` for cancellation and hourly cleanup of expired pending rows; this does not delete ERP customers or tickets.

Backend WhatsApp mode uses the existing SAR Zone `otp` template. Local log mode is available only for development/testing. No real WhatsApp message is sent by the automated tests.
