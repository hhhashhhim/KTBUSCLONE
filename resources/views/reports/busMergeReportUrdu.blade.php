<!DOCTYPE html>
<html lang="ur" dir="rtl">

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
            direction: rtl;
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
            direction: rtl;
        }

        .text-center {
            text-align: center;
        }

        th,
        td {
            padding: 5px;
            border: 1px solid black;
        }

        .summary-wrapper {
            width: 100%;
            margin-top: 20px;
        }

        .summary-box {
            float: left;
            width: 250px;
            border: 2px solid black;
            padding: 10px;
            background-color: #f9f9f9;
        }

        .summary-box-left {
            float: right;
            width: 250px;
            border: 2px solid black;
            padding: 10px;
            background-color: #ffffff;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            font-size: 11pt;
            border-bottom: 1px dotted #ccc;
        }

        .summary-row span {
            font-size: 12px !important;
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>

    <title>یومیہ خلاصہ رپورٹ</title>
</head>

<body>
    <div style="border: 2px solid black; padding: 15px 3px 5px 3px !important;">
        <div id="info">
            <div class="companyName">
                <span>کلوزنگ کا خلاصہ برائے {{ date('d-M-Y', strtotime($data['closing_date'])) }}</span>
            </div>
        </div>
        <br>

        <table border="2">
            <thead>
                <tr>
                    <th>نمبر شمار</th>
                    <th>بس نمبر</th>
                    <th>راستہ</th>
                    <th>کل سیل</th>
                    <th>اخراجات</th>
                    <th>خالص سیل</th>
                    {{-- Dynamic Headers for Portals --}}
                    @foreach ($data['dynamicTypes'] as $type)
                        <th>{{ $type }}</th>
                    @endforeach
                    <th>خالص نقدی</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data['merges'] as $key => $item)
                    <tr>
                        <td class="text-center">{{ $key + 1 }}</td>
                        <td>{{ $item['bus_no'] }}</td>
                        <td>{{ $item['route'] }}</td>

                        <td>{{ number_format($item['sale']) }}</td>
                        <td>{{ number_format($item['expense']) }}</td>
                        <td>{{ number_format($item['net_sale']) }}</td>
                        {{-- Dynamic Values for Portals --}}
                        @foreach ($data['dynamicTypes'] as $type)
                            <td class="text-center">
                                {{ number_format($item['types'][$type] ?? 0) }}
                            </td>
                        @endforeach
                        <td>{{ number_format($item['net_cash']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="font-weight: bold;">
                    <td colspan="2" class="text-center">کل</td>
                    <td></td>

                    <td>{{ number_format($data['merges']->sum('sale')) }}</td>
                    <td>{{ number_format($data['merges']->sum('expense')) }}</td>
                    <td>{{ number_format($data['merges']->sum('net_sale')) }}</td>
                    {{-- Dynamic Totals for Portals --}}
                    @foreach ($data['dynamicTypes'] as $type)
                        <td>
                            {{ number_format($data['merges']->sum(fn($m) => $m['types'][$type] ?? 0)) }}
                        </td>
                    @endforeach
                    <td>{{ number_format($data['merges']->sum('net_cash')) }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="summary-wrapper clearfix">
            <div class="summary-box-left">
                <div style="text-align: center; font-weight: bold; text-decoration: underline; margin-bottom: 10px;">
                    کل کریڈٹ اخراجات
                </div>

                @php
                    $groupedExpenses = collect($data['expenses'])
                        ->groupBy(fn($e) => $e['expense_category']['name'])
                        ->map(fn($items) => $items->sum('amount'));
                @endphp

                @foreach ($groupedExpenses as $name => $totalAmount)
                    <div class="summary-row">
                        <span>{{ $name }}</span>
                        <span>{{ number_format($totalAmount) }}</span>
                    </div>
                @endforeach

                {{-- Total Row --}}
                <div class="summary-row"
                    style="border-top: 2px solid black; font-weight: bold; margin-top: 5px; background-color: #f2f2f2;">
                    <span>کل کریڈٹ:</span>
                    <span>{{ number_format($data['expenses']->sum('amount')) }}</span>
                </div>

            </div>

            <div class="summary-box">
                <div style="text-align: center; font-weight: bold; text-decoration: underline; margin-bottom: 10px;">
                    مکمل خلاصہ
                </div>

                <div class="summary-row">
                    <span>کل مجموعی سیل:</span>
                    <span>{{ number_format($data['merges']->sum('sale')) }}</span>
                </div>

                <div class="summary-row">
                    <span>کل اخراجات:</span>
                    <span style="color: red;">- {{ number_format($data['merges']->sum('expense')) }}</span>
                </div>

                <div class="summary-row">
                    <span>خالص سیل:</span>
                    <span>{{ number_format($data['merges']->sum('net_sale')) }}</span>
                </div>

                <div class="summary-row">
                    <span>عام اخراجات:</span>
                    <span style="color: red;">- {{ number_format($data['totalCounterExpense']) }}</span>
                </div>

                @foreach ($data['dynamicTypes'] as $type)
                    <div class="summary-row">
                        <span>Total {{ $type }}:</span>
                        <span style="color: red;">-
                            {{ number_format($data['merges']->sum(fn($m) => $m['types'][$type] ?? 0)) }}</span>
                    </div>
                @endforeach

                <div class="summary-row" style="background-color: #eee;">
                    <span>بینک کیش:</span>
                    <span style="color: red;">- {{ number_format($data['totalReceivedBank']) }}</span>
                </div>

                <div class="summary-row"
                    style="border-top: 2px solid black; font-weight: bold; margin-top: 5px; background-color: #f2f2f2;">
                    <span>کل کریڈٹ:</span>
                    <span>{{ number_format($data['expenses']->sum('amount')) }}</span>
                </div>

                <div class="summary-row" style="background-color: #eee;">
                    <span>خالص نقدی:</span>
                    <span>{{ number_format($data['merges']->sum('net_cash') - ($data['totalCounterExpense'] + $data['totalReceivedBank'])) }}</span>
                </div>

                <div class="summary-row" style="background-color: #eee;">
                    <span>کے ٹی کمیشن:</span>
                    <span>{{ number_format($data['totalKtCommission']) }}</span>
                </div>

                <div class="summary-row" style="background-color: #eee;">
                    <span>دیگر کمیشن:</span>
                    <span>{{ number_format($data['totalCounterIncome']) }}</span>
                </div>

                <div class="summary-row"
                    style="border-top: 2px solid black; font-weight: bold; margin-top: 5px; background-color: #f2f2f2;">
                    <span>خالص قابل ادائیگی:</span>
                    @php
                        $expenses = $data['expenses']->sum('amount');
                        $mergesMinusCounter =
                            $data['merges']->sum('net_cash') +
                            $data['totalCounterIncome'] -
                            $data['totalCounterExpense'] -
                            $data['totalReceivedBank'];

                        $total =
                            $mergesMinusCounter >= 0
                                ? $expenses + $mergesMinusCounter
                                : $expenses - abs($mergesMinusCounter);
                    @endphp

                    <span>
                        {{ number_format($total + $data['totalKtCommission']) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
