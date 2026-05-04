@php($visibleColumnsLookup = array_fill_keys($visibleColumns ?? [], true))

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

    <title> Reschedule Report</title>
</head>

<body>
<div style="border: 2px solid black; padding: 15px 3px 5px 3px !important;">
    <div id="info">
        <div class="companyName"><span>Kainat Travels</span></div>
    </div>
    <br>
    <br>
    <div id="info">
        <div class="companyName"><span>Reschedule Report</span></div>
    </div>
    <br>
    <table border="2" style="text-align: center;">
        <thead>
        <tr>
            @if(!empty($visibleColumnsLookup['terminal_name']))
                <th>Terminal Name</th>
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
            @if(!empty($visibleColumnsLookup['status']))
                <th>Status</th>
            @endif
            @if(!empty($visibleColumnsLookup['current_status']))
                <th>Current Status</th>
            @endif
            @if(!empty($visibleColumnsLookup['from_bus_time']))
                <th>From Bus Time</th>
            @endif
            @if(!empty($visibleColumnsLookup['to_bus_time']))
                <th>To Bus Time</th>
            @endif
            @if(!empty($visibleColumnsLookup['reschedule_from']))
                <th>Reschedule From</th>
            @endif
            @if(!empty($visibleColumnsLookup['reschedule_to']))
                <th>Reschedule To</th>
            @endif
            @if(!empty($visibleColumnsLookup['from_seat']))
                <th>From Seat</th>
            @endif
            @if(!empty($visibleColumnsLookup['to_seat']))
                <th>To Seat</th>
            @endif
            @if(!empty($visibleColumnsLookup['old_fare']))
                <th>Old Fare</th>
            @endif
            @if(!empty($visibleColumnsLookup['new_fare']))
                <th>New Fare</th>
            @endif
            @if(!empty($visibleColumnsLookup['remarks']))
                <th>Remarks</th>
            @endif
            @if(!empty($visibleColumnsLookup['reschedule_by']))
                <th>Over Issue By</th>
            @endif
            @if(!empty($visibleColumnsLookup['reschedule_time']))
                <th>Over Issue Time</th>
            @endif
        </tr>
        </thead>
        <tbody>
        @foreach($tickets as $single)
            <tr>
                @if(!empty($visibleColumnsLookup['terminal_name']))
                    <td>{{ $single->terminal_name ? $single->terminal_name : 'Not Fetched'  }}</td>
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
                @if(!empty($visibleColumnsLookup['status']))
                    <td>{{ $single->type }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['current_status']))
                    <td>{{ $single->new_type }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['from_bus_time']))
                    <td>{{ $single->old_bus_time }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['to_bus_time']))
                    <td>{{ $single->new_bus_time }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['reschedule_from']))
                    <td>{{ $single->old_departure.'-'.$single->old_destination }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['reschedule_to']))
                    <td>{{ $single->new_departure.'-'.$single->new_destination }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['from_seat']))
                    <td>{{ $single->old_seat }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['to_seat']))
                    <td>{{ $single->new_seat }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['old_fare']))
                    <td>{{ $single->old_fare }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['new_fare']))
                    <td>{{ $single->new_fare }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['remarks']))
                    <td>{{ $single->reason }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['reschedule_by']))
                    <td>{{ $single->reschedule_by }}</td>
                @endif
                @if(!empty($visibleColumnsLookup['reschedule_time']))
                    <td>{{ $single->reschedule_time }}</td>
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
</body>
</html>
