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
            direction: rtl; /* Added for Urdu alignment */
            height: 10%;
            overflow: scroll;
            margin: 40px 30px 40px 30px;
            font-size: 6pt;
            font-family: 'Noori Nastaleeq', Verdana, Arial, sans-serif; /* Added common Urdu font hint */
        }

        .companyName {
            font-weight: 800;
            font-size: 18pt;
            text-transform: uppercase;
            margin-top: -15px;
            text-align: center;
        }

        table {
            padding: 10px;
            font-size: 10pt !important;
            border-collapse: collapse;
            width: 100% !important;
            text-align: center;
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
            float: left; /* Swapped for RTL */
            width: 250px;
            border: 2px solid black;
            padding: 10px;
            background-color: #f9f9f9;
        }

        .summary-box-left {
            float: right; /* Swapped for RTL */
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
            flex-direction: row-reverse; /* Ensures numbers stay right and text left in RTL flex */
        }

        .summary-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 12pt;
            margin-top: 5px;
            border-top: 2px solid black;
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        .summary-row span {
            font-size: 12px !important;
        }
    </style>
    <script type="text/javascript">
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
    <title>روزانہ سمری رپورٹ</title>
</head>

<body>

    <div style="border: 2px solid black; padding: 15px 3px 5px 3px !important;">
        <div id="info">
            <div class="companyName">
                <span>
                    کلوزنگ سمری 
                    ({{ date('d M Y', strtotime($data['closing_from_date'])) }}
                    تا 
                    {{ date('d M Y', strtotime($data['closing_to_date'])) }})
                </span>
            </div>
        </div>
        <br>

        <table border="2">
            <thead>
                <tr>
                    <th>سیریل نمبر</th>
                    <th>بس نمبر</th>
                    <th>روٹ</th>
                    <th>سیل (فروخت)</th>
                    <th>اخراجات</th>
                    <th>نیٹ سیل</th>
                    <th>بینک وصولی</th>
                    {{-- Dynamic Headers --}}
                    @foreach ($data['dynamicTypes'] as $type)
                    <th>{{ $type }}</th>
                    @endforeach
                    <th>نیٹ کیش</th>
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
                        <td>{{ number_format($item['total_received_bank']) }}</td>
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
                    <td colspan="2" class="text-center">کل ٹوٹل</td>
                    <td></td>
                    <td>{{ number_format($data['merges']->sum('sale')) }}</td>
                    <td>{{ number_format($data['merges']->sum('expense')) }}</td>
                    <td>{{ number_format($data['merges']->sum('net_sale')) }}</td>
                    <td>{{ number_format($data['merges']->sum('total_received_bank')) }}</td>
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
                    کل ادھار اخراجات (Credit)
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

                <div class="summary-row"
                    style="border-top: 2px solid black; font-weight: bold; margin-top: 5px; background-color: #f2f2f2;">
                    <span>ٹوٹل ادھار:</span>
                    <span>{{ number_format($data['expenses']->sum('amount')) }}</span>
                </div>
            </div>

            <div class="summary-box">
                <div style="text-align: center; font-weight: bold; text-decoration: underline; margin-bottom: 10px;">
                    گرینڈ سمری
                </div>

                <div class="summary-row">
                    <span>ٹوٹل گراس سیل:</span>
                    <span>{{ number_format($data['merges']->sum('sale')) }}</span>
                </div>

                <div class="summary-row">
                    <span>ٹوٹل اخراجات:</span>
                    <span style="color: red;">- {{ number_format($data['merges']->sum('expense')) }}</span>
                </div>

                <div class="summary-row">
                    <span>نیٹ سیل:</span>
                    <span>{{ number_format($data['merges']->sum('net_sale')) }}</span>
                </div>

                <div class="summary-row">
                    <span>جنرل اخراجات:</span>
                    <span style="color: red;">- {{ number_format($data['totalCounterExpense']) }}</span>
                </div>

                @foreach ($data['dynamicTypes'] as $type)
                    <div class="summary-row">
                        <span>ٹوٹل {{ $type }}:</span>
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
                    <span>ٹوٹل ادھار:</span> 
                    <span>{{ number_format($data['expenses']->sum('amount')) }}</span>
                </div>

                <div class="summary-row" style="background-color: #eee;">
                    <span>نیٹ کیش:</span>
                    <span>{{ number_format($data['merges']->sum('net_cash') - ($data['totalCounterExpense'] + $data['totalReceivedBank'])) }}</span>
                </div>

                <div class="summary-row" style="background-color: #eee;">
                    <span>کمیشن (KT):</span>
                    <span>{{ number_format($data['totalKtCommission']) }}</span>
                </div>

                <div class="summary-row" style="background-color: #eee;">
                    <span>دیگر کمیشن:</span>
                    <span> {{ number_format($data['totalCounterIncome']) }}</span>
                </div>

                <div class="summary-row" style="background-color: #eee;">
                    <span>نیٹ واجب الادا (Payable):</span>
                    @php
                        $expenses = $data['expenses']->sum('amount');
                        $mergesMinusCounter =
                            $data['merges']->sum('net_cash') +
                            $data['totalCounterIncome'] -
                            $data['totalCounterExpense'] -
                            $data['totalReceivedBank'];

                        $total = $mergesMinusCounter >= 0
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
