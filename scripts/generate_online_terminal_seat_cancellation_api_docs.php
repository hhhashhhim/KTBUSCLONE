<?php

require __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$outputPath = realpath(__DIR__ . '/../public/docs') . DIRECTORY_SEPARATOR . 'online-terminal-seat-cancellation-api-docs.pdf';

$requestJson = <<<'JSON'
{
  "request_id": "api-test-6",
  "invoice_id": 878700,
  "transaction_id": "123456789",
  "seat_numbers": ["5 up"],
  "cancellation_reason": "Cancelled from sasta ticket API",
  "deduction_percentage": 25,
  "source": "sasta ticket"
}
JSON;

$successJson = <<<'JSON'
{
  "status": "success",
  "message": "Seat cancellation successful.",
  "data": {
    "request_id": "api-test-7",
    "invoice_id": 878700,
    "transaction_id": "123456789",
    "cancelled_seats": ["5 up"],
    "deduction_percentage": 25,
    "deduction_amount": 2250,
    "refund_percentage": 75,
    "refund_amount": 6750,
    "refunds": [
      {
        "ticket_id": 1513061,
        "seat_no": "5 up",
        "transaction_id": "123456789",
        "seat_fare": 9000,
        "deduction_percentage": 25,
        "deduction_amount": 2250,
        "refund_percentage": 75,
        "refund_amount": 6750,
        "refund_reason": "Cancelled from sasta ticket API"
      }
    ],
    "approved_at": null,
    "cancelled_at": null
  },
  "error": null
}
JSON;

$errorJson = <<<'JSON'
{
  "status": "error",
  "message": "Validation failed",
  "error": {
    "request_id": ["The request id field is required."],
    "invoice_id": ["The invoice id field is required."]
  }
}
JSON;

$html = '
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Online Terminal Seat Cancellation API Docs</title>
    <style>
        @page { margin: 26px 32px 34px; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #172033;
            font-size: 11px;
            line-height: 1.45;
            margin: 0;
        }
        .header {
            background: #0b3b63;
            color: #ffffff;
            padding: 18px 22px;
            border-radius: 8px;
            margin-bottom: 18px;
        }
        .brand {
            font-size: 13px;
            letter-spacing: .06em;
            text-transform: uppercase;
            opacity: .9;
            margin-bottom: 5px;
        }
        h1 {
            font-size: 25px;
            margin: 0 0 5px;
            line-height: 1.2;
        }
        .subtitle {
            font-size: 12px;
            opacity: .92;
            margin: 0;
        }
        h2 {
            font-size: 16px;
            color: #0b3b63;
            border-bottom: 1px solid #d7e1ea;
            padding-bottom: 5px;
            margin: 18px 0 9px;
        }
        h3 {
            font-size: 13px;
            color: #163a59;
            margin: 13px 0 7px;
        }
        p { margin: 0 0 8px; }
        .meta {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0 12px;
        }
        .meta td {
            border: 1px solid #d7e1ea;
            padding: 8px 10px;
            vertical-align: top;
        }
        .meta .label {
            width: 120px;
            background: #f3f7fa;
            color: #36536a;
            font-weight: bold;
        }
        .pill {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 999px;
            background: #e9f4ff;
            color: #0b5794;
            font-weight: bold;
            font-size: 10px;
        }
        code.inline {
            background: #eef3f7;
            color: #0f2d43;
            padding: 1px 4px;
            border-radius: 3px;
            font-family: DejaVu Sans Mono, Consolas, monospace;
            font-size: 10px;
        }
        pre {
            background: #111827;
            color: #f8fafc;
            padding: 12px 14px;
            border-radius: 7px;
            font-family: DejaVu Sans Mono, Consolas, monospace;
            font-size: 8.7px;
            line-height: 1.35;
            white-space: pre-wrap;
            word-wrap: break-word;
            margin: 8px 0 12px;
        }
        table.fields {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0 12px;
        }
        table.fields th {
            background: #0b3b63;
            color: #ffffff;
            font-size: 10.5px;
            text-align: left;
            padding: 8px;
        }
        table.fields td {
            border: 1px solid #d7e1ea;
            padding: 7px 8px;
            vertical-align: top;
        }
        table.fields tr:nth-child(even) td { background: #f8fbfd; }
        .note {
            border-left: 4px solid #0b7fab;
            background: #eef8fb;
            padding: 10px 12px;
            border-radius: 5px;
            margin: 9px 0 12px;
        }
        .warning {
            border-left-color: #c7831d;
            background: #fff8ea;
        }
        ul {
            margin: 6px 0 10px 18px;
            padding: 0;
        }
        li { margin-bottom: 4px; }
        .footer {
            color: #687789;
            font-size: 9px;
            border-top: 1px solid #d7e1ea;
            padding-top: 8px;
            margin-top: 18px;
        }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">Kainat Travels API Docs</div>
        <h1>Online Terminal Seat Cancellation API</h1>
        <p class="subtitle">Developer documentation for external terminal integrations</p>
    </div>

    <h2>1. API Overview</h2>
    <p>This API is used by online ticketing terminals such as Bookkaru, SastaTicket, Bookme, and future approved terminal partners.</p>
    <p>Its purpose is to request seat cancellation through Kainat Travels approval workflow. The API creates a cancellation request immediately, while actual seat cancellation and refund processing remain subject to staff approval in the portal.</p>

    <table class="meta">
        <tr>
            <td class="label">Endpoint</td>
            <td><code class="inline">POST /api/v1/online-terminals/cancel-seat</code></td>
        </tr>
        <tr>
            <td class="label">Content Type</td>
            <td><code class="inline">application/json</code></td>
        </tr>
        <tr>
            <td class="label">Workflow</td>
            <td>Approval based. Seat remains active until approved from the portal.</td>
        </tr>
    </table>

    <h2>2. Request Specification</h2>
    <table class="meta">
        <tr>
            <td class="label">Method</td>
            <td><span class="pill">POST</span></td>
        </tr>
        <tr>
            <td class="label">Headers</td>
            <td>
                <code class="inline">Content-Type: application/json</code><br>
                <code class="inline">Authorization: Bearer Token</code> if issued to the terminal.<br>
                API key headers may also be used where configured.
            </td>
        </tr>
    </table>

    <h3>Request Body Example</h3>
    <pre>' . htmlspecialchars($requestJson, ENT_QUOTES, 'UTF-8') . '</pre>

    <h2>3. Field Descriptions</h2>
    <table class="fields">
        <thead>
            <tr>
                <th>Field</th>
                <th>Required</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code class="inline">request_id</code></td>
                <td>Yes</td>
                <td>Unique request identifier supplied by the terminal. Duplicate request IDs return the stored request response.</td>
            </tr>
            <tr>
                <td><code class="inline">invoice_id</code></td>
                <td>Yes</td>
                <td>Booking invoice ID created by Kainat Travels booking flow.</td>
            </tr>
            <tr>
                <td><code class="inline">transaction_id</code></td>
                <td>Yes*</td>
                <td>Ticket transaction ID. The legacy alias <code class="inline">booking_reference</code> is also supported.</td>
            </tr>
            <tr>
                <td><code class="inline">seat_numbers</code></td>
                <td>Yes</td>
                <td>Array of seat numbers to request for cancellation, for example <code class="inline">["5 up"]</code>.</td>
            </tr>
            <tr>
                <td><code class="inline">cancellation_reason</code></td>
                <td>Yes</td>
                <td>Reason provided by the terminal for the cancellation request.</td>
            </tr>
            <tr>
                <td><code class="inline">deduction_percentage</code></td>
                <td>No</td>
                <td>Deduction percentage from 0 to 100. If omitted, the configured default is used.</td>
            </tr>
            <tr>
                <td><code class="inline">source</code></td>
                <td>Yes</td>
                <td>Online terminal name, for example Bookkaru, SastaTicket, sasta ticket, or Bookme. Source is normalized by lowercasing and removing spaces.</td>
            </tr>
        </tbody>
    </table>
    <p><strong>*</strong> Required unless <code class="inline">booking_reference</code> is provided.</p>

    <div class="page-break"></div>

    <h2>4. Response Behavior</h2>
    <h3>Online Terminal Response: Success Format</h3>
    <p>For approved online terminal sources, the API returns a final-style success response for a better terminal user experience.</p>
    <div class="note">
        <strong>Important:</strong> This response is an API acknowledgment only. Internally, the cancellation remains pending approval.
    </div>
    <ul>
        <li>Message is returned as <code class="inline">Seat cancellation successful.</code></li>
        <li>Response includes <code class="inline">deduction_amount</code>, <code class="inline">refund_amount</code>, <code class="inline">cancelled_seats</code>, and a <code class="inline">refunds</code> array.</li>
        <li><code class="inline">approved_at</code> is returned as <code class="inline">null</code>.</li>
        <li><code class="inline">cancelled_at</code> is returned as <code class="inline">null</code>.</li>
    </ul>

    <h3>Internal System Behavior</h3>
    <div class="note warning">
        The cancellation request is stored as pending. Staff must approve or reject it from the portal:
        <br><code class="inline">https://portal.kainattravels.net/booking/bookkaru/cancellations</code>
    </div>
    <ul>
        <li>No actual seat cancellation happens when this API responds.</li>
        <li>Seat remains active until approved in the portal.</li>
        <li>Final refund processing is performed after approval only.</li>
        <li>Approval workflow remains unchanged.</li>
    </ul>

    <h2>5. Success Response Example</h2>
    <pre>' . htmlspecialchars($successJson, ENT_QUOTES, 'UTF-8') . '</pre>

    <h2>6. Error Response Example</h2>
    <pre>' . htmlspecialchars($errorJson, ENT_QUOTES, 'UTF-8') . '</pre>

    <h2>7. Important Notes</h2>
    <ul>
        <li>Duplicate <code class="inline">request_id</code> is not allowed for a new cancellation request.</li>
        <li>A seat cannot be cancelled twice.</li>
        <li>The approval system remains unchanged.</li>
        <li>Final refund is calculated and processed after portal approval.</li>
        <li>The online terminal API response is only an acknowledgment/preview for external terminals.</li>
    </ul>

    <div class="footer">
        Kainat Travels API Docs - Online Terminal Seat Cancellation - Generated for external terminal integrations.
    </div>
</body>
</html>';

$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

file_put_contents($outputPath, $dompdf->output());

echo $outputPath . PHP_EOL;
