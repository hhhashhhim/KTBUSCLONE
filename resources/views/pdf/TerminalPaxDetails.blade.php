<!DOCTYPE html>
<html>

<head>
    <script src="{{ asset('assets/js/app.min.js') }}"></script>
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
            font-size: 13pt !important;
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
    <title>Terminal Passenger List </title>
</head>
{{--{{dd($data)}}--}}
<body>
<div id="info">
    <div class="companyName"><span>Kainat Travels</span></div>
    <div class="companyAddress">
        <div style=" padding-bottom: 5px;"><span style="font-weight:600">   {{ $format->address }}</span></div>
        <div style=" padding-bottom: 5px;"><span style="font-weight:600">{{$format->terminal->name}}</span></div>
        <div style="padding-bottom: 5px;"><span><b>UAN(24/7) : </b> {{ formatUAN($format->uan) }}</span></div>
        <div><span><b>Phone # : </b>{{ formatContact($format->phone) }}</span></div>
    </div>
</div>
<br>
<hr>
<br>
<table id="table1">
    <tr>
        <th style="text-align: start;">Route:</th>
        @if ($data)
            <td style="text-align: end;">{{ $data['routeName'] }}</td>
        @else
            <td style="text-align: end;"></td>
        @endif
    </tr>
    <tr>
        <th style="text-align: start;">Schedule Date:</th>
        @if($data)
            <td style="text-align: end;">{{ $data['date'] }}</td>
        @else
            <td style="text-align: end;">N/A</td>
        @endif
    </tr>
    <tr>
        <th style="text-align: start;">Bus No:</th>
        @if ($data)
            <td style="text-align: end;">{{ $data['busNo']->bus_class->name}}</td>
        @else
            <td style="text-align: end;"></td>
        @endif
    </tr>
</table>
<br>
<hr>
<br>
<table border="2" id="table2">
    <tr>
        <th> Sr #</th>
        <th>Seat #</th>
        <th>Name</th>
        <th>CNIC</th>
        <th>Dept City</th>
        <th>Dest City</th>
        <th>Elt Price</th>
        <th>Ticket Amount</th>
        <th>Terminal Name</th>
        <th>Ticket Booked By</th>

    </tr>
    {{--        {{dd($data['record'])}}--}}
    @if(count($data['record']) > 0)
        @foreach ($data['record'] as $key => $item)
            <tr>
                <td>{{ $key +1 }}</td>
                <td>{{ $item->seat_no }}</td>
                <td>{{ ucfirst($item->customer->name) }}</td>
                <td>{{ formatCNIC($item->customer->cnic) }}</td>
                <td>{{ucfirst($item->departure_city->name)}}</td>
                <td>{{ ucfirst($item->destination_city->name) }}</td>
                <td>{{ $item->ticketElt == null ? 0 : $item->ticketElt->elt_price }}</td>
                <td>{{ $item->seat_fare }}</td>
                <td>{{ ucfirst($item->terminal->name) }}</td>
                <td>{{ ucfirst($item->addedBy->name) }}</td>

            </tr>
        @endforeach
    @else
        <tr style="height: 20px">
            <td>N/A</td>
            <td>N/A</td>
            <td>N/A</td>
            <td>N/A</td>
            <td>N/A</td>
            <td>N/A</td>
            <td>N/A</td>
            <td>N/A</td>
            <td>N/A</td>
            <td>N/A</td>

        </tr>

    @endif
    <tr>
        <th colspan="9"> Main Total Online Tickets</th>
        <th>0</th>
    </tr>
    <tr>
        <th colspan="9"> Main Total Online Tickets Amount</th>
        <th>0</th>
    </tr>
    <tr>
        <th colspan="9"> Terminal Gross Sale</th>
        <th>0</th>
    </tr>
    <tr>
        <th colspan="9"> Terminal Discount</th>
        <th>0</th>
    </tr>
    <tr>
        <th colspan="9"> Elt Amount</th>
        <th>0</th>
    </tr>
    <tr>
        <th colspan="9"> Terminal Tickets Commission</th>
        <th>0</th>
    </tr>
    <tr>
        <th colspan="9"> Terminal Fixed Commission</th>
        <th>0</th>
    </tr>
    <tr>
        <th colspan="9"> Terminal Ticket Refund</th>
        <th>0</th>
    </tr>
    <tr>
        <th colspan="9"> Main Net Sale</th>
        <th>0</th>
    </tr>
    <tr>
        <th colspan="9"> Bank Deposit Amount</th>
        <th>0</th>
    </tr>
    <tr>
        <th colspan="9">Cash On Bus</th>
        <th>0</th>
    </tr>
</table>
<br>
<hr>
<br>
<table border="2" id="table3">
    <tr>
        <th>Driver One Name</th>
        @if($data['driverInfo'])
            @if(count($data['driverInfo']) > 0)
                <td colspan="2" class="fontWightTh" style="text-align: start; padding-left: 10px">

                    @foreach($data['driverInfo'] as $key => $value)

                        <li>{{$value->name}} ({{formatContact($value->contact)}})<br></li>

                    @endforeach

                </td>
            @else
                <td colspan="2" class="fontWightTh" style="text-align: start; padding-left: 10px">
                    N/A
                </td>
            @endif
        @endif

        <th>Hostess Name</th>
        @if($data['hostInfo'])
            @if(count($data['hostInfo']) > 0)
                <td colspan="2" class="fontWightTh" style="text-align: start; padding-left: 10px">

                    @foreach($data['hostInfo'] as $key => $value)

                        <li>{{$value->name}} ({{formatContact($value->contact)}})<br></li>

                    @endforeach

                </td>
            @else
                <td colspan="2" class="fontWightTh" style="text-align: start; padding-left: 10px">
                    N/A
                </td>
            @endif
        @endif

    </tr>
</table>
<br>
<br>
<hr>
<br>
<br>
<br>
<br>
<div style="float: right">
    <p style="font-size: medium; font-weight: 600">Terminal Manager Signature <span style="font-weight: normal">..........................................</span>
    </p>
</div>
{{--<script type="text/javascript">--}}
{{--    window.onload = function () {--}}
{{--        window.print();--}}
{{--    }--}}
{{--</script>--}}
</body>

</html>
