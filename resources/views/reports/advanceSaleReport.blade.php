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
    <br><br>

    <table border="0">
        <tr>
            <td>Terminal : {{$filterData->terminal}}</td>
            <td>User : {{$filterData->user}}</td>
            <td>Route : {{count($filterData->route) > 0 ? implode(",",$filterData->route) : "All"}}</td>
            <td>{{$filterData->from}} -- {{$filterData->to}}</td>
        </tr>
    </table>

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
    
    


</div>
</body>
</html>
