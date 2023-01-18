<!DOCTYPE html>
<html>
<head>
    <style>
        @page {
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
            font-weight: 900;
            font-size: 20pt;
            text-transform: uppercase;
            margin-top: -15px;
            margin-bottom: 10px;
            text-align: center;
            font-family: sans-serif, Verdana, Arial;
        }

        #table1 {
            padding: 10px;
            font-size: 10pt !important;
            border-collapse: collapse;
            width: 100% !important;
        }

        #table2 {
            border: 1px solid black;
            padding: 10px;
            font-size: 10pt !important;
            border-collapse: collapse;
            width: 100% !important;
            text-align: center;
        }

        #table3 {
            border: 1px solid black;
            padding: 10px;
            font-size: 10pt !important;
            border-collapse: collapse;
            width: 100% !important;
            text-align: center;
        }

        .companyAddress {
            font-weight: 400;
            font-size: 15pt;
            margin-bottom: 10px;
            text-align: center;
            font-family: sans-serif, Verdana, Arial;
        }

        .centerTH {
            text-align: start;
            width: 17%;
        }

        .countPassenger {
            margin-top: 18px !important;
        }
    </style>
    <title>Print Bus Invoice</title>
</head>
<body>

<div id="info">
    <div class="companyName"><span>Kainat Travels</span></div>
    <div class="companyAddress">
        <span style="padding-bottom: 10px !important;text-transform: capitalize">{{ $infoData->current_terminal }} Terminal</span>
        <div><span><b>UAN(24/7) : </b>03-111-777-333
        </span></div>
    </div>
</div>
<br>
<hr>
<br>
<table border="2" id="table1">
    <tr>
        <th class="centerTH">Route:</th>
        <th>{{ $infoData->route }}</th>
        <th class="centerTH">Date& Time</th>
        <th>{{ date("m/d/Y h:i:s A",strtotime($infoData->departure_date.' '.$infoData->departure_time)) }}</th>
        <th class="centerTH">Bus No:</th>
        <th>-</th>
    </tr>
</table>
<br>
<hr>
<br>
<table border="2" id="table2" style="text-transform: capitalize;">
    <tr>
        <th style="width: 5% !important;">SR #</th>
        <th>Name</th>
        <th>Total Seat</th>
        <th>Destination</th>
        <th>Seat #</th>
        <th>Sale</th>
        <th>Discount</th>
        <th>Commission</th>
        <th>ELT Price</th>
        <th>Net Sale</th>
    </tr>
    @php
        $totalSeat = 0;
        $totalSale = 0;
        $totalDiscount = 0;
    @endphp
    @foreach($mainData as $terminal)
    @foreach($terminal as $destination)
    <tr>
        <td>SR #</td>
        <td>{{ $destination[0]->terminal->name }}</td>
        <td>{{ $destination->count() }}</td>
        @php
            $totalSeat += $destination->count()
        @endphp
        <td>{{ $destination[0]->destination_city->name }}</td>
        <td>{{ $destination->pluck('seat_no')->implode(",") }}</td>
        <td>{{ $destination->sum("seat_fare") }}</td>
        @php
            $totalSale += $destination->sum("seat_fare")
        @endphp
        <td>{{ $destination->sum("discount") }}</td>
        @php
            $totalDiscount += $destination->sum("discount")
        @endphp
        <td>Departure City Name</td>
        <td>Departure City Name</td>
        <td>{{ $destination->sum("seat_fare") - $destination->sum("discount") }}</td>
    </tr>
    @endforeach
    @endforeach
    <tr>
        <th colspan="2">Total</th>
        <th colspan="2">{{ $totalSeat }}</th>
        <th></th>
        <th>{{ $totalSale }}</th>
        <th>{{ $totalDiscount }}</th>
        <th>-</th>
        <th>-</th>
        <th>{{ $totalSale - $totalDiscount }}</th>
    </tr>
    <tr>
        <th colspan="8">Main Terminal Fixed Commision</th>
        <th>-</th>
        <th></th>
    </tr>
    <tr>
        <th colspan="8">Gross Sale</th>
        <th colspan="2">-</th>
    </tr>
</table>
<br>
<br>
<br>
<br>
<div style="padding-bottom: 8px;">
    <span style="font-weight: 900;font-size:12pt;">Driver One Name:</span>
    <span style="font-size: 12pt; padding-left: 10px;">-</span>
</div>
<div style="padding-bottom: 8px;">
    <span style="font-weight: 900;font-size:12pt;">Driver Two Name:</span>
    <span style="font-size: 12pt; padding-left: 10px;">-</span>
</div>
<div style="padding-bottom: 8px;">
    <span style="font-weight: 900;font-size:12pt;">HostessName:</span>
    <span style="font-size: 12pt; padding-left: 10px;">-</span>
</div>
<script type="text/javascript">
    // window.onload = function () {
    //     window.print();
    // }
</script>
</body>
</html>
