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
            margin-bottom: 20px;
        }
    </style>

    <title> Daily Summary Report</title>
</head>

<body>
<div >
    
    <div id="info">
        <div class="companyName"><span>Advance Sale Report</span></div>
    </div>
    <br>

    <table border="2">
        <tr>
            <th>Date</th>
            <th>Bus No</th>
            <th>Bus Class</th>
            <th>No of Seat</th>
            <th>Terminal Name</th>
            <th>User Name</th>
            <th>Sale Amount</th>
            <th>Elt Amount</th>
        </tr>
        @foreach($record as $data)
        <tr>
            <td>{{$data['date']}}<br>{{$data['time']}}</td>
            <td>{{$data['bus_number']}}</td>
            <td>{{$data['bus_class']}}</td>
            <td>{{$data['seats']}}</td>
            <td>{{$data['terminal']}}</td>
            <td>{{$data['user']}}</td>
            <td>{{$data['sales']}}</td>
            <td>{{$data['elt']}}</td>
        </tr>
        @endforeach
        <!-- Total Row -->
        <tr>
            <th colspan="3"></th>
            <th>{{ array_sum(array_column($record, 'seats'))}}</th>
            <th></th>
            <th></th>
            <th>{{ array_sum(array_column($record, 'sales'))}}</th>
            <th>{{ array_sum(array_column($record, 'elt'))}}</th>
        </tr>
    </table>
    
    <div id="info">
        <div class="companyName"><span>Ticket Refund</span></div>
    </div>
    <br>

    <table border="2">
        <tr>
            <th>TICKET ID</th>
            <th>TERMINAL</th>
            <th>BUS NO</th>
            <th>SEAT NO</th>
            <th>REFUND AMOUNT</th>
            <th>CANCELATION CHARGES</th>
            <th>BUS TIMING</th>
            <th>REFUND BY</th>
            <th>CANCELATION DATE</th>
        </tr>
        @foreach($refund as $ref)
        <tr>
            <td>{{ $ref['id'] }}</td>
            <td>{{ $ref['terminal_name'] }}</td>
            <td>{{ $ref['bus_NO'] }}</td>
            <td>{{ $ref['seat_no'] }}</td>
            <td>{{ $ref['amount_refund'] }}</td>
            <td>{{ $ref['cancelation_charges'] }}</td>
            <td>{{ $ref['bus_time'] }}</td>
            <td>{{ $ref['refund_by'] }}</td>
            <td>{{ $ref['cancel_date'] }}</td>
        </tr>
        @endforeach
        <!-- Total Row -->
        <tr>
            <th colspan="4"></th>
            <th>{{$refund->sum("amount_refund")}}</th>
            <th>{{$refund->sum("cancelation_charges")}}</th>
            <th></th>
            <th></th>
            <th></th>
        </tr>
    </table>

    <div id="info">
        <div class="companyName"><span>Counter Expense</span></div>
    </div>
    <br>

    <table border="2">
        <tr>
            <th>Terminal Name</th>
            <th>Amount</th>
            <th>Narration</th>
            <th> Added By</th>
        </tr>
        @foreach($counterExpenses as $expense)
        <tr>
            <td>{{ $expense['terminal']['name'] }}</td>
            <td>{{ $expense['amount'] }}</td>
            <td>{{ $expense['narration'] }}</td>
            <td>{{ $expense['added_by_data']['name']??'N/A' }}</td>
        </tr>
        @endforeach
        <!-- Total Row -->
        <tr>
            <th></th>
            <th>{{$counterExpenses->sum("amount")}}</th>
            <th></th>
            <th></th>
        </tr>
    </table>
    
    <div id="info">
        <div class="companyName"><span>Cash Detail</span></div>
    </div>
    <br>

    <table border="2">
        <tr>
            <th style="width: 75% !important;">CASH ON COUNTER</th>
            <td style="width: 25% !important;">
            {{ array_sum(array_column($record, 'sales'))}}
            </td>
        </tr>
        <tr>
            <th style="width: 75% !important;">TOTAL ELT</th>
            <td style="width: 25% !important;">
            {{ array_sum(array_column($record, 'elt'))}}
            </td>
        </tr>
        <tr>
            <th style="width: 75% !important;">TOTAL REFUND</th>
            <td style="width: 25% !important;">
            {{$refund->sum("amount_refund")}}
            </td>
        </tr>
        <tr>
            <th style="width: 75% !important;">TOTAL CANCELLATION CHARGES</th>
            <td style="width: 25% !important;">
            {{$refund->sum("cancelation_charges")}}
            </td>
        </tr>
        <tr>
            <th style="width: 75% !important;">Total Counter Expenses</th>
            <td style="width: 25% !important;">
            {{$counterExpenses->sum("amount")}}
            </td>
        </tr>
    </table>


</div>
</body>
</html>
