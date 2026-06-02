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
    </style>
    <script type="text/javascript">
        window.addEventListener('load', function () {
            window.print();
        });
    </script>

    <title> Daily Summary Report</title>
</head>

<body>
<div style="border: 2px solid black; padding: 15px 3px 5px 3px !important; width: 100% !important;">

    <div class="companyName">Kainat Travels</div>
    <br>

    <table style="border: none;">
        <tr>
            <th class="centerTH">Date</th>
            <th class="fontWightTh">{{ $singleData->closing_date ? \Carbon\Carbon::parse($singleData->closing_date)->format('d-m-Y') : '' }}</th>
            <th class="centerTH">Bus No</th>
            <th class="fontWightTh">{{ $singleData->bus->bus_number }}</th>
            <th class="centerTH">Route</th>

<th class="fontWightTh">
    {{ $start?->name ?? 'N/A' }}
    →
    {{ $return?->name ?? 'N/A' }}
</th>
        </tr>
    </table>

    <br>

    <div>
        <!-- ================= START CITY ================= -->
        <div style="width:50%;float:left; margin-bottom:10px">
            <table border="2" style="text-align:center;">
                <tr style="background-color:black;color:white;">
                    <th colspan="5">{{ $singleData->city_one }}</th>
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

                @foreach($startShortages as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->terminal->name ?? 'N/A' }}</td>
                        <td>{{ $item->passenger_count }}</td>

                        @php
                            $startTotalPass += $item->passenger_count;
                        @endphp

                        <td>
                            {{ number_format($item->total_receivable) }}
                        </td>

                        @php
                            $startTotalAmount += ($item->total_receivable);
                        @endphp

                        <td>{{ $item->elt }}</td>

                        @php
                            $startTotalElt += $item->elt;
                        @endphp
                    </tr>
                @endforeach
            </table>

            <table border="2" style="text-align:center;">
                <tr>
                    <td style="width:44%">Total</td>
                    <td style="width:34%">{{ $startTotalPass }}</td>
                    <td style="width:16%; background-color:yellow;">{{ number_format($startTotalAmount) }}</td>
                    <td>{{ $startTotalElt }}</td>
                </tr>
            </table>
        </div>

        <!-- ================= RETURN CITY ================= -->
        <div style="width:50%;float:left; margin-bottom:10px">
            <table border="2" style="text-align:center;border-left:none;">
                <tr style="background-color:black;color:white;">
                    <th colspan="5">{{ $singleData->city_two }}</th>
                </tr>
                <tr>
                    <th>Sr No</th>
                    <th>Terminal Name</th>
                    <th>Passenger Count</th>
                    <th>Amount</th>
                    <th>Elt</th>
                </tr>

                @php
                    $returnTotalPass = 0;
                    $returnTotalAmount = 0;
                    $returnTotalElt = 0;
                @endphp

                @foreach($returnShortages as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->terminal->name ?? 'N/A' }}</td>
                        <td>{{ $item->passenger_count }}</td>

                        @php
                            $returnTotalPass += $item->passenger_count;
                        @endphp

                        <td>
                            {{ number_format($item->total_receivable) }}
                        </td>

                        @php
                            $returnTotalAmount += ($item->total_receivable);
                        @endphp

                        <td>{{ $item->elt }}</td>

                        @php
                            $returnTotalElt += $item->elt;
                        @endphp
                    </tr>
                @endforeach
            </table>

            <table border="2" style="text-align:center;">
                <tr>
                    <td style="width:53%">Total</td>
                    <td style="width:28%">{{ $returnTotalPass }}</td>
                    <td style="width:14%; background-color:yellow;">{{ number_format($returnTotalAmount) }}</td>
                    <td>{{ $returnTotalElt }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div style="clear:both;"></div>

    <!-- ================= EXPENSES ================= -->
    <table border="2" style="text-align:center;">
        <tr style="background-color:black;color:white;">
            <th colspan="3">Expenses</th>
        </tr>
        <tr>
            <th>Expenses Details</th>
            <th>Description</th>
            <th>Amount</th>
        </tr>

        @foreach($expenses as $item)
            <tr>
                <td>{{ $item->expense_category->name ?? 'N/A' }}</td>
                <td>{{ $item->description }}</td>
                <td>{{ number_format($item->amount) }}</td>
            </tr>
        @endforeach
    </table>

    <table border="2" style="text-align:center;">
        <tr>
            <td style="width:91%">Total</td>
            <td style="width:12%; background-color:yellow;">
                {{ number_format($expenses->sum('amount')) }}
            </td>
        </tr>
    </table>

    @php
        $startTotalAmount += $startTotalElt;
        $returnTotalAmount += $returnTotalElt;
    @endphp

    <!-- ================= SUMMARY ================= -->
    <div style="margin-top:30px;background-color:#bdbdbd;">
        <table border="2" style="text-align:center;">
            <tr>
                <td style="width:46%">{{ $singleData->city_one }}</td>
                <td>{{ $startTotalPass }}</td>
                <td style="width:17.5%">+ {{ number_format($startTotalAmount) }}</td>
            </tr>
        </table>

        <table border="2" style="text-align:center;">
            <tr>
                <td style="width:46%">{{ $singleData->city_two }}</td>
                <td>{{ $returnTotalPass }}</td>
                <td style="width:17.5%">+ {{number_format( $returnTotalAmount )}}</td>
            </tr>
        </table>

        <table border="2" style="text-align:center;">
            <tr>
                <th style="width:46%">Gross Total</th>
                <th>{{ $startTotalPass + $returnTotalPass }}</th>
                <th style="width:17.5%">
                    = {{ number_format($startTotalAmount + $returnTotalAmount) }}
                </th>
            </tr>
        </table>

        <table border="2" style="text-align:center;">
            <tr>
                <td style="width:50%">Expenses</td>
                <td style="width:50%">- {{ number_format($expenses->sum('amount')) }}</td>
            </tr>
        </table>
        <table border="2" style="text-align:center;">
            <tr>
                <td style="width:50%">KT Commission</td>
                <td style="width:50%">- {{ number_format($KtCommssion) }}</td>
            </tr>
        </table>
        <table border="2" style="text-align:center;">
            <tr>
                <td style="width:50%">Other Commission</td>
                <td style="width:50%">- {{ number_format($OtherCommssion) }}</td>
            </tr>
        </table>

        <table border="2" style="text-align:center;">
            <tr>
                <th style="width:50%">Net Profit</th>
                <th style="width:50%">
                    = {{ number_format(($startTotalAmount + $returnTotalAmount ) - ($expenses->sum('amount') + $KtCommssion + $OtherCommssion)) }}
                </th>
            </tr>
        </table>
    </div>

</div>
</body>
</html>
