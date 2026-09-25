<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>Ticket activity - Invoice {{ $invoice_id }}</title>
<style>
body{font-family:Arial,sans-serif;color:#233047;margin:25px;font-size:12px}h1{font-size:23px}h2{font-size:17px}.note{padding:12px;background:#f3f5f8}.ticket{border:1px solid #cfd6e1;padding:14px;margin:16px 0;break-inside:avoid}.status{font-weight:bold;color:#9a3412}table{border-collapse:collapse;width:100%;margin-top:12px}th,td{padding:8px;border:1px solid #cfd6e1;text-align:left;vertical-align:top;overflow-wrap:anywhere}th{background:#eef1f6}.ticket td{width:50%}tr{break-inside:avoid}button{padding:9px 18px;cursor:pointer}@page{size:A4;margin:12mm}@media print{body{margin:0;font-size:10px}.print-controls{display:none}thead{display:table-header-group}}
</style></head><body>
<div class="print-controls"><button onclick="window.print()">Print / Save PDF</button></div>
<h1>Ticket Details &amp; Activity</h1><p>Invoice <strong>{{ $invoice_id }}</strong> &middot; Generated {{ now()->format('d-M-Y h:i A') }}</p>
<p class="note">{{ $note }}</p>
@foreach ($tickets as $ticket)
<section class="ticket"><h2>Seat {{ $ticket['seat'] }} &middot; Ticket #{{ $ticket['id'] }}</h2>
<p class="status">{{ $ticket['status'] }}{{ $ticket['historical'] ? ' — Previous seat (rescheduled)' : '' }}{{ $ticket['removed'] ? ' — Archived record' : '' }}</p>
<table>
<tr><td><b>Passenger:</b> {{ $ticket['passenger'] ?: 'Not recorded' }}</td><td><b>Contact:</b> {{ $ticket['contact'] ?: 'Not recorded' }}</td></tr>
<tr><td><b>CNIC:</b> {{ $ticket['cnic'] ?: 'Not recorded' }}</td><td><b>Transaction:</b> {{ $ticket['transaction_id'] ?: 'Not recorded' }}</td></tr>
<tr><td><b>Route:</b> {{ $ticket['route'] ?: 'Not recorded' }}</td><td><b>Departure (saved):</b> {{ $ticket['departure'] }}</td></tr>
<tr><td><b>Terminal:</b> {{ $ticket['terminal'] ?: 'Not recorded' }}</td><td><b>Bus / class:</b> {{ $ticket['bus'] ?: 'Not recorded' }} / {{ $ticket['bus_class'] ?: 'Not recorded' }}</td></tr>
<tr><td><b>Seat fare:</b> Rs {{ number_format($ticket['fare'], 2) }}<br><b>Discount:</b> Rs {{ number_format($ticket['discount'], 2) }}</td><td><b>Net ticket fare:</b> Rs {{ number_format($ticket['net_fare'], 2) }}<br><b>Recorded refund:</b> {{ $ticket['recorded_refund'] === null ? 'Not recorded' : 'Rs ' . number_format($ticket['recorded_refund'], 2) }}</td></tr>
</table>
@if ($ticket['remarks'])<p><b>Remarks:</b> {{ $ticket['remarks'] }}</p>@endif
@if ($ticket['cancellation_reason'])<p><b>Cancellation reason:</b> {{ $ticket['cancellation_reason'] }}</p>@endif
</section>
@endforeach
<h2>Activity history</h2><table><thead><tr><th>Date &amp; time</th><th>Seat</th><th>Action / user</th><th>Details</th></tr></thead><tbody>
@foreach ($events as $event)
<tr><td>{{ $event['at'] ?: 'Not recorded' }}</td><td>{{ $event['seat'] }}</td><td><b>{{ $event['action'] }}</b><br>{{ $event['actor'] }}</td><td>{{ $event['detail'] }}</td></tr>
@endforeach
</tbody></table></body></html>
