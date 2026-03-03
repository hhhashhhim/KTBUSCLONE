<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
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
            font-size: 8pt;
            font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', Arial, sans-serif;
        }

        .companyName {
            font-weight: 800;
            font-size: 18pt;
            margin-top: -15px;
            text-align: center;
        }

        table {
            padding: 10px;
            font-size: 9pt !important;
            border-collapse: collapse;
            width: 100% !important;
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

        .summary-box,
        .summary-box-left {
            width: 250px;
            border: 2px solid black;
            padding: 10px;
            background-color: #f9f9f9;
        }

        .summary-box {
            float: right;
        }

        .summary-box-left {
            float: left;
            background-color: #ffffff;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            font-size: 10pt;
            border-bottom: 1px dotted #ccc;
        }

        .summary-row:last-child {
            border-bottom: none;
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
    <title>روزانہ خلاصہ رپورٹ</title>
</head>

<body>

    <div style="border: 2px solid black; padding: 15px 3px 5px 3px !important;">
        <div class="companyName">
            اختتامی خلاصہ برائے {{ date('d-M-Y', strtotime($data['closing_date'])) }}
        </div>

        <br>

        <table>
            <thead>
                <tr>
                    <th>نمبر شمار</th>
                    <th>بس نمبر</th>
                    <th>روٹ</th>
                    <th>کل فروخت</th>
                    <th>اخراجات</th>
                    <th>خالص فروخت</th>
                    <th>موصول شدہ بینک</th>

                    @foreach ($data['dynamicTypes'] as $type)
                        <th>{{ $type }}</th>
                    @endforeach

                    <th>خالص نقد</th>
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
                    <td colspan="2" class="text-center">کل</td>
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

            <!-- Left Box -->
            <div class="summary-box-left">
                <div style="text-align:center;font-weight:bold;text-decoration:underline;margin-bottom:10px;">
                    کل ادھار اخراجات
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

                <div class="summary-row" style="border-top:2px solid black;font-weight:bold;">
                    <span>کل ادھار:</span>
                    <span>{{ number_format($data['expenses']->sum('amount')) }}</span>
                </div>
            </div>

            <!-- Right Box -->
            <div class="summary-box">
                <div style="text-align:center;font-weight:bold;text-decoration:underline;margin-bottom:10px;">
                    مجموعی خلاصہ
                </div>

                <div class="summary-row">
                    <span>کل مجموعی فروخت:</span>
                    <span>{{ number_format($data['merges']->sum('sale')) }}</span>
                </div>

                <div class="summary-row">
                    <span>کل اخراجات:</span>
                    <span style="color:red;">- {{ number_format($data['merges']->sum('expense')) }}</span>
                </div>

                <div class="summary-row">
                    <span>خالص فروخت:</span>
                    <span>{{ number_format($data['merges']->sum('net_sale')) }}</span>
                </div>

                <div class="summary-row">
                    <span>عمومی اخراجات:</span>
                    <span style="color:red;">- {{ number_format($data['totalCounterExpense']) }}</span>
                </div>

                @foreach ($data['dynamicTypes'] as $type)
                    <div class="summary-row">
                        <span>کل {{ $type }}:</span>
                        <span style="color:red;">
                            - {{ number_format($data['merges']->sum(fn($m) => $m['types'][$type] ?? 0)) }}
                        </span>
                    </div>
                @endforeach

                <div class="summary-row">
                    <span>بینک نقد:</span>
                    <span style="color:red;">- {{ number_format($data['totalReceivedBank']) }}</span>
                </div>

                <div class="summary-row">
                    <span>خالص نقد:</span>
                    <span>
                        {{ number_format($data['merges']->sum('net_cash') - ($data['totalCounterExpense'] + $data['totalReceivedBank'])) }}
                    </span>
                </div>

                <div class="summary-row">
                    <span>کے ٹی کمیشن:</span>
                    <span>{{ number_format($data['totalKtCommission']) }}</span>
                </div>

                <div class="summary-row">
                    <span>دیگر کمیشن:</span>
                    <span>{{ number_format($data['totalCounterIncome']) }}</span>
                </div>

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

                <div class="summary-row" style="border-top:2px solid black;font-weight:bold;">
                    <span>قابل ادا رقم:</span>
                    <span>{{ number_format($total + $data['totalKtCommission']) }}</span>
                </div>

            </div>
        </div>

    </div>

</body>

</html>