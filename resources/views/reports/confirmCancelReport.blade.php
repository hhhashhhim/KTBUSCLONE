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
    <table border="2" style="text-align: center;">
        <thead>
        <tr>
            <th>Bus Time</th>
            <th>Terminal Name</th>
            <th>Cancel By</th>
            <th>Seat No</th>
            <th>Type</th>
            <th>Passenger Name</th>
            <th>Cell NO</th>
            <th>Total Fare</th>
            <th>Cancellation Percentage</th>
            <th>Amount Refund</th>
            <th>Cancellation Charges</th>
            <th>Remarks</th>
            <th>Cancellation Date</th>
        </tr>
        </thead>
        <tbody>
        @foreach($tickets as $single)
            <tr>
                <td>{{ $single->bus_time}}</td>
                <td>{{ $single->terminal_name}}</td>
                <td>{{ $single->cancel_by }}</td>
                <td>{{ $single->seat_no}}</td>
                <td>{{ $single->type}}</td>
                <td>{{ $single->passenger_name}}</td>
                <td>{{ $single->passenger_contact}}</td>
                <td>{{ $single->total_fare}}</td>
                <td>{{ $single->cancel_percentage}} %</td>
                <td>{{ $single->amount_refund}}</td>
                <td>{{ $single->cancelation_charges}}</td>
                <td>{{ $single->cancel_reason}}</td>
                <td>{{ $single->cancel_date}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
</body>
</html>
