<!DOCTYPE html>
<html>
<head>
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

        .countPassenger {
            margin-top: 18px !important;
        }
    </style>
    <script src="{{ asset('/assets/js/jquery.min.js') }}"></script>
    <script type="text/javascript">

        $(document).ready(function () {
            window.print();
        });
    </script>
    <title>Print Bus Invoice</title>
</head>
<body>
<div style="width: 100% !important; height: auto !important;">
    <div id="info">
        <div class="companyName"><span>{{isset($format->name) ? $format->name : "Kainat Travels"}}</span></div>
        <div class="companyAddress">
            <span style="padding-bottom: 10px !important;text-transform: capitalize">{{ $infoData->current_terminal }} Terminal</span>
            <div><span><b>UAN(24/7) : </b>{{isset($format->uan) ? $format->uan : "03-111-777-333"}}
        </span></div>
        </div>
    </div>
    <br>
    <hr>
    <br>
    <table border="2" id="table1" style="text-transform: capitalize;">
        <tr>
            <th class="centerTH">Route:</th>
            <th>{{ $infoData->route }}</th>
            <th class="centerTH">Date& Time</th>
            <th>{{ date("m/d/Y h:i:s A",strtotime($infoData->departure_date.' '.$infoData->departure_time)) }}</th>
            <th class="centerTH">Bus No:</th>
            <th>{{$infoData->bus_data ? $infoData->bus_data->bus->bus_number : 'Bus Not Alloted Yet'}}</th>
        </tr>
    </table>
    <br>
    <hr>
    <br>
    <table border="2" id="table2" style="text-transform: capitalize;">
        <tr>
            <th>SR #</th>
            <th>Name</th>
            <th>Total Seat</th>
            <th>Destination</th>
            <th style="min-width: 150px !important;">Seat #</th>
            <th>Sale</th>
            <th>Discount</th>
            <th>Commission</th>
            <th>KT Commission</th>
            <th>ELT Price</th>
            <th>Net Sale</th>
        </tr>
        @php
            $totalSeat = 0;
            $totalSale = 0;
            $totalDiscount = 0;
            $totalElt = 0;
            $totalCommission = 0;
            $totalFixCommission = 0;
            $totalAdjustCommission = 0;
            $count = 1;
        @endphp
        @foreach($mainData as $key => $terminal)
            @foreach($terminal as $destination)
                <tr>
                    <td>{{ $count++ }}</td>
                    <td>{{ $destination[0] && $destination[0]->terminal ? $destination[0]->terminal->name : 'Terminal Not Alloted Yet' }}</td>
                    <td>{{ $destination->count() }}</td>
                    @php
                        $totalSeat += $destination->count()
                    @endphp
                    <td>{{ $destination[0]->destination_city->name }}</td>
                    <td>{{ $destination->pluck('seat_no')->implode(", ") }}</td>
                    <td>{{ $destination->sum("seat_fare") }}</td>
                    @php
                        $totalSale += $destination->sum("seat_fare")
                    @endphp
                    <td>{{ $destination->sum("discount") }}</td>
                    @php
                        $totalDiscount += ($destination->sum("discount"))
                    @endphp
                    <td>
                        @if($destination[0]->commission)
                            @if($destination[0]->commission->flat_commission == 0)
                                {{ $commission = (($destination->sum("seat_fare") - ($destination->sum("discount")))/100)*$destination[0]->commission->percentage_commission }}
                            @else
                                {{ $commission = $destination->count() * $destination[0]->commission->flat_commission }}
                            @endif
                        @else
                            {{ $commission = 0 }}
                        @endif
                        @php
                            $totalCommission += $commission;
                        @endphp
                    </td>
                    <td>
                        @if($destination[0]->commission)
                            {{ $adjustCommission = (($destination->sum("seat_fare") - $destination->sum("discount"))/100)*$destination[0]->commission->adjustment_commission }}
                        @else
                            {{ $adjustCommission = 0 }}
                        @endif
                        @php
                            $totalAdjustCommission += $adjustCommission;
                        @endphp
                    </td>
                    <td>{{ $destination->sum("elt_price") }}</td>
                    @php
                        $totalElt += $destination->sum("elt_price")
                    @endphp
                    <td>{{ ((($destination->sum("seat_fare") + $destination->sum("elt_price")) - ($destination->sum("discount"))) - $commission) - $adjustCommission }}</td>
                </tr>
            @endforeach
        @endforeach
        <tr>
            <th colspan="2">Total</th>
            <th colspan="2">{{ $totalSeat }}</th>
            <th></th>
            <th>{{ $totalSale }}</th>
            <th>{{ $totalDiscount }}</th>
            <th>{{ $totalCommission }}</th>
            <th>{{ $totalAdjustCommission }}</th>
            <th>{{ $totalElt }}</th>
            <th>{{ ((($totalSale + $totalElt) - $totalDiscount) - $totalCommission) - $totalAdjustCommission }}</th>
        </tr>
        @foreach($mainData as $terminal)
            @if($terminal->first()[0]->commission && $terminal->first()[0]->commission->fix_commission != 0)
            <tr>
                <th colspan="8">{{ $terminal->first()[0]->terminal->name }} Fix Commission</th>
                <td colspan="3">{{ $fixCommission = $terminal->first()[0]->commission ? intVal($terminal->first()[0]->commission->fix_commission) : 0 }}</td>
            </tr>
            @php
                $totalFixCommission += $fixCommission;
            @endphp
            @endif
        @endforeach
        @php $refundAmount = 0;  @endphp
        @foreach($refundTerminal as $refund)
            @if($refund['amount'] != 0)
            <tr>
                <th colspan="8">{{ $refund['terminal'] }} cancellation charges of seats {{" (" . $refund['seats'] . ")"}}</th>
                <th colspan="3">{{ $refund['amount'] }}</th>
                @php $refundAmount += $refund['amount'] @endphp
            </tr>
            @endif
        @endforeach
        <tr>
            <th colspan="8">Gross Sale</th>
            <th colspan="3">{{ (((($totalSale + $totalElt + $refundAmount) - $totalDiscount) - $totalCommission) - $totalFixCommission) - $totalAdjustCommission }}</th>
        </tr>
    </table>
    <br>
    <br>
    <br>
    <br>
    <div style="padding-bottom: 8px;">
        <span style="font-weight: 900;font-size:12pt;">Drivers Name:</span>
        <span style="font-size: 12pt; padding-left: 10px;">
    @if($infoData->bus_data)
                @foreach($infoData->bus_data->members as $data)
                    @if($data->type == 1)
                        {{ $data->driver_name->name . " (". $data->driver_name->contact .") |" }}
                    @endif
                @endforeach
            @else
                Bus Not Alloted Yet
            @endif
    </span>
    </div>
    <div style="padding-bottom: 8px;">
        <span style="font-weight: 900;font-size:12pt;">Hosts Name:</span>
        <span style="font-size: 12pt; padding-left: 10px;">
    @if($infoData->bus_data)
                @foreach($infoData->bus_data->members as $data)
                    @if($data->type == 2)
                        {{ $data->host_name->name . " (". $data->host_name->contact .") |" }}
                    @endif
                @endforeach
            @else
                Bus Not Alloted Yet
            @endif
    </span>
    </div>
</div>
<script type="text/javascript">
    // window.onload = function () {
    //     window.print();
    // }
</script>
</body>
</html>
