@php($visibleColumnsLookup = array_fill_keys($visibleColumns ?? [], true))

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
            @if(!empty($visibleColumnsLookup['date']))
                <th>Date</th>
            @endif
            @if(!empty($visibleColumnsLookup['bus_number']))
                <th>Bus No</th>
            @endif
            @if(!empty($visibleColumnsLookup['bus_class']))
                <th>Bus Class</th>
            @endif
            @if(!empty($visibleColumnsLookup['seats']))
                <th>No of Seat</th>
            @endif
            @if(!empty($visibleColumnsLookup['terminal']))
                <th>Terminal Name</th>
            @endif
            @if(!empty($visibleColumnsLookup['user']))
                <th>User Name</th>
            @endif
            @if(!empty($visibleColumnsLookup['invoice_id']))
                <th>Invoice</th>
            @endif
            @if(!empty($visibleColumnsLookup['transaction_id']))
                <th>Transaction #</th>
            @endif
            @if(!empty($visibleColumnsLookup['passenger_name']))
                <th>Passenger Name</th>
            @endif
            @if(!empty($visibleColumnsLookup['passenger_contact']))
                <th>Cell No</th>
            @endif
            @if(!empty($visibleColumnsLookup['passenger_cnic']))
                <th>CNIC No</th>
            @endif
            @if(!empty($visibleColumnsLookup['sales']))
                <th>Sale Amount</th>
            @endif
            @if(!empty($visibleColumnsLookup['elt']))
                <th>Elt Amount</th>
            @endif
        </tr>
        @foreach($record as $data)
        <tr>
            @if(!empty($visibleColumnsLookup['date']))
                <td>{{$data['date']}}<br>{{$data['time']}}</td>
            @endif
            @if(!empty($visibleColumnsLookup['bus_number']))
                <td>{{$data['bus_number']}}</td>
            @endif
            @if(!empty($visibleColumnsLookup['bus_class']))
                <td>{{$data['bus_class']}}</td>
            @endif
            @if(!empty($visibleColumnsLookup['seats']))
                <td>{{$data['seats']}}</td>
            @endif
            @if(!empty($visibleColumnsLookup['terminal']))
                <td>{{$data['terminal']}}</td>
            @endif
            @if(!empty($visibleColumnsLookup['user']))
                <td>{{$data['user']}}</td>
            @endif
            @if(!empty($visibleColumnsLookup['invoice_id']))
                <td>
                @if(!empty($data['invoice_id']))
                    {!! implode('<br>', $data['invoice_id']) !!}
                @else
                    N/A
                @endif
                </td>
            @endif
            @if(!empty($visibleColumnsLookup['transaction_id']))
                <td>
                @if(!empty($data['transaction_id']))
                    {!! implode('<br>', $data['transaction_id']) !!}
                @else
                    N/A
                @endif
                </td>
            @endif
            @if(!empty($visibleColumnsLookup['passenger_name']))
                <td>
                @if(!empty($data['passenger_name']))
                    {!! implode('<br>', $data['passenger_name']) !!}
                @else
                    N/A
                @endif
                </td>
            @endif
            @if(!empty($visibleColumnsLookup['passenger_contact']))
                <td>
                @if(!empty($data['passenger_contact']))
                    {!! implode('<br>', $data['passenger_contact']) !!}
                @else
                    N/A
                @endif
                </td>
            @endif
            @if(!empty($visibleColumnsLookup['passenger_cnic']))
                <td>
                @if(!empty($data['passenger_cnic']))
                    {!! implode('<br>', $data['passenger_cnic']) !!}
                @else
                    N/A
                @endif
                </td>
            @endif
            @if(!empty($visibleColumnsLookup['sales']))
                <td>{{$data['sales']}}</td>
            @endif
            @if(!empty($visibleColumnsLookup['elt']))
                <td>{{$data['elt']}}</td>
            @endif
        </tr>
        @endforeach
        <!-- Total Row -->
        <tr>
            @if(!empty($visibleColumnsLookup['date']))
                <th></th>
            @endif
            @if(!empty($visibleColumnsLookup['bus_number']))
                <th></th>
            @endif
            @if(!empty($visibleColumnsLookup['bus_class']))
                <th></th>
            @endif
            @if(!empty($visibleColumnsLookup['seats']))
                <th>{{ array_sum(array_column($record, 'seats'))}}</th>
            @endif
            @if(!empty($visibleColumnsLookup['terminal']))
                <th></th>
            @endif
            @if(!empty($visibleColumnsLookup['user']))
                <th></th>
            @endif
            @if(!empty($visibleColumnsLookup['invoice_id']))
                <th></th>
            @endif
            @if(!empty($visibleColumnsLookup['transaction_id']))
                <th></th>
            @endif
            @if(!empty($visibleColumnsLookup['passenger_name']))
                <th></th>
            @endif
            @if(!empty($visibleColumnsLookup['passenger_contact']))
                <th></th>
            @endif
            @if(!empty($visibleColumnsLookup['passenger_cnic']))
                <th></th>
            @endif
            @if(!empty($visibleColumnsLookup['sales']))
                <th>{{ array_sum(array_column($record, 'sales'))}}</th>
            @endif
            @if(!empty($visibleColumnsLookup['elt']))
                <th>{{ array_sum(array_column($record, 'elt'))}}</th>
            @endif
        </tr>
    </table>

</div>
</body>
</html>
