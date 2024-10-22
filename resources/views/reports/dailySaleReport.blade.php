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

    <title> Daily Summary Report</title>
</head>

<body>
<div style="border: 2px solid black; padding: 15px 3px 5px 3px !important; width: 100% !important; height: auto !important; ">
    <div id="info">
        <div class="companyName"><span>Kainat Travels</span></div>
    </div>
    <br>

    <table style="border: none;">
        <tr>
            <th class="centerTH">Date</th>
            <th class="fontWightTh">{{now()->subDays(1)->format("d-M-Y")}}</th>
            <th class="centerTH">Bus No</th>
            <th class="fontWightTh">{{$singleData->bus_number}}</th>
            <th class="centerTH">Route</th>
            <th class="fontWightTh">{{$singleData->city_one . ' - ' . $singleData->city_two}}</th>
        </tr>
    </table>
    <br>
    <div>
        <!-- City 1 -->
        <div style="width:50%;float: left; margin-bottom: 10px">
            <table border="2" style="text-align: center;">
                <tr style="background-color: black; color: white;">
                    <th colspan="5">{{$singleData->city_one}}</th>
                </tr>
                <tr>
                    <th>Sr No</th>
                    <th>Terminal Name</th>
                    <th>Passenger Count</th>
                    <th>Amount</th>
                    <th>Elt</th>
                </tr>
                @php
                    $startTotalPass = 0;
                    $startTotalAmount = 0;
                    $startTotalElt = 0;
                @endphp
                @foreach($data->schedule_start as $item)
                    <tr>
                        <td>{{$loop->iteration}}</td>
                        <td>{{$item[0]->terminal->name}}</td>
                        <td>{{$item->count()}}</td>
                        @php
                            $startTotalPass += $item->count();
                        @endphp
                        <td>
                            @if($item[0]->commission)
                                @if($item[0]->commission->flat_commission == 0)
                                    @php $startCommission = (($item->sum("seat_fare") - ($item->sum("discount")))/100)*$item[0]->commission->percentage_commission @endphp
                                @else
                                    @php $startCommission = $item->count() * $item[0]->commission->flat_commission @endphp
                                @endif
                                <!-- adjustment commission -->
                                @php 
                                    $startAdjustCommission = (($item->sum("seat_fare") - $item->sum("discount"))/100)*$item[0]->commission->adjustment_commission;
                                    $startFixCommission = $item[0]->commission->fix_commission 
                                @endphp
                                <!-- fix commission -->
                            @else
                                <!-- adjustment commission -->
                                @php 
                                    $startCommission = 0 ;
                                    $startAdjustCommission = 0;
                                    $startFixCommission = 0;
                                @endphp
                            @endif

                            <!-- for elt -->
                            @php $startElt = 0 @endphp
                            @foreach($item as $ticket)
                                @if($ticket->elt)
                                    @php $startElt += $ticket->elt->elt_price; @endphp
                                @endif
                            @endforeach
                            
                            {{($item->sum('seat_fare') - $item->sum('discount')) - $startCommission - $startAdjustCommission - $startFixCommission }}
                        
                        </td>
                        @php
                            $startTotalAmount += ($item->sum('seat_fare') - $item->sum('discount')) - $startCommission - $startAdjustCommission - $startFixCommission;
                        @endphp
                        <td>{{ $startElt }}</td>
                        @php $startTotalElt += $startElt @endphp
                    </tr>
                @endforeach
            </table>
            <table border="2" style="text-align: center;">
                <tr>
                    <td style="width: 44%">Total</td>
                    <td style="width: 34%">{{$startTotalPass}}</td>
                    <td style="width: 16%; background-color: yellow;">{{$startTotalAmount}}</td>
                    <td style="">{{$startTotalElt}}</td>
                </tr>
            </table>

        </div>
        <!-- City 2 -->
        <div style="width:50%;float: left; margin-bottom: 10px">
            <table border="2" style="text-align: center;border-left: none;">
                <tr style="background-color: black; color: white;">
                    <th colspan="5"
                        style="border-left: none !important; border-right: none !important;">{{$singleData->city_two}}</th>
                </tr>
                <tr>
                    <th style="border-left: none !important;">Sr No</th>
                    <th style="border:1px solid rgb(80, 79, 79) !important">Terminal Name</th>
                    <th style="border:1px solid rgb(80, 79, 79) !important">Passenger Count</th>
                    <th style="border-right: none !important;">Amount</th>
                    <th style="border-right: none !important;">Elt</th>
                </tr>
                @php
                    $returnTotalPass = 0;
                    $returnTotalAmount = 0;
                    $returnTotalElt = 0;
                @endphp
                @foreach($data->schedule_return as $item)
                    <tr>
                        <td>{{$loop->iteration}}</td>
                        <td>{{$item[0]->terminal->name}}</td>
                        <td>{{$item->count()}}</td>
                        @php
                            $returnTotalPass += $item->count();
                        @endphp
                        <td>
                            @if($item[0]->commission)
                                @if($item[0]->commission->flat_commission == 0)
                                    @php $returnCommission = (($item->sum("seat_fare") - ($item->sum("discount")))/100)*$item[0]->commission->percentage_commission @endphp
                                @else
                                    @php $returnCommission = $item->count() * $item[0]->commission->flat_commission @endphp
                                @endif
                                <!-- adjustment commission -->
                                @php 
                                    $returnAdjustCommission = (($item->sum("seat_fare") - $item->sum("discount"))/100)*$item[0]->commission->adjustment_commission;
                                    $returnFixCommission = $item[0]->commission->fix_commission 
                                @endphp
                                <!-- fix commission -->
                            @else
                                <!-- adjustment commission -->
                                @php 
                                    $returnCommission = 0 ;
                                    $returnAdjustCommission = 0;
                                    $returnFixCommission = 0;
                                @endphp
                            @endif

                            <!-- for elt -->
                            @php $returnElt = 0 @endphp
                            @foreach($item as $ticket)
                                @if($ticket->elt)
                                    @php $returnElt += $ticket->elt->elt_price; @endphp
                                @endif
                            @endforeach
                            
                            {{($item->sum('seat_fare') - $item->sum('discount')) - $returnCommission - $returnAdjustCommission - $returnFixCommission }}
                        </td>
                        @php
                            $returnTotalAmount += ($item->sum('seat_fare') - $item->sum('discount')) - $returnCommission - $returnAdjustCommission - $returnFixCommission ;
                        @endphp
                        <td>{{ $returnElt }}</td>
                        @php $returnTotalElt += $returnElt @endphp
                    </tr>
                @endforeach
            </table>
            <table border="2" style="text-align: center;">
                <tr>
                    <td style="width: 53%">Total</td>
                    <td style="width: 28%">{{$returnTotalPass}}</td>
                    <td style="width: 14%; background-color: yellow;">{{$returnTotalAmount}}</td>
                    <td style=""> {{$returnTotalElt}}</td>
                </tr>

            </table>
        </div>
    </div>
    <div style="clear: both;">
        <div>
            <table border="2" style="text-align: center;">
                <tr style="background-color: black; color: white;">
                    <th colspan="3">Expenses</th>
                </tr>
                <tr>
                    <th>Expenses Details</th>
                    <th>Description</th>
                    <th>Amount</th>
                </tr>
                @foreach($data->expense as $item)
                    <tr>
                        <td>{{$item->expense_category->name}}</td>
                        <td>{{$item->description}}</td>
                        <td>{{$item->amount}}</td>
                    </tr>
                @endforeach
            </table>
            <table border="2" style="text-align: center;">
                <tr>
                    <td style="width: 91%">Total</td>
                    <td style="width: 12%; background-color: yellow;">{{$data->expense->sum("amount")}}</td>
                </tr>
            </table>
        </div>
        @php
            $startTotalAmount +=$startTotalElt;
            $returnTotalAmount +=$returnTotalElt;
        @endphp
        <div style="margin-top: 30px; background-color: #bdbdbd;">
            <table border="2" style="text-align: center;">
                <tr>
                    <td style="width: 46%">{{$singleData->city_one}}</td>
                    <td>{{$startTotalPass}}</td>
                    <td style="width: 17.5%">+ {{$startTotalAmount}}</td>
                </tr>
            </table>
            <table border="2" style="text-align: center;">
                <tr>
                    <td style="width: 46%">{{$singleData->city_two}}</td>
                    <td>{{$returnTotalPass}}</td>
                    <td style="width: 17.5%">+ {{$returnTotalAmount}}</td>
                </tr>
            </table>
            <table border="2" style="text-align: center;">
                <tr>
                    <th style="width: 46%">Gross Total</th>
                    <th>{{$startTotalPass + $returnTotalPass}}</th>
                    <th style="width: 17.5%">= {{$startTotalAmount + $returnTotalAmount}}</th>
                </tr>
            </table>
          
            <table border="2" style="text-align: center;">
                <tr>
                    <td style="width: 50%">All Terminal Refund</td>
                    <td style="width: 50%">+ {{array_sum(array_column($refundTerminal, 'amount'))}}</td>
                </tr>
            </table>
            <table border="2" style="text-align: center;">
                <tr>
                    <td style="width: 50%">Expenses</td>
                    <td style="width: 50%">- {{$data->expense->sum('amount')}}</td>
                </tr>
            </table>
            <table border="2" style="text-align: center;">
                <tr>
                    <th style="width: 50%">Net Profit</th>
                    <th style="width: 50%">= {{$startTotalAmount + array_sum(array_column($refundTerminal, 'amount')) + $returnTotalAmount - $data->expense->sum('amount')}}</th>
                </tr>
            </table>
        </div>
    </div>
</div>
</body>
</html>
