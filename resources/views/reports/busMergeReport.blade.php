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
        .text-center
        {
            text-align: center;
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
            <th>Departure Date</th>
            <th>Return Date</th>
            <th>Closing Date</th>
            <th>Sale</th>
            <th>Expense</th>
            <th>Net Sale</th>
        </tr>
        
        @foreach($data['merges'] as $key => $merge)
        <tr>
            <td>{{$key + 1}}</td>
            <td>{{$merge->bus->bus_number}}</td>
            <td>{{$merge->schedule_departure_date}}</td>
            <td>{{$merge->schedule_return_date}}</td>
            <td>{{$merge->closing_date??"N/A"}}</td>
            <td>{{($merge->seat_fare) + ($merge->elt) + ($merge->refund) - ($merge->discount) - ($merge->commission)}}</td>
            <td>{{intVal($merge->expenses_sum_amount)}}</td>
            <td>{{($merge->seat_fare) + ($merge->elt) + ($merge->refund) - ($merge->discount) - ($merge->commission) - ($merge->expenses_sum_amount)}}</td>
        </tr>
        @endforeach
        <tr>
            <td colspan="5" class="text-center"><b>Total<b></td>
            <td><b>{{($data['merges']->sum('seat_fare')) + ($data['merges']->sum('elt')) + ($data['merges']->sum('refund')) - ($data['merges']->sum('discount')) - ($data['merges']->sum('commission'))}}<b></td>
            <td><b>{{$data['merges']->sum('expenses_sum_amount')}}<b></td>
            <td><b>{{($data['merges']->sum('seat_fare')) + ($data['merges']->sum('elt')) + ($data['merges']->sum('refund')) - ($data['merges']->sum('discount')) - ($data['merges']->sum('commission')) - ($data['merges']->sum('expenses_sum_amount'))}}<b></td>
        </tr>
    </table>
</div>
</body>
</html>
