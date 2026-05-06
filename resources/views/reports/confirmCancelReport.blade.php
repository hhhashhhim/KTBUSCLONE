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

        .red {
            background-color: #ec3030;
        }

        .green {
            background-color: #03b203;
        }

        .white {
            background-color: #ffffff;
        }
    </style>

    <title> Confirm Cancellation Report</title>
</head>

<body>
<div style="border: 2px solid black; padding: 15px 3px 5px 3px !important;">
    <div id="info">
        <div class="companyName"><span>Kainat Travels</span></div>
    </div>
    <br>
    <br>
    <div id="info">
        <div class="companyName"><span>Confirm Cancellation Report</span></div>
    </div>
    <br>
    @php($visibleColumnsLookup = array_fill_keys($visibleColumns ?? [], true))

<table border="2" style="text-align: center;">
    <thead>
        <tr>
            @if(!empty($visibleColumnsLookup['bus_time']))
                <th width="200px">Bus Time</th>
            @endif
            @if(!empty($visibleColumnsLookup['cancel_date']))
                <th width="200px">Cancellation Date</th>
            @endif
            @if(!empty($visibleColumnsLookup['remarks']))
                <th>Remarks</th>
            @endif
            @if(!empty($visibleColumnsLookup['terminal_name']))
                <th>Terminal Name</th>
            @endif
            @if(!empty($visibleColumnsLookup['route']))
                <th>Route</th>
            @endif
            @if(!empty($visibleColumnsLookup['transaction_id']))
                <th>Transaction #</th>
            @endif
            @if(!empty($visibleColumnsLookup['invoice']))
                <th>Invoice</th>
            @endif
            @if(!empty($visibleColumnsLookup['cancel_by']))
                <th>Cancel By</th>
            @endif
            @if(!empty($visibleColumnsLookup['seat_no']))
                <th>Seat No</th>
            @endif
            @if(!empty($visibleColumnsLookup['type']))
                <th>Type</th>
            @endif
            @if(!empty($visibleColumnsLookup['passenger_name']))
                <th>Passenger Name</th>
            @endif
            @if(!empty($visibleColumnsLookup['passenger_contact']))
                <th>Cell NO</th>
            @endif
            @if(!empty($visibleColumnsLookup['passenger_cnic']))
                <th>Cnic NO</th>
            @endif
            @if(!empty($visibleColumnsLookup['total_fare']))
                <th>Total Fare</th>
            @endif
            @if(!empty($visibleColumnsLookup['cancel_percentage']))
                <th>Cancellation Percentage</th>
            @endif
            @if(!empty($visibleColumnsLookup['amount_refund']))
                <th>Amount Refund</th>
            @endif
            @if(!empty($visibleColumnsLookup['cancelation_charges']))
                <th>Cancellation Charges</th>
            @endif
        </tr>
    </thead>

    <tbody>
        @foreach($tickets as $single)
            <tr class="{{ $single->cancellation_status_color ?? $single->badge ?? 'white' }}">
                @if(!empty($visibleColumnsLookup['bus_time']))
                    <td>{{ $single->bus_time }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['cancel_date']))
                    <td>{{ $single->cancel_date }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['remarks']))
                    <td>{{ $single->cancel_reason }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['terminal_name']))
                    <td>{{ $single->terminal_name }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['route']))
                    <td>{{ $single->route_name }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['transaction_id']))
                    <td>{{ $single->transaction_id ?? 'N/A' }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['invoice']))
                    <td>{{ $single->invoice_id }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['cancel_by']))
                    <td>{{ $single->cancel_by }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['seat_no']))
                    <td>{{ $single->seat_no }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['type']))
                    <td>{{ $single->type }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['passenger_name']))
                    <td>{{ $single->passenger_name }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['passenger_contact']))
                    <td>{{ $single->passenger_contact }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['passenger_cnic']))
                    <td>{{ $single->passenger_cnic }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['total_fare']))
                    <td>{{ $single->total_fare }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['cancel_percentage']))
                    <td>{{ $single->cancel_percentage }} %</td>
                @endif
                @if(!empty($visibleColumnsLookup['amount_refund']))
                    <td>{{ $single->amount_refund }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['cancelation_charges']))
                    <td>{{ $single->cancelation_charges }}</td>
                @endif
            </tr>
        @endforeach
    </tbody>
</table>

</div>
</body>
</html>
