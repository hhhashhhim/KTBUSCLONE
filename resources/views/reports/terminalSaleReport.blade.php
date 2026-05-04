@php
    $visibleColumnsLookup = array_fill_keys($visibleColumns ?? [], true);
    $ticketCount = $tickets->count();
    $totalSale = 0;
    $totalRefund = 0;
    $totalCommission = 0;
    $totalNetCash = 0;

    foreach ($tickets as $ticket) {
        $ticketSale = $ticket->type !== 'canceled' ? ((float) $ticket->seat_fare - (float) $ticket->discount) : 0;
        $ticketRefund = $ticket->type === 'canceled' ? ((float) ($ticket->refund ?? 0)) : 0;
        $ticketCommission = $ticket->type === 'canceled' ? 0 : ((float) ($ticket->comsn ?? 0));
        $ticketNetCash = $ticketSale + $ticketRefund - $ticketCommission;

        $totalSale += $ticketSale;
        $totalRefund += $ticketRefund;
        $totalCommission += $ticketCommission;
        $totalNetCash += $ticketNetCash;
    }
@endphp

<!DOCTYPE html>
<html>
<head>
    <style>
        @page {
            transform: rotate(-90deg);
            padding: 0;
            margin: 10px;
        }

        body {
            height: 10%;
            overflow: scroll;
            margin: 40px 30px 40px 30px;
            font-size: 6pt;
            font-family: Verdana, Arial, sans-serif;
        }

        .companyName {
            font-weight: 800;
            font-size: 18pt;
            text-transform: uppercase;
            margin-top: -15px;
            text-align: center;
            font-family: sans-serif, Verdana, Arial;
        }

        table {
            padding: 10px;
            font-size: 10pt !important;
            border-collapse: collapse;
            width: 100% !important;
        }
    </style>

    <title>Terminal Sales Report</title>
</head>

<body>
<div style="border: 2px solid black; padding: 15px 3px 5px 3px !important;">
    <div id="info">
        <div class="companyName"><span>Terminal Sales Report</span></div>
    </div>
    <br>
    <table border="2" style="text-align: center;">
        <thead>
        <tr>
            @if(!empty($visibleColumnsLookup['bus_time']))
                <th>Bus Time</th>
            @endif
            @if(!empty($visibleColumnsLookup['bus_number']))
                <th>Bus No</th>
            @endif
            @if(!empty($visibleColumnsLookup['bus_class']))
                <th>Bus Class</th>
            @endif
            @if(!empty($visibleColumnsLookup['route']))
                <th>Route</th>
            @endif
            @if(!empty($visibleColumnsLookup['passenger_name']))
                <th>Name</th>
            @endif
            @if(!empty($visibleColumnsLookup['passenger_cnic']))
                <th>Cnic</th>
            @endif
            @if(!empty($visibleColumnsLookup['passenger_contact']))
                <th>Contact</th>
            @endif
            @if(!empty($visibleColumnsLookup['seat_no']))
                <th>Seat No</th>
            @endif
            @if(!empty($visibleColumnsLookup['invoice_id']))
                <th>Invoice</th>
            @endif
            @if(!empty($visibleColumnsLookup['transaction_id']))
                <th>Transaction id</th>
            @endif
            @if(!empty($visibleColumnsLookup['terminal_name']))
                <th>Terminal Name</th>
            @endif
            @if(!empty($visibleColumnsLookup['status']))
                <th>Status</th>
            @endif
            @if(!empty($visibleColumnsLookup['action_by']))
                <th>Action By</th>
            @endif
            @if(!empty($visibleColumnsLookup['sale']))
                <th>Sale</th>
            @endif
            @if(!empty($visibleColumnsLookup['refund']))
                <th>Refund</th>
            @endif
            @if(!empty($visibleColumnsLookup['commission']))
                <th>Commission</th>
            @endif
            @if(!empty($visibleColumnsLookup['net_cash']))
                <th>Net Cash</th>
            @endif
        </tr>
        </thead>

        <tbody>
        @foreach($tickets as $single)
            @php
                $sale = $single->type !== 'canceled' ? ((float) $single->seat_fare - (float) $single->discount) : 0;
                $refund = $single->type === 'canceled' ? ((float) ($single->refund ?? 0)) : 0;
                $commission = $single->type === 'canceled' ? 0 : ((float) ($single->comsn ?? 0));
                $netCash = $sale + $refund - $commission;
            @endphp
            <tr>
                @if(!empty($visibleColumnsLookup['bus_time']))
                    <td>{{ $single->schedule_date }}<br>{{ $single->schedule_time }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['bus_number']))
                    <td>{{ $single->bus ? $single->bus->bus_number : 'N/A' }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['bus_class']))
                    <td>{{ $single->busClass->name }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['route']))
                    <td>{{ $single->route->name }} ({{ $single->route->via ?? 'n/a' }})</td>
                @endif
                @if(!empty($visibleColumnsLookup['passenger_name']))
                    <td>{{ $single->customer->name }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['passenger_cnic']))
                    <td>{{ $single->customer->cnic }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['passenger_contact']))
                    <td>{{ $single->customer->contact }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['seat_no']))
                    <td>{{ $single->seat_no }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['invoice_id']))
                    <td>{{ $single->invoice_id }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['transaction_id']))
                    <td>{{ $single->transaction_id ?? 0 }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['terminal_name']))
                    <td>{{ $single->terminal->name }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['status']))
                    <td>{{ $single->type }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['action_by']))
                    <td>{{ $single->updated_name->name ?? 'N/A' }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['sale']))
                    <td>{{ $sale }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['refund']))
                    <td>{{ $refund }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['commission']))
                    <td>{{ $commission }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['net_cash']))
                    <td>{{ $netCash }}</td>
                @endif
            </tr>
        @endforeach
        <tr>
            @if(!empty($visibleColumnsLookup['bus_time']))
                <th></th>
            @endif
            @if(!empty($visibleColumnsLookup['bus_number']))
                <th></th>
            @endif
            @if(!empty($visibleColumnsLookup['bus_class']))
                <th></th>
            @endif
            @if(!empty($visibleColumnsLookup['route']))
                <th></th>
            @endif
            @if(!empty($visibleColumnsLookup['passenger_name']))
                <th></th>
            @endif
            @if(!empty($visibleColumnsLookup['passenger_cnic']))
                <th></th>
            @endif
            @if(!empty($visibleColumnsLookup['passenger_contact']))
                <th></th>
            @endif
            @if(!empty($visibleColumnsLookup['seat_no']))
                <th>{{ $ticketCount }}</th>
            @endif
            @if(!empty($visibleColumnsLookup['invoice_id']))
                <th></th>
            @endif
            @if(!empty($visibleColumnsLookup['transaction_id']))
                <th></th>
            @endif
            @if(!empty($visibleColumnsLookup['terminal_name']))
                <th></th>
            @endif
            @if(!empty($visibleColumnsLookup['status']))
                <th></th>
            @endif
            @if(!empty($visibleColumnsLookup['action_by']))
                <th></th>
            @endif
            @if(!empty($visibleColumnsLookup['sale']))
                <th>{{ $totalSale }}</th>
            @endif
            @if(!empty($visibleColumnsLookup['refund']))
                <th>{{ $totalRefund }}</th>
            @endif
            @if(!empty($visibleColumnsLookup['commission']))
                <th>{{ $totalCommission }}</th>
            @endif
            @if(!empty($visibleColumnsLookup['net_cash']))
                <th>{{ $totalNetCash }}</th>
            @endif
        </tr>
        </tbody>
    </table>
</div>
</body>
</html>
