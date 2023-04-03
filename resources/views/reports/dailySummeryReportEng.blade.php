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

    <title> Daily Summary Report</title>
</head>

<body>
<div style="border: 2px solid black; padding: 15px 3px 5px 3px !important;">
    <div id="info">
        {{--        <div class="companyName"><span>(City Name) Closing {{ date('d/m/Y') }}</span></div>--}}
    </div>
    <br>

    <table border="2">
        <tr>
            <th>Sr NO</th>
            <th>Bus NO</th>
            <th>MOD</th>
            <th>Income</th>
            <th>Expenses</th>
            <th>Profit</th>
            <th>Commission</th>
            <th>Hawa Jali</th>
            <th>M Tag</th>
            <th>Paid</th>
            <th>Non Paid</th>
            @foreach(getTerminals()  as $item)
                <th>{{ $item->name }}</th>
            @endforeach
            {{--            <th>Jazz Cash</th>--}}
            {{--            <th>Do Safar</th>--}}
            {{--            <th>Online Web</th>--}}
            {{--            <th>Online Mobile</th>--}}
            {{--            <th>1 Link</th>--}}
            {{--            <th>SASTA Tcket</th>--}}
            {{--            <th>Book Me</th>--}}
            {{--            <th>Book Kro</th>--}}
            <th>Net Cash</th>
        </tr>
        <!-- Raw Data -->
        @foreach($data as $key => $single)
            @php
                $singleRowNet = 0;
            @endphp
            <tr>
                <td>{{$key + 1}}</td>
                <td>{{ getBusName($single->closing[0]->bus_id) }}</td>
                <td>{{ $single->mod }}</td>
                <td>{{$single->total_income}}</td>
                <td>{{ $single->total_expenses }}</td>
                <td>{{ $single->total_income - $single->total_expenses}}</td>
                @php
                    $singleRowNet += ($single->total_income - $single->total_expenses);
                @endphp
                <td>Commission</td>
                <td>Hawa Jali</td>
                <td>M Tag</td>
                <td>Paid</td>
                <td>Non Paid</td>
                @foreach(getTerminals() as $key => $singleTerminal)
                    @php
                    $online_terminals_income = isset($online_terminals[$single->id][$singleTerminal->id]) ? $online_terminals[$single->id][$singleTerminal->id]->sum('seat_fare') - $online_terminals[$single->id][$singleTerminal->id]->sum('discount') : 0;
                    @endphp
                    <td>
                        {{ $online_terminals_income }}
                    </td>
                    @php
                        $singleRowNet -= $online_terminals_income;
                    @endphp

                @endforeach
                <td>{{ $singleRowNet }}</td>
            </tr>
        @endforeach
        <!-- Total Row -->
        <tr>
            <th></th>
            <th></th>
            <th>Total MOD</th>
            <th>Total Income</th>
            <th>Total Expenses</th>
            <th>Total Profit</th>
            <th>Total Commission</th>
            <th>Total Hawa Jali</th>
            <th>Total M Tag</th>
            <th>Total Paid</th>
            <th>Total Non Paid</th>
            @foreach(getTerminals() as $singleTerminal)
                <th> Total</th>
            @endforeach
            <th>Total Net Cash</th>
        </tr>
    </table>
</div>
</body>
</html>
