<!DOCTYPE html>
<html>
<head>
    {{--    <script src="{{ asset('assets/js/app.min.js') }}"></script>--}}
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
            padding: 10px;
            font-size: 10pt !important;
            border-collapse: collapse;
            width: 100% !important;
        }

        #table2 {
            border: 1px solid black;
            padding: 10px;
            margin-top: 5px !important;
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
    <title> Print Passenger List </title>
</head>
<body>
<div id="info">
    <div class="companyName"><span>Kainat Travels</span></div>
    <div class="companyAddress">
        <div><span><b>UAN(24/7) : </b>{{formatUAN($format->uan)}}</span></div>
    </div>
</div>
<table border="2" id="table1">
    <tr>
        <th class="centerTH">Schedule:</th>
        <th class="fontWightTh">{{$format->scheduleName}}</th>
        <th class="centerTH">Date& Time</th>
        <th class="fontWightTh">{{$format->actualDeparture}}</th>
        <th class="centerTH">Bus No:</th>
        <th class="fontWightTh" style="width: 15% !important;">{{$format->busNo}}</th>
    </tr>
    <tr>
        <th colspan="1" class="centerTH">Driver Info</th>
        @if($format->driverInfo)
            @if($format->driverInfo->count() > 0)
                <th colspan="2" class="fontWightTh" style="text-align: start; padding-left: 10px">

                    @foreach($format->driverInfo as $key => $value)

                        <li>{{$value->name}} ({{formatContact($value->contact)}})<br></li>

                    @endforeach

                </th>
            @else
                <th colspan="2" class="fontWightTh" style="text-align: start; padding-left: 10px">
                    N/A
                </th>
            @endif
        @endif
        <th colspan="1" class="centerTH">Host Info</th>
        @if($format->hostInfo)
            @if($format->hostInfo->count() > 0)
                <th colspan="2" class="fontWightTh" style="text-align: start; padding-left: 10px">

                    @foreach($format->hostInfo as $key => $value)

                        <li>{{$value->name}} ({{formatContact($value->contact)}})<br></li>

                    @endforeach

                </th>
            @else
                <th colspan="2" class="fontWightTh" style="text-align: start; padding-left: 10px">
                    N/A
                </th>
            @endif
        @endif
    </tr>
</table>
{{--Table for passenger list--}}
<table border="2" id="table2">
    <tr>
        <th style="width: 5% !important;">SR #</th>
        <th>Seat #</th>
        <th>Passenger Name</th>
        <th>CNIC</th>
        <th>Phone Number</th>
        <th>Terminal Name</th>
        <th>Departure City Name</th>
        <th>Destination City Name</th>
    </tr>
    @if($data)
        @foreach($data as $key => $item)
            {{--            {{dd($item)}}--}}
            <tr>
                <td>{{$key + 1}}</td>
                <td>{{ $item->seat_no }}</td>
                <td>{{ $item->customer->name }}</td>
                <td>{{ formatCNIC($item->customer->cnic) }}</td>
                <td>{{formatContact($item->customer->contact)}}</td>
                <td>{{$item->terminal->city->name}} - {{$item->terminal->name}}</td>
                <td>{{ $item->departure_city->name}}</td>
                <td>{{ $item->destination_city->name }}</td>
            </tr>
        @endforeach
    @endif

</table>

{{--No of Passenger By Terminal Name--}}
{{--{{dd(\Illuminate\Support\Facades\Auth::user())}}--}}

<div>
    <div class="countPassenger">
        <h1><span style="font-size: 20px;font-weight: 900;padding-right: 7px;">&#10233;</span>No of Passenger By
            Terminal Name</h1>
    </div>

    <table border="2" id="table3">
        <tr>
            <th>Terminal Name</th>
            <th>No of Passengers</th>
        </tr>
        @if($terminalData)
            @foreach ($terminalData as $single)
                <tr>
                    <td>{{$single->terminal->city->name}} - {{$single->terminal->name}}</td>
                    <td>{{ $single->terminalPassengerCount }}</td>
                </tr>
            @endforeach
        @endif
    </table>
</div>
{{--No of Passenger By Departure City--}}
<div>
    <div class="countPassenger">
        <h1><span style="font-size: 20px;font-weight: 900;padding-right: 7px;">&#10233;</span>No of Passenger By
            Departure City</h1>
    </div>

    <table border="2" id="table3">
        <tr>
            <th>Departure City</th>
            <th>No of Passengers</th>
        </tr>
        @if($departureData)
            @foreach($departureData as $item)
                <tr>
                    <td>{{$item->departure_city->name}}</td>
                    <td>{{$item->departurePassengerCount}}</td>
                </tr>
            @endforeach
        @endif
    </table>
</div>
{{--No of Passenger By Destination City--}}
<div>
    <div class="countPassenger">
        <h1><span style="font-size: 20px;font-weight: 900;padding-right: 7px;">&#10233;</span>No of Passenger By
            Destination City</h1>
    </div>

    <table border="2" id="table3">
        <tr>
            <th>Destination Cities</th>
            <th>No of Passengers</th>
        </tr>
        @if($destinationData)
            @foreach($destinationData as $value)
                <tr>
                    <td>{{$value->destination_city->name}}</td>
                    <td>{{$value->destinationPassengerCount}}</td>
                </tr>
            @endforeach
        @endif
    </table>
</div>
<script type="text/javascript">
    window.onload = function () {
        // window.print();
    }
</script>
</body>
</html>
