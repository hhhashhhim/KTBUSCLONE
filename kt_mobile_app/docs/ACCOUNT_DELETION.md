# Account deletion

Account → Delete account → review removal/retention details → enter current password → acknowledge → final confirmation.

The verified backend contract is `DELETE /api/mobile/v1/account` with a passenger bearer token and JSON `password` plus `confirmation: DELETE`. The backend verifies the password, deletes the mobile account, saved passengers and booking quotes, records a minimal audit event and revokes all sessions in one transaction. No deletion OTP is sent.

The app only clears its session and passenger caches and opens login after API success. Cancel sends nothing. Wrong password, offline and server failures remain on the screen for retry. Duplicate taps and back navigation are blocked while submitting. A lost response is not proof of deletion; a later 401 means the session is no longer valid.

Shared ERP customer information, tickets, payments and loyalty records remain. Deletion does not cancel trips, issue refunds or erase loyalty balances. Save ticket references before deleting. Fresh signup uses a new account ID and does not restore the old mobile booking list.

Deployment dependency: deploy the deletion controller/request/route and `2026_09_15_000010_create_mobile_account_deletions_table.php` migration from `mobile-backend` first. See that branch’s `MOBILE_API.md`. No production deployment or database migration has been performed. Business retention periods and any public web deletion page are separate follow-ups.

## Changed files

Repository: `hhhashhhim/KTBUSCLONE`.

`mobile-frontend` worktree (`KTBUSGIT`):

- `kt_mobile_app/lib/features/profile/delete_account_screen.dart`
- `kt_mobile_app/lib/features/profile/profile_screen.dart`
- `kt_mobile_app/lib/app/router.dart`
- `kt_mobile_app/lib/features/auth/data/auth_repository.dart`
- `kt_mobile_app/lib/features/auth/application/auth_controller.dart`
- `kt_mobile_app/test/account_deletion_test.dart`
- `kt_mobile_app/docs/ACCOUNT_DELETION.md`

`mobile-backend` worktree (`KTBUSCLONE-mobile-backend`):

- `app/Http/Controllers/Mobile/MobileAccountDeletionController.php`
- `app/Http/Requests/Mobile/DeletePassengerAccountRequest.php`
- `routes/api/mobile.php`
- `database/migrations/2026_09_15_000010_create_mobile_account_deletions_table.php`
- `tests/Feature/MobileAccountDeletionTest.php`
- `MOBILE_API.md` (account-deletion section only)

Validation: Flutter analysis passed; all 32 Flutter tests passed (including five deletion tests); five backend deletion tests passed with 48 assertions using SQLite in memory. Existing uncommitted OTP and unrelated files were preserved. Nothing was committed or pushed.
