<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Online Terminal Cancel Seat API Documentation</title>
    <style>
        :root {
            --bg: #f4f7fb;
            --panel: #ffffff;
            --ink: #132238;
            --muted: #5b6573;
            --line: #d9e1ec;
            --accent: #1f6feb;
            --good: #0f7b3f;
            --warn: #a15c00;
            --code: #0f172a;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: var(--bg);
            color: var(--ink);
            line-height: 1.55;
        }
        .page {
            max-width: 1080px;
            margin: 0 auto;
            padding: 32px 20px 48px;
        }
        .header {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 24px 28px;
            margin-bottom: 18px;
        }
        .title {
            margin: 0 0 10px;
            font-size: 28px;
            font-weight: 700;
        }
        .subtitle {
            margin: 0;
            color: var(--muted);
        }
        .grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 16px;
        }
        .card {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 22px 24px;
        }
        .section {
            margin-top: 18px;
        }
        .section h2 {
            margin: 0 0 12px;
            font-size: 18px;
        }
        .meta {
            display: grid;
            gap: 10px;
        }
        .row {
            display: grid;
            grid-template-columns: 180px 1fr;
            gap: 12px;
            align-items: start;
        }
        .label {
            color: var(--muted);
            font-weight: 700;
        }
        .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0;
        }
        .badge.post { background: #e7f0ff; color: var(--accent); }
        .badge.success { background: #e6f7ec; color: var(--good); }
        .badge.error { background: #fdecec; color: #b42318; }
        .note {
            color: var(--muted);
            font-size: 14px;
        }
        pre {
            margin: 0;
            padding: 16px;
            border-radius: 10px;
            background: #0b1220;
            color: #dbe4ff;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.5;
        }
        code {
            font-family: Consolas, Monaco, 'Courier New', monospace;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .table th,
        .table td {
            border-top: 1px solid var(--line);
            padding: 10px 8px;
            text-align: left;
            vertical-align: top;
            font-size: 14px;
        }
        .table th {
            color: var(--muted);
            width: 180px;
        }
        .stack > * + * {
            margin-top: 14px;
        }
        .small {
            font-size: 13px;
            color: var(--muted);
        }
        @media (max-width: 820px) {
            .grid { grid-template-columns: 1fr; }
            .row { grid-template-columns: 1fr; }
            .title { font-size: 24px; }
        }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <h1 class="title">Online Terminal Cancel Seat API Documentation</h1>
        <p class="subtitle">Seat cancellation is created first as pending approval. Deduction is applied during approval and the remaining amount is refunded.</p>
    </div>

    <div class="grid">
        <div class="card stack">
            <div class="section">
                <h2>Endpoint</h2>
                <div class="meta">
                    <div class="row">
                        <div class="label">Method</div>
                        <div><span class="badge post">POST</span></div>
                    </div>
                    <div class="row">
                        <div class="label">URL</div>
                        <div><code>https://api.kainattravels.net/api/v1/online-terminals/cancel-seat</code></div>
                    </div>
                    <div class="row">
                        <div class="label">Legacy URL</div>
                        <div><code>https://api.kainattravels.net/api/v1/bookkaru/cancel-seat</code></div>
                    </div>
                    <div class="row">
                        <div class="label">API Key Header</div>
                        <div><code>X-ONLINE-TERMINAL-API-KEY</code> <span class="small">(legacy <code>X-BOOKKARU-API-KEY</code> is also accepted)</span></div>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2>Request Body JSON</h2>
                <pre><code>{
  "request_id": "api-test-3",
  "invoice_id": 865127,
  "transaction_id": "123456789",
  "seat_numbers": ["6 up"],
  "cancellation_reason": "Cancelled from online terminal API",
  "deduction_percentage": 25,
  "source": "Bookkaru"
}</code></pre>
            </div>

            <div class="section">
                <h2>Field Details</h2>
                <table class="table">
                    <tbody>
                    <tr>
                        <th>request_id</th>
                        <td>string, required, unique request ID from the online terminal</td>
                    </tr>
                    <tr>
                        <th>invoice_id</th>
                        <td>integer, required, Kainat invoice ID</td>
                    </tr>
                    <tr>
                        <th>transaction_id</th>
                        <td>string, required, Kainat ticket transaction ID. Legacy <code>booking_reference</code> is temporarily accepted as an alias.</td>
                    </tr>
                    <tr>
                        <th>seat_numbers</th>
                        <td>array, required, seat numbers to cancel</td>
                    </tr>
                    <tr>
                        <th>cancellation_reason</th>
                        <td>string, required, reason for cancellation</td>
                    </tr>
                    <tr>
                        <th>deduction_percentage</th>
                        <td>numeric, optional, 0 to 100. If omitted, backend uses <code>ONLINE_TERMINAL_DEDUCTION_PERCENTAGE</code>. Legacy <code>refund_percentage</code> is temporarily accepted as an alias for deduction percentage.</td>
                    </tr>
                    <tr>
                        <th>source</th>
                        <td>string, required, request source. Supported examples: <code>Bookkaru</code>, <code>SastaTicket</code>, <code>Bookme</code>, and future online terminals.</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="section">
                <h2>Refund Rule</h2>
                <p class="note">
                    Deduction and refund are calculated per seat as:
                    <br><code>deduction_amount = seat_fare * deduction_percentage / 100</code>
                    <br><code>refund_percentage = 100 - deduction_percentage</code>
                    <br><code>refund_amount = seat_fare * refund_percentage / 100</code>
                </p>
            </div>
        </div>

        <div class="card stack">
            <div class="section">
                <h2>Initial Success Response</h2>
                <span class="badge success">Pending Approval</span>
                <pre><code>{
  "status": "success",
  "message": "Cancellation request received and is pending approval.",
  "data": {
    "request_id": "api-test-4",
    "invoice_id": 866245,
    "transaction_id": "123456789",
    "seat_numbers": [
      "6 up"
    ],
    "deduction_percentage": 25,
    "refund_percentage": 75,
    "approval_status": "pending",
    "cancellation_status": "pending",
    "source": "Bookkaru"
  },
  "error": null
}</code></pre>
            </div>

            <div class="section">
                <h2>After Approval Success Response</h2>
                <span class="badge success">Processed</span>
                <pre><code>{
  "status": "success",
  "message": "Seat cancellation successful.",
  "data": {
    "request_id": "api-test-4",
    "invoice_id": 866245,
    "transaction_id": "123456789",
    "cancelled_seats": [
      "6 up"
    ],
    "deduction_percentage": 25,
    "deduction_amount": 625,
    "refund_percentage": 75,
    "refund_amount": 1875,
    "refunds": [
      {
        "ticket_id": 1493038,
        "seat_no": "6 up",
        "transaction_id": "123456789",
        "seat_fare": 2500,
        "deduction_percentage": 25,
        "deduction_amount": 625,
        "refund_percentage": 75,
        "refund_amount": 1875,
        "refund_reason": "Cancelled from online terminal API"
      }
    ],
    "approved_at": "2026-06-10 15:33:00",
    "cancelled_at": "2026-06-10 15:33:00"
  },
  "error": null
}</code></pre>
            </div>

            <div class="section">
                <h2>Validation Error Response</h2>
                <span class="badge error">Validation</span>
                <pre><code>{
  "status": "error",
  "message": "Validation failed",
  "data": null,
  "error": {
    "request_id": [
      "The request id field is required."
    ],
    "invoice_id": [
      "The invoice id field is required."
    ],
    "transaction_id": [
      "The transaction id field is required."
    ],
    "seat_numbers": [
      "The seat numbers field is required."
    ],
    "cancellation_reason": [
      "The cancellation reason field is required."
    ],
    "source": [
      "The source field is required."
    ]
  }
}</code></pre>
            </div>

            <div class="section">
                <h2>Notes</h2>
                <p class="small">
                    If <code>deduction_percentage</code> is not sent by a legacy integration, the backend uses the default
                    <code>ONLINE_TERMINAL_DEDUCTION_PERCENTAGE</code> setting. Duplicate <code>request_id</code> values return the existing response and do not process cancellation or refund again.
                    A new <code>request_id</code> for the same <code>invoice_id</code>, <code>transaction_id</code>, and seat is blocked while an active cancellation request already exists.
                    The cancellation request is created first as pending approval, then the approved flow cancels the seat and stores refund details per ticket.
                </p>
            </div>
        </div>
    </div>
</div>
</body>
</html>
