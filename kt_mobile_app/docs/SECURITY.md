# Mobile security changes — 29 October 2026

## Flutter client

Production and staging API configurations require HTTPS. Release builds require
`APP_ENV=production` on Android and iOS; invalid environment names, embedded
credentials, URL queries/fragments, and incorrect API base paths are rejected
before an API request reads the passenger token.

The passenger API client sends requests only to the configured origin and
`/api/mobile/v1` path. It rejects encoded path traversal and other destinations
before reading stored credentials. Automatic API redirects are disabled so a
redirect cannot forward passwords or tokens elsewhere. The payment WebView keeps
its separate provider navigation flow and receives no passenger API token.

Files: `lib/core/config/app_environment.dart`, `lib/core/network/api_client.dart`,
and `test/api_security_test.dart`. The injected HTTP adapter is used by tests;
production requests continue to use Dio's native adapter and certificate checks.

## Dedicated mobile API

The separate `mobile-backend` work unit preserves shared customer records when
booking against a supplied CNIC, stores submitted passenger details with the
booking, applies daily/monthly and unpaid-reservation limits, removes abandoned
quotes, and supports optional dedicated payment/notification workers. It keeps
existing ERP routes, reports, payment helpers and dependency versions unchanged.
See `MOBILE_API.md` and `MOBILE_DEPLOYMENT.md` on that branch for the required quote
migration and optional worker configuration. No API response fields were invented
or added to the Flutter client for these controls; it uses the existing errors.

These changes do not repair historical customer edits, rotate external provider
credentials, remediate shared Laravel dependencies or verify production capacity.
A new app build is needed for the client guards. Backend controls require the
separate mobile API release and its reviewed migration; nothing is auto-deployed.
