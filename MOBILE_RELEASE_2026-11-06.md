# Mobile backend release follow-up — 6 November 2026

## Scope and current baseline

Repository: `hhhashhhim/KTBUSCLONE`, branch `mobile-backend`, local source
`/Users/malik/Desktop/Codes/KTBUSCLONE-mobile-backend`. The production mobile API
checkout is `/home/wwwkaina/kt-mobile-api`. A later read-only check on 6 November
confirmed live revision `b78d1cc1`, superseding the earlier `cc2e21d2` observation
in `MOBILE_DEPLOYMENT.md`. A deployed commit alone does not prove migrations,
cached settings, cron execution or successful end-to-end transactions.

The fresh migration check confirmed `link_mobile_booking_snapshots` applied in
batch 103, `harden_mobile_booking_and_token_schema` in batch 104 and
`preserve_mobile_first_login` in batch 105. The cron list still contained the
separate ERP `schedule:run` plus mobile `mobile:notifications` only. The new index
repair and scheduler flag in this follow-up are not part of `b78d1cc1`.
Observed local server changes were generated bootstrap caches and existing
untracked private backups/artifacts; preserve them and never stage their secrets.

Fresh selected cached configuration was also read without printing secrets:

| Setting | Observed value |
| --- | --- |
| Retention enabled | `false` |
| Notification / login / push retention days | `90` / `90` / `90` |
| Deletion-audit retention days | `365` |
| Maximum restore-copy age | `null` (not confirmed/configured) |
| Retention batch size | `500` |
| Mobile-only scheduler | `null` (new source not installed) |
| Forced update | `false` |

These observations confirm that the prepared retention configuration is present
but execution remains disabled. They do not approve the proposed periods or
establish processor/backup deletion. Leave that execution gate unchanged until
the policy and preview review are complete.

This follow-up contains two code fixes:

- A new forward migration ensures the abandoned-quote cleanup index exists.
  The two October migrations both guard `invoice_id`, so normal filename order
  could previously skip the cleanup index. The repair preserves existing quotes,
  invoice uniqueness and tokens, and safely handles already-recorded migrations.
- `MOBILE_SCHEDULER_ONLY=true` makes the dedicated mobile scheduler register only
  mobile commands. Its default is `false`, preserving existing deployments' ERP
  schedule. This allows the separate mobile host to run reconciliation and cleanup
  without also duplicating ERP reservation, legacy JazzCash and report jobs.

No retention periods are approved or changed by this follow-up. Retention remains
disabled by default. Financial/CNIC policy, provider deletion and the separate ERP
campaign writer require their own approved work. Do not deploy the local `api`
branch over the live ERP checkout; their recorded branches/revisions differ.

## Preflight and source installation

Run these separately in the mobile checkout and review each result:

```bash
cd /home/wwwkaina/kt-mobile-api
pwd
git remote -v
git branch --show-current
git status --short
git log -1 --oneline
/usr/local/bin/php --version
/usr/local/bin/php artisan migrate:status
```

Require the expected repository and branch. Stop for unexplained source changes,
another branch or a failed command. Preserve `.env`, `APP_KEY`, uploads and storage.
Do not print the full environment, credentials, passenger data or log contents.

Take the approved protected database/deployment backup and record its expiry.
Install the reviewed mobile-backend source through the approved deployment method;
this document does not authorize a commit, push, branch merge or production change.
For an artifact deployment, the existing `scripts/build_mobile_backend.py` must
run from committed, clean source; do not bypass its guard to package this pending
work. Use the locked production Composer dependencies with PHP 8.2 or later.

Before maintenance, let in-flight requests/jobs finish and pause this deployment's
standalone mobile notification cron. Preserve the separate ERP cron. Enter mobile
maintenance mode before source replacement and scoped schema changes:

```bash
/usr/local/bin/php artisan down
```

The following commands assume the reviewed follow-up source is now installed.
Run separately, stop on failure, and keep maintenance active until fixed:

```bash
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
/usr/local/bin/php artisan config:clear
/usr/local/bin/php artisan route:clear
/usr/local/bin/php artisan view:clear
```

Do not run `cache:clear`: shared cache contains OTP attempt budgets and booking
locks. Keep the existing lock-capable cache; cross-channel writers selling the same
seats must coordinate using the same lock store and namespace. Validate concurrency
before changing cache topology or adding replicas.

## Scoped migration sequence

All earlier mobile schema must already exist. If `migrate:status` shows missing
pre-29-October migrations, use their reviewed rollout sections first; do not run
all legacy ERP migrations. For the `cc2e21d2` or `b78d1cc1` upgrade path, apply only
these exact files in this order. Laravel skips files already recorded as run,
including the three migrations confirmed above:

```bash
/usr/local/bin/php artisan migrate --force --path=database/migrations/2026_10_29_000001_harden_mobile_booking_and_token_schema.php
/usr/local/bin/php artisan migrate --force --path=database/migrations/2026_10_29_000001_link_mobile_booking_snapshots.php
/usr/local/bin/php artisan migrate --force --path=database/migrations/2026_11_06_000001_preserve_mobile_first_login.php
/usr/local/bin/php artisan migrate --force --path=database/migrations/2026_11_06_000002_ensure_mobile_quote_cleanup_index.php
/usr/local/bin/php artisan migrate:status
```

The first two preserve existing data and ensure booking snapshots can be linked
to invoices. The first-login migration preserves/backfills existing login history.
The final repair ensures the cleanup index regardless of prior install order.
These migrations deliberately retain added metadata on rollback. Verify against a
staging copy on the actual MariaDB/MySQL version before production; SQLite tests
do not prove production DDL locking or concurrent booking behavior.

## Mobile-only scheduler activation

On the dedicated mobile API host, privately set `MOBILE_SCHEDULER_ONLY=true`.
Keep `MOBILE_RETENTION_ENABLED=false` until the approved policy and dry-run review
are complete. Leave background queue settings unchanged unless supervised durable
workers have been independently prepared. Then rebuild the config and inspect:

```bash
/usr/local/bin/php artisan config:cache
/usr/local/bin/php artisan queue:restart
/usr/local/bin/php artisan schedule:list
```

**Do not install or resume the mobile scheduler unless this check passes:** the list
must contain exactly these six mobile commands and no `reserved:cancel`,
`jazzcash:check-pending-payment` or `daily:report` command:

| Command | Frequency |
| --- | --- |
| `mobile:reconcile-payments` | Every minute |
| `mobile:notifications` | Every minute |
| `mobile:prune-pending-registrations` | Hourly |
| `mobile:prune-biometric-sessions` | Daily |
| `mobile:prune-booking-quotes` | Daily |
| `mobile:prune-personal-data --execute` | Daily, gated by retention enabled |

Seeing legacy commands means the source/configuration is wrong or the new flag
was not cached correctly; stop and correct that before changing cron. Merely
appearing in `schedule:list` does not mean the retention job is enabled.

Replace the existing one-minute **mobile notification-only** cron with one mobile
`schedule:run` entry. Do not retain both entries, and do not change/duplicate the
separate ERP cron. Use a dated scheduler log so host-managed expiry can rotate it:

```cron
* * * * * cd /home/wwwkaina/kt-mobile-api && /usr/local/bin/php artisan schedule:run >> storage/logs/mobile-scheduler-$(date +\%F).log 2>&1
```

The backslash before `%` is required in a crontab entry. Configure an operator-owned
expiry for these dated logs and existing notification logs under the approved
retention policy; dated names alone do not delete old files. Configure failure
monitoring so an exited or stalled scheduler is noticed. After schema/configuration
checks and cron replacement succeed:

```bash
/usr/local/bin/php artisan up
```

Observe at least two normal cron cycles and verify mobile reconciliation and
notification processing complete. Do not manually run a second scheduler alongside
cron to test it. Reconciliation can contact providers/change ticket status and
notifications can send actual alerts; their activation is a production operation.

## Release acceptance and retention handoff

1. Require successful HTTPS JSON from `/api/mobile/v1/app/config`, the intended live
   payment configuration and approved store/version settings. Keep forced updates
   off until an available store release matches the configured URLs and minimum.
2. Use controlled disposable accounts and provider-approved payment tests to verify
   signup/OTP, login/reset, schedules/seats/quote, booking, callback/app resume,
   failure/abandonment/expiry, duplicate settlement and ticket display. Check concurrent
   counter/web/mobile claims for one seat on the actual shared cache/database.
3. Coordinate the ERP campaign writer fix, then verify account deletion during
   notification/profile/device/booking requests on MySQL. Deleted mobile private
   data must not be recreated; financial records and other companies stay intact.
4. Run `php artisan mobile:prune-personal-data` without `--execute`. It is a read-only
   preview. Resolve migration gaps and review counts against approved retention
   periods before separately enabling cleanup. The 30-day hosting-backup period
   is owner-confirmed; do not set the maximum restore-copy age until replicas and
   exports are also inventoried. Verify deletion replay on an isolated restore.
5. Publish consistent approved public/app privacy and deletion wording, confirm
   processor/Firebase handling, then finalize Data safety using the released system's
   behavior. Do not treat these source changes as a completed live retention rollout.

## Validation of this follow-up

Fresh execution used a refreshed isolated source copy with the existing clean,
locked test dependencies, no source `.env` or runtime caches, SQLite in memory,
array cache/mail/session and disabled real OTP. Full backend suite: **395 cases:
393 passed and two optional MySQL cases skipped; 2,563 assertions, zero failures
or errors**. Counts were confirmed from the JUnit testcase entries. The four new
migration-order/scheduler cases passed with 26 assertions. No live database,
provider sends, migrations or scheduler execution were performed by these tests.
The skipped cases still need an explicitly configured disposable MySQL database.
