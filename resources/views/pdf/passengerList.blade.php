<!DOCTYPE html>
<html>
<head>
    <script src="{{ asset('assets/js/app.min.js') }}"></script>
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
            font-weight: 900;
            font-size: 20pt;
            text-transform: uppercase;
            margin-top: -15px;
            text-align: center;
            font-family: sans-serif, Verdana, Arial;
        }

        #table1 {
            border-bottom: none;
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

        .fontWightTh {
            font-weight: 100 !important;
        }

        .countPassenger {
            margin-top: 18px !important;
        }
    </style>
    <title>Print Passenger List </title>
</head>
<body>

<div id="info">
    <div class="companyName"><span>Kainat Travels</span></div>
    <div class="companyAddress">
        <span>Address</span>
        <div><span><b>UAN(24/7) : </b>Uan</span></div>
    </div>
</div>
<table border="2" id="table1">
    <tr>
        <th class="centerTH">Route:</th>
        <th class="fontWightTh">RouteName</th>
        <th class="centerTH">Date& Time</th>
        <th class="fontWightTh">Date Time</th>
        <th class="centerTH">Bus No:</th>
        <th class="fontWightTh">Class Name</th>
    </tr>
    <tr>
        <th class="centerTH">Driver One Name</th>
        <th class="fontWightTh">Name 1</th>
        <th class="centerTH">Driver two Name</th>
        <th class="fontWightTh">name 2</th>
        <th class="centerTH">Hostess Name</th>
        <th class="fontWightTh">name</th>
    </tr>
    <tr>
        <th class="centerTH">Driver One COntact</th>
        <th class="fontWightTh">Contact</th>
        <th class="centerTH">driver Two Contatct</th>
        <th class="fontWightTh">Contact</th>
        <th></th>
        <th></th>
    </tr>
</table>
<table border="2" id="table2">
    <tr>
        <th style="width: 5% !important;">SR #</th>
        <th>Seat #</th>
        <th>Passenger Name</th>
        <th>CNIC</th>
        <th>Phone Number</th>
        <th>Terminal Name</th>
        <th>Departure City Name</th>
        <th>Departure City Name</th>
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
    </tr>
</table>
{{--No of Passenger By Terminal Name--}}
<div>
    <div class="countPassenger">
        <h1><span style="font-size: 20px;font-weight: 900;padding-right: 10px;">&#10233;</span>No of Passenger By
            Terminal Name</h1>
    </div>

    <table border="2" id="table3">
        <tr>
            <th>Terminal Name</th>
            <th>No of Passengers</th>
        </tr>
        <tr>
            <td>Main</td>
            <td>5</td>
        </tr>
    </table>
</div>
{{--No of Passenger By Departure City--}}
<div>
    <div class="countPassenger">
        <h1><span style="font-size: 20px;font-weight: 900;padding-right: 10px;">&#10233;</span>No of Passenger By
            Departure City</h1>
    </div>

    <table border="2" id="table3">
        <tr>
            <th>Departure City</th>
            <th>No of Passengers</th>
        </tr>
        <tr>
            <td>Faisalabad</td>
            <td>2</td>
        </tr>
        <tr>
            <td>Rawalpindi</td>
            <td>3</td>
        </tr>
    </table>
</div>
{{--No of Passenger By Destination City--}}
<div>
    <div class="countPassenger">
        <h1><span style="font-size: 20px;font-weight: 900;padding-right: 10px;">&#10233;</span>No of Passenger By
            Destination City</h1>
    </div>

    <table border="2" id="table3">
        <tr>
            <th>Destination Cities</th>
            <th>No of Passengers</th>
        </tr>
        <tr>
            <td>Karachi</td>
            <td>5</td>
        </tr>
    </table>
</div>
<script type="text/javascript">
    window.onload = function () {
        window.print();
    }
</script>
</body>
</html>
