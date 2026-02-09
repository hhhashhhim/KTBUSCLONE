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

        <div class="companyName">
            <span>یومیہ کلوزنگ خلاصہ برائے {{ date('d-M-Y', strtotime($data['closing_date'])) }}</span>
        </div>

        <br>

        <!-- ================= MAIN TABLE ================= -->
        <table border="2" dir="rtl" style="text-align: center;">
            <thead>
                <tr>
                    <th>نمبر شمار</th>
                    <th>بس نمبر</th>
                    <th>کل سیل</th>
                    <th>اخراجات</th>
                    <th>خالص سیل</th>

                    @foreach ($data['dynamicTypes'] as $type)
                        <th>{{ $type }}</th>
                    @endforeach

                    <th>خالص کیش</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($data['merges'] as $key => $item)
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td>{{ $item['bus_no'] }}</td>

                        <td>{{ number_format($item['sale']) }}</td>
                        <td>{{ number_format($item['expense']) }}</td>
                        <td>{{ number_format($item['net_sale']) }}</td>

                        @foreach ($data['dynamicTypes'] as $type)
                            <td>
                                {{ number_format($item['types'][$type] ?? 0) }}
                            </td>
                        @endforeach

                        <td>{{ number_format($item['net_cash']) }}</td>
                    </tr>
                @endforeach
            </tbody>

            <tfoot>
                <tr style="font-weight: bold;">
                    <td colspan="2">کل</td>
                    <td>{{ number_format($data['merges']->sum('sale')) }}</td>
                    <td>{{ number_format($data['merges']->sum('expense')) }}</td>
                    <td>{{ number_format($data['merges']->sum('net_sale')) }}</td>

                    @foreach ($data['dynamicTypes'] as $type)
                        <td>
                            {{ number_format($data['merges']->sum(fn($m) => $m['types'][$type] ?? 0)) }}
                        </td>
                    @endforeach

                    <td>{{ number_format($data['merges']->sum('net_cash')) }}</td>
                </tr>
            </tfoot>
        </table>

        <!-- ================= SUMMARY SECTION ================= -->
        <div class="summary-wrapper clearfix">

            <!-- LEFT BOX : CREDIT EXPENSES -->
            <div class="summary-box-left">
                <div style="text-align: center; font-weight: bold; text-decoration: underline; margin-bottom: 10px;">
                    کل کریڈٹ اخراجات
                </div>

                @php
                    $groupedExpenses = collect($data['expenses'])
                        ->groupBy(fn ($e) => $e['expense_category']['name'])
                        ->map(fn ($items) => $items->sum('amount'));
                @endphp

                @foreach ($groupedExpenses as $name => $totalAmount)
                    <div class="summary-row">
                        <span>{{ $name }}</span>
                        <span>{{ number_format($totalAmount) }}</span>
                    </div>
                @endforeach

                <div class="summary-row" style="border-top: 2px solid black; font-weight: bold;">
                    <span>کل کریڈٹ:</span>
                    <span>{{ number_format($data['expenses']->sum('amount')) }}</span>
                </div>
            </div>

            <!-- RIGHT BOX : GRAND SUMMARY -->
            <div class="summary-box">

                <div style="text-align: center; font-weight: bold; text-decoration: underline; margin-bottom: 10px;">
                    گرینڈ خلاصہ
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
                    <span>کاؤنٹر اخراجات:</span>
                    <span>{{ number_format($data['counterExpense']) }}</span>
                </div>

                @foreach ($data['dynamicTypes'] as $type)
                    <div class="summary-row">
                        <span >کل {{ $type }}:</span>
                        <span style="color: red;">
                            - {{ number_format($data['merges']->sum(fn($m) => $m['types'][$type] ?? 0)) }}
                        </span>
                    </div>
                @endforeach

                <div class="summary-row" style="border-top: 2px solid black; font-weight: bold;">
                    <span>کل کریڈٹ:</span>
                    <span>{{ number_format($data['expenses']->sum('amount')) }}</span>
                </div>

                <div class="summary-row">
                    <span>خالص کیش:</span>
                    <span>{{ number_format($data['merges']->sum('net_cash') - $data['counterExpense']) }}</span>
                </div>

                <div class="summary-row">
                    <span>کمیشن:</span>
                    <span>{{ number_format($data['totalKtCommission']) }}</span>
                </div>

                <div class="summary-row" style="font-weight: bold;">
                    <span>قابلِ ادا رقم:</span>

                    @php
                        $expenses = $data['expenses']->sum('amount');
                        $mergesMinusCounter = $data['merges']->sum('net_cash') - $data['counterExpense'];

                        $total =
                            $mergesMinusCounter >= 0
                                ? $expenses + $mergesMinusCounter
                                : $expenses - abs($mergesMinusCounter);
                    @endphp

                    <span>{{ number_format($total + $data['totalKtCommission']) }}</span>
                </div>

            </div>
        </div>
    </div>

</body>

</html>
