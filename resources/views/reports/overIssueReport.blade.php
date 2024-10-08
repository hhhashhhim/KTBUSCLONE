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

    <title> Over Issue Report</title>
</head>

<body>
<div style="border: 2px solid black; padding: 15px 3px 5px 3px !important;">
    <div id="info">
        <div class="companyName"><span>Kainat Travels</span></div>
    </div>
    <br>
    <br>
    <div id="info">
        <div class="companyName"><span>Over Issue Report</span></div>
    </div>
    <br>
    <table border="2" style="text-align: center;">
        <thead>
        <tr>
            <th>Bus Time</th>
            <th>Terminal Name</th>
            <th>Seat No</th>
            <th>Type</th>
            <th>Passenger Name</th>
            <th>Cell NO</th>
            <th>Total Fare</th>
            <th>Remarks</th>
            <th>Over Issue By</th>
            <th>Over Issue Time</th>
        </tr>
        </thead>
        <tbody>
        @foreach($tickets as $single)
            <tr>
                <td>{{ $single->bus_time}}</td>
                <td>{{ $single->terminal_name}}</td>
                <td>{{ $single->seat_no}}</td>
                <td>{{ $single->type}}</td>
                <td>{{ $single->passenger_name}}</td>
                <td>{{ $single->passenger_contact}}</td>
                <td>{{ $single->total_fare}}</td>
                <td>{{ $single->overissue_reason}}</td>
                <td>{{ $single->overissue_by}}</td>
                <td>{{ $single->overissue_date }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
</body>
</html>
