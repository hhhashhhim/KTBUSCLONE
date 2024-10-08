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
            <th>Terminal Name</th>
            <th>Passenger Name</th>
            <th>Cell NO</th>
            <th>Status</th>
            <th>Current Status</th>
            <th>From Bus Time</th>
            <th>To Bus Time</th>
            <th>Reschedule From</th>
            <th>Reschedule To</th>
            <th>From Seat</th>
            <th>To Seat</th>
            <th>Old Fare</th>
            <th>New Fare</th>
            <th>Remarks</th>
            <th>Over Issue By</th>
            <th>Over Issue Time</th>
        </tr>
        </thead>
        <tbody>
        @foreach($tickets as $single)
            <tr>
                <td>{{ $single->terminal_name ? $single->terminal_name : 'Not Fetched'  }}</td>
                <td>{{ $single->passenger_name }}</td>
                <td>{{ $single->passenger_contact }}</td>
                <td>{{ $single->type }}</td>
                <td>{{ $single->new_type }}</td>
                <td>{{ $single->old_bus_time }}</td>
                <td>{{ $single->new_bus_time }}</td>
                <td>{{ $single->old_departure.'-'.$single->old_destination }}</td>
                <td>{{ $single->new_departure.'-'.$single->new_destination }}</td>
                <td>{{ $single->old_seat }}</td>
                <td>{{ $single->new_seat }}</td>
                <td>{{ $single->old_fare }}</td>
                <td>{{ $single->new_fare }}</td>
                <td>{{ $single->reason }}</td>
                <td>{{ $single->reschedule_by }}</td>
                <td>{{ $single->reschedule_time }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
</body>
</html>
