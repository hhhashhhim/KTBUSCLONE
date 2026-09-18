# Upcoming schedules for external booking APIs

The external `v1/booking` and `v1/checkk` available-schedules and seat-preview endpoints can read `schedule_details_next_60_days`. All booking writes, portal pages, reports, ticket relationships, and historical records continue to use `schedule_details` and `schedules`.

## Deployment

On the API server, after pulling the `api` branch:

```sh
php artisan migrate --force
php artisan schedules:sync-upcoming
```

The migration requires MySQL/MariaDB `CREATE TABLE`, `INDEX`, and `TRIGGER` privileges. It copies the deployed detail-table schema, keeps original IDs, and adds a departure lookup index only to the new table. If trigger setup fails, the migration cleans up its own partial installation. It does not alter original table columns, indexes, or data.

The existing Laravel scheduler must run every minute (`php artisan schedule:run`). The new command refreshes the rolling window daily at 00:05 in the application timezone. No additional queue worker is required. Initial or large refreshes should use the command above to avoid web-server request timeouts.

On the portal, users with schedule access and Add/Edit Schedule permission can click **Sync Upcoming Schedules**. It syncs only their authenticated company, repairs missing/outdated copies, and shows the covered dates and completion time. Concurrent syncs for the same company are rejected with a clear message. The frontend change is Vue source; build locally using the existing deployment workflow. There is no production frontend build requirement.

## Consistency and window rules

- The window is today through today + 59 days, inclusive, based on the passenger's `departure_date`. A trip starting yesterday can therefore still be included for an overnight intermediate-city departure today.
- MySQL row triggers mirror inserts, updates (including query-builder/bulk changes, soft deletes and restores), and deletes in the source transaction. A rolled-back schedule edit also rolls back its copied change.
- The original `schedules` and drop records are read live. Hidden/deleted/dropped trips are excluded by the external endpoints, including preview. Tickets, quotas, fares, discounts, and terminal rules are also read live.
- A company switches to the copy only after a complete successful sync. Before migration/initial sync, outside the window, or outside the last completed window, reads fall back to the original table. An empty successfully synced date is valid, not a reason to fall back.
- Sync locks source rows in batches before upserting them, so concurrent edits cannot be overwritten by an older copy. The transaction retries deadlocks. Large syncs can briefly contend with schedule edits; run the initial fill off peak if possible.
- Expiry cleanup affects the copy only. It never deletes original schedules, details, or tickets.
- The triggers calculate the date using UTC and the application's fixed Asia/Karachi offset. If the application timezone or original detail-table schema changes, add a corresponding migration to update the copy and regenerate the triggers. Sync refuses a mismatched schema.

## Verification

Opt-in integration tests create and destroy a uniquely named disposable **local** MySQL database. They do not modify the application database:

```powershell
$env:RUN_SCHEDULE_PROJECTION_MYSQL_TESTS='1'
php vendor/bin/phpunit --filter UpcomingScheduleProjectionTest
Remove-Item Env:RUN_SCHEDULE_PROJECTION_MYSQL_TESTS
```

Coverage includes original-record preservation, complete field/ID copying, both external API response formats, live booked-seat changes, drop/hide filtering, transaction rollback, bulk edits, soft deletes/restores, date-boundary moves, overnight departures, company isolation, repair, daily rollover, and fallback before installation or outside coverage.

Implementation references: [MySQL row triggers](https://dev.mysql.com/doc/refman/8.0/en/trigger-syntax.html), [CREATE TABLE LIKE](https://dev.mysql.com/doc/refman/8.0/en/create-table-like.html), and [Laravel scheduler](https://laravel.com/docs/8.x/scheduling).
