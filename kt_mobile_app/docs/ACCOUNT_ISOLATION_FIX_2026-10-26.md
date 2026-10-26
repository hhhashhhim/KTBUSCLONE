# Account isolation fix — 26 October 2026

Repository: `hhhashhhim/KTBUSCLONE`, local checkout `/Users/malik/Desktop/Codes/KTBUSGIT`, branch `mobile-frontend`. Scope: Flutter frontend inside `kt_mobile_app/`. This fixes QA 1 from the [readiness review](QA_REPORT_2026-10-26.md). No backend changes or API contract changes are required for this local cache defect.

## Result

Saved passengers, wallet balance and bookings now belong to a specific passenger login session. A fresh login creates a fresh cache scope even when the same passenger returns. Signed-out and obsolete cache instances return empty private data. The booking draft resets when the session changes, including its passenger details, seats and quote.

The app removes private routes and their local forms when an authenticated session ends or changes. Delayed session restoration, profile saves, passenger-form continuation, quotes and booking results are checked before they can update the current session or navigate. The affected cache providers also ignore results from obsolete requests. This does not cancel a server operation that has already been submitted; the fix prevents its response from populating another session's UI.

Updating the active passenger's profile preserves the session and its current draft. Existing logout storage-failure behavior is retained: a failed local logout reports the error instead of falsely claiming success.

## Checks actually run after the fix

| Check | Result |
| --- | --- |
| `flutter analyze --no-pub` | Passed, no issues |
| `flutter test --no-pub test/account_isolation_test.dart test/account_session_navigation_test.dart --reporter expanded` | **24 passed, 0 failed** |
| `flutter test --no-pub --reporter expanded` | **194 passed, 2 failed**, 196 total |
| `git diff --check` | Passed |

The full-suite failures are the same two reproduced before this fix: the 42-seat map has 13 logical pixels of scrolling at 375 × 812 with system insets, and large text overflows the bottom navigation. No previously passing test failed in this run. The initial suite had 170 passing tests and those same two failures; the 24 new tests are now part of the default suite.

Regression coverage includes the four original defects; signed-out access without repository calls; logout, expiry and biometric lock; old data during the next account's loading and failure states; late successful and failed cache responses; same-account re-login; same-account profile edits; delayed session restoration and profile saves; delayed quotes; and removal of private routes/forms after expiry and direct account switching.

Evidence: [dedicated isolation tests](../qa/evidence/2026-10-26-account-isolation-fix/isolation-tests.log), [full Flutter suite](../qa/evidence/2026-10-26-account-isolation-fix/flutter-tests.log), [static analysis](../qa/evidence/2026-10-26-account-isolation-fix/analyze.log). Tests use synthetic accounts and fakes. No live passenger data, booking, payment or account mutation was used.

Native Android/iOS builds and simulator smoke checks in the initial review predate this fix. They were not repeated, and those existing binaries do not contain these changes. The app still needs the other corrections and release acceptance described in the readiness review.

## Files changed for this fix

Paths below are relative to `kt_mobile_app/`. Some files already contained unrelated user changes; this list describes only the account isolation additions.

| Files | Purpose |
| --- | --- |
| `lib/features/auth/application/auth_controller.dart` | Session identity and generation; stale authentication result guards |
| `lib/features/travel/application/travel_providers.dart` | Session-scoped private providers and draft reset |
| `lib/app/app.dart` | Remove private navigation state when a session ends or changes |
| `lib/features/home/home_screen.dart`, `lib/features/tickets/tickets_screen.dart` | Read and refresh bookings for the current session |
| `lib/features/profile/saved_passengers_screen.dart`, `lib/features/profile/wallet_screen.dart` | Read and refresh the current session's private data |
| `lib/features/profile/profile_screen.dart` | Ignore a profile save response for an obsolete session |
| `lib/features/travel/presentation/lovable_search_flow.dart` | Use session-scoped caches; guard passenger, quote and booking continuations |
| `test/account_isolation_test.dart` | 23 cache, draft, lifecycle and delayed-response regressions |
| `test/account_session_navigation_test.dart` | One app/router test covering expiry and account switching |
| `test/fare_checkout_test.dart`, `test/home_layout_test.dart`, `test/payment_test.dart`, `test/ticket_payment_actions_test.dart`, `test/trips_loading_test.dart` | Adapt existing fixtures to session-scoped providers |
| `qa/account_isolation_test.dart` | Keep the original QA command available through the default regression suite |
| `analysis_options.yaml` | Exclude generated `build/**` snapshots from source analysis; real `lib/`, `test/` and `qa/` sources remain analyzed |
| `docs/QA_REPORT_2026-10-26.md`, this document | Record the correction while preserving the original QA findings |
| `qa/evidence/2026-10-26-account-isolation-fix/` | Save final analyzer and test results separately from the original failure evidence |

Nothing was committed, pushed, merged, deployed or submitted to an app store. No branches were switched. Existing work outside this fix was preserved.
