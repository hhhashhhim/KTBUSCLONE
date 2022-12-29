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
        <span style="padding-bottom: 10px !important;">Peshawar Road Pirwadhai Mor Rawalpindi</span>
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
        <th>RouteName</th>
        <th class="centerTH">Date& Time</th>
        <th>Date Time</th>
        <th class="centerTH">Bus No:</th>
        <th>Class Name</th>
    </tr>
</table>
<br>
<hr>
<br>
<table border="2" id="table2">
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
    <tr>
        <td>SR #</td>
        <td>Seat #</td>
        <td>Passenger Name</td>
        <td>CNIC</td>
        <td>Phone Number</td>
        <td>Terminal Name</td>
        <td>Departure City Name</td>
        <td>Departure City Name</td>
        <td>Departure City Name</td>
        <td>Departure City Name</td>
    </tr>
    <tr>
        <td>SR #</td>
        <td>Seat #</td>
        <td>Passenger Name</td>
        <td>CNIC</td>
        <td>Phone Number</td>
        <td>Terminal Name</td>
        <td>Departure City Name</td>
        <td>Departure City Name</td>
        <td>Departure City Name</td>
        <td>Departure City Name</td>
    </tr>
    <tr>
        <td>SR #</td>
        <td>Seat #</td>
        <td>Passenger Name</td>
        <td>CNIC</td>
        <td>Phone Number</td>
        <td>Terminal Name</td>
        <td>Departure City Name</td>
        <td>Departure City Name</td>
        <td>Departure City Name</td>
        <td>Departure City Name</td>
    </tr>
    <tr>
        <td>SR #</td>
        <td>Seat #</td>
        <td>Passenger Name</td>
        <td>CNIC</td>
        <td>Phone Number</td>
        <td>Terminal Name</td>
        <td>Departure City Name</td>
        <td>Departure City Name</td>
        <td>Departure City Name</td>
        <td>Departure City Name</td>
    </tr>
    <tr>
        <th colspan="2">Total</th>
        <th colspan="2">2</th>
        <th></th>
        <th>7000</th>
        <th>0</th>
        <th>700</th>
        <th>0</th>
        <th>6300</th>
    </tr>
    <tr>
        <th colspan="8">Main Terminal Fixed Commision</th>
        <th>500</th>
        <th></th>
    </tr>
    <tr>
        <th colspan="8">Gross Sale</th>
        <th colspan="2">500</th>
    </tr>
</table>
<br>
<br>
<br>
<br>
<div style="padding-bottom: 8px;">
    <span style="font-weight: 900;font-size:12pt;">Driver One Name:</span>
    <span style="font-size: 12pt; padding-left: 10px;">Name</span>
</div>
<div style="padding-bottom: 8px;">
    <span style="font-weight: 900;font-size:12pt;">Driver Two Name:</span>
    <span style="font-size: 12pt; padding-left: 10px;">Name</span>
</div>
<div style="padding-bottom: 8px;">
    <span style="font-weight: 900;font-size:12pt;">HostessName:</span>
    <span style="font-size: 12pt; padding-left: 10px;">Name</span>
</div>
<script type="text/javascript">
    window.onload = function () {
        window.print();
    }
</script>
</body>
</html>
