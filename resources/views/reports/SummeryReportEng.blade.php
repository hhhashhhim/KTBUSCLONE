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
        <div class="companyName"><span>(City Name) Closing {{ date('d/m/Y') }}</span></div>
    </div>
    <br>

    <table border="2">
        <tr>
            <th>Sr NO</th>
            <th>Bus NO</th>
            <th>MOD</th>
            <th>Income</th>
            <th>commission</th>
            <th>Expenses</th>
            <th>Profit</th>
            @foreach(getDynamicHeaders() as $header)
                <th>{{ $header->name }}</th>
            @endforeach
            @foreach(getTerminals()  as $item)
                <th>{{ $item->name }}</th>
            @endforeach
            <th>Net Cash</th>
        </tr>
        @php
            $totalMOd = 0;
            $totalIncome = 0;
            $totalCommission = 0;
            $totalExpense = 0;
            $totalProfit = 0;
            $totalHeaders = [];
            $totalTerminals = [];
            $totalNetCash = 0;
        @endphp
            <!-- Raw Data -->
        @foreach(getDynamicHeaders() as $singleHeader)
            @php
                array_push( $totalHeaders,0)
            @endphp
        @endforeach
        @foreach(getTerminals() as $singleHeader)
            @php
                array_push( $totalTerminals ,0)
            @endphp
        @endforeach
        @foreach($data as $key => $single)
            @php
                $singleRowNet = 0;
            @endphp
            <tr>
                
                <td>{{$key + 1}}</td>
                <td>{{ getBusName($single->closing[0]->bus_id)  }}</td>

                <td>{{ $single->mod }}</td>
                @php
                    $totalMOd += $single->mod;
                @endphp
                <td>{{$single->total_income}}</td>
                @php
                    $totalIncome += $single->total_income;
                    $commission = 0;
                @endphp
                @if(isset($physical_terminals[$single->id]))
                @foreach($physical_terminals[$single->id] as $schedules)
                @foreach($schedules as $terminal)
                    @php $commission += $terminal->sum("commission_amount") + $terminal[0]->fix_commission; @endphp
                
                @endforeach
                @endforeach
                @endif

                @if(isset($online_terminals[$single->id]))
                @foreach($online_terminals[$single->id] as $terminal)
                    @php $commission += $terminal->sum("commission_amount"); @endphp
                @endforeach
                @endif

                <td>{{ $commission }}</td>
                @php
                $totalCommission += $commission;
                @endphp
                <td>{{ $single->total_expenses }}</td>
                @php
                $totalExpense += $single->total_expenses;
                @endphp
                <td>{{ $single->total_income - $single->total_expenses - $commission}}</td>
                @php
                    $singleRowNet += ($single->total_income - $single->total_expenses - $commission);
                    $totalProfit += ($single->total_income - $single->total_expenses - $commission);
                @endphp
                @foreach(getDynamicHeaders() as $keyHeader => $singleHeader)
                    @if(isset($headers_link[$single->id]))
                        <td> {{ (int)$headers_link[$single->id][$singleHeader->id][0]->value}}</td>
                        @php
                            $totalHeaders[$keyHeader] += (int)$headers_link[$single->id][$singleHeader->id][0]->value;
                        @endphp
                    @else
                        <td> 0 </td>
                        @php
                            $totalHeaders[$keyHeader] += 0;
                        @endphp
                    @endif
                @endforeach
                @foreach(getTerminals() as $keyTerminal => $singleTerminal)
                    @php
                        $online_terminals_income = isset($online_terminals[$single->id][$singleTerminal->id]) ? $online_terminals[$single->id][$singleTerminal->id]->sum('seat_fare') - $online_terminals[$single->id][$singleTerminal->id]->sum('discount') - $online_terminals[$single->id][$singleTerminal->id]->sum('commission_amount') : 0;
                    @endphp
                    <td>
                        {{ $online_terminals_income }}
                    </td>
                    @php
                        $singleRowNet -= $online_terminals_income;
                        $totalTerminals[$keyTerminal] += (int)$online_terminals_income;
                    @endphp
                @endforeach
                <td>{{ $singleRowNet }}</td>
            </tr>
            @php
                $totalNetCash += $singleRowNet;
            @endphp
        @endforeach
        <!-- Total Row -->
        <tr>
            <th></th>
            <th></th>
            <th>{{ $totalMOd }}</th>
            <th>{{ $totalIncome }}</th>
            <th>{{ $totalCommission }}</th>
            <th>{{ $totalExpense }}</th>
            <th>{{ $totalProfit }}</th>
            @foreach($totalHeaders as  $j)
                <th>{{ $j  }}</th>
            @endforeach
            @foreach($totalTerminals as $k)
                <th> {{ $k }}</th>
            @endforeach
            <th> {{ $totalNetCash }}</th>
        </tr>
    </table>
</div>
</body>
</html>
