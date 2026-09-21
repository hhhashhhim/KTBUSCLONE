# ERP ticket cancellation permissions

The staff endpoints `POST /api/web/v1/booking/canceling` and
`POST /api/web/v1/booking/canceling/all` use the ticket's current stored status:

| Ticket status | Required permission |
| --- | --- |
| `advance booking` (reserved/unpaid) | `reserved-cancel` |
| `booked` (confirmed/issued) | `cancel-ticket` |

These rules apply to mobile-created tickets and other ERP tickets. Other statuses
are rejected. Having either permission does not grant the other permission.
Existing staff authentication and company scoping remain in place.

The backend locks the selected tickets within a transaction before checking their
statuses. Bulk requests require a nonempty array of distinct positive integer IDs
in `cancelAllSeat`. Every requested ticket must exist, be active, belong to the
staff member's company, and have a status that their permissions allow cancelling.
A mixed reserved/issued selection requires both permissions. All checks complete
before any ticket, loyalty balance, baggage record, or cancellation log changes.

Responses:

- `200`: existing `{ "tickets": [...] }` envelope; the array contains only IDs of
  tickets that were issued before cancellation. Reserved-only selections return
  an empty array.
- `403`: missing or mismatched permission, or an unsupported ticket status;
  existing staff `{ "Error": [...] }` envelope.
- `404`: any requested ticket is missing, already deleted, or outside the staff
  company; `{ "Error": [...] }` envelope. Bulk requests cancel nothing.
- `422`: malformed bulk selection, using Laravel's validation response, or the
  existing transaction-failure response. Bulk failures leave no partial changes.

Successful cancellations retain the existing soft deletion, cancellation reason,
refund metadata, staff audit, and loyalty handling. They do not initiate a gateway
refund. Mobile routes, request/response contracts, payment verification, and
reservation expiry are unchanged. No migration, configuration change, or Flutter
release is required for this permission fix; deploy the updated booking controller
to enforce the rule in ERP.

Verification uses disposable SQLite databases and faked gateway requests:

```sh
php vendor/bin/phpunit --do-not-cache-result --filter 'ErpCancellationPermissionTest|Mobile'
```

The permission tests cover both endpoints, denied requests with no side effects,
authorized cancellation/audit behavior, mixed bulk selections in both status
orders, invalid selections, and company/missing/deleted-ticket boundaries. Mobile
regression tests also cover booking creation, payment confirmation, expiry, ERP
cancellation, and late payment receipts. SQLite tests do not exercise MySQL row
lock scheduling under concurrent requests.
