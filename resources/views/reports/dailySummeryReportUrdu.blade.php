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
<div style="border: 0px solid black; padding: 15px 3px 5px 3px !important;">
    <div id="info">
        <div class="companyName"><span>Closing</span></div>
    </div>
    <br>

    <table border="2" dir="rtl" style="text-align: center;">
        <tr>
            <!-- <th>نمبر شمار</th> -->
            <th>بس نمبر</th>
            <th>آمدن</th>
            <th>کمیشن</th>
            <th>خرچہ</th>
            <th>بچت</th>
            @foreach(getTerminals()  as $item)
                <th>{{ $item->urdu_name }}</th>
            @endforeach
            <th>Net Cash</th>
        </tr>
        @php
            $totalMOd = 0;
            $totalIncome = 0;
            $totalCommission = 0;
            $totalExpense = 0;
            $totalProfit = 0;
            $totalTerminals = [];
            $totalNetCash = 0;
        @endphp
            <!-- Raw Data -->
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
                <!-- <td>{{$key + 1}}</td> -->
                <td>{{ getBusName($single->closing[0]->bus_id)  }}</td>
                
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
                <td>{{ $commission }}</td>
                @php
                $totalCommission += $commission;
                @endphp
                <td>{{ $single->total_expenses }}</td>
                
                @php
                    $totalExpense += $single->total_expenses;
                @endphp
                <td dir="ltr">{{ $single->total_income - $single->total_expenses - $commission}}</td>
                @php
                    $singleRowNet += ($single->total_income - $single->total_expenses - $commission);
                    $totalProfit += ($single->total_income - $single->total_expenses - $commission);
                @endphp
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
                <td dir="ltr">{{ $singleRowNet }}</td>
            </tr>
            @php
                $totalNetCash += $singleRowNet;
            @endphp
        @endforeach
        <!-- Total Row -->
        <tr>
            <th></th>
            <th>{{ $totalIncome }}</th>
            <th>{{ $totalCommission }}</th>
            <th>{{ $totalExpense }}</th>
            <th dir="ltr">{{ $totalProfit }}</th>
            @foreach($totalTerminals as $k)
                <th> {{ $k }}</th>
            @endforeach
            <th dir="ltr"> {{ $totalNetCash }}</th>
        </tr>
        
        <tr>
            <th> --- </th>
            <th> --- </th>
        </tr>
        
        <tr>
            <th> بنام / بچت </th>
            <th dir="ltr"> {{ $totalNetCash }}</th>
        </tr>
        
        <tr>
            <th> متفرق خرچہ </th>
            <th dir="ltr"> {{ $office_expense }}</th>
        </tr>
        
        <tr>
            <th> بنام / بچت </th>
            <th dir="ltr"> {{ $totalNetCash + $office_expense }}</th>
        </tr>
    </table>
</div>
</body>
</html>
