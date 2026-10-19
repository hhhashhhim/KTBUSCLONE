# Call Center module

Imported from KTBUSCLONE api commit 6bad036b44860dc91d44761b7a510289ae958b53,
with companion frontend mix commit 023a1bf62d653400b174d796ccb21bcf63f0baa6.
Only module files and required navigation/permission integration were transferred.

## Deployment

Pull this project's `api` and `mix` branches into their respective applications.
Back up the API database, then run in the API directory:

```sh
php artisan call-center:install --force
php artisan route:clear
```

The installer runs only the 13 Call Center migrations, not unrelated pending
migrations. It is safe to rerun after successful installation. The passenger
lookup index is added to `tickets`; schedule deployment for a quiet period on
large databases because building that index may take time.

Existing non-Call-Center permissions are preserved. Company `admin` roles always
receive all Call Center actions, including existing admins after installation.
The module becomes available to companies but is disabled for other roles until configured in
**Users > Roles > Permissions > Call Center**. Enable the module and select
the required actions. Call Center does not require Ticketing access.

For agents, enable the needed call, passenger-assistance and complaint actions.
For supervisors, additionally enable **View team records**, the required
reports, review permission and assignment actions. Grant **Access all company
terminals** only when needed; assigned route restrictions still apply.

The module records calls, complaints, handovers, callbacks, customer agreements,
shift/daily reports and audit history. Refund/reschedule agreements are call
records only: they do not execute refunds, change seats or modify bookings.

No frontend build was run during integration. Deploy the tracked frontend assets
with the source; use your normal local build workflow for later changes.

## Validation

```sh
php vendor/bin/phpunit --filter CallCenter
```

Tests use an isolated in-memory database. The frontend repository also includes
`node --test tests/frontend/call-center.test.mjs`.
