<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Closing Summary Report</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 20px;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            color: #000;
        }

        .header {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th {
            background: #f2f2f2;
            font-weight: bold;
            text-align: center;
            padding: 6px;
            border: 1px solid #000;
        }

        table td {
            padding: 5px;
            border: 1px solid #000;
            text-align: center;
        }

        table tfoot td {
            font-weight: bold;
            background: #eaeaea;
        }

        .summary-container {
            width: 100%;
            margin-top: 10px;
        }

        .summary-left,
        .summary-right {
            width: 48%;
            display: inline-block;
            vertical-align: top;
        }

        .summary-box {
            border: 2px solid #000;
            padding: 10px;
        }

        .summary-title {
            text-align: center;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 10px;
        }

        .summary-row {
            display: block;
            margin-bottom: 5px;
        }

        .summary-row span:first-child {
            float: left;
        }

        .summary-row span:last-child {
            float: right;
        }

        .clear {
            clear: both;
        }

        .highlight {
            background: #f2f2f2;
            font-weight: bold;
        }

        .negative {
            color: red;
        }
    </style>
</head>

<body>

<div class="header">
    CLOSING SUMMARY FOR {{ date('d-M-Y', strtotime($data['closing_date'])) }}
</div>

<table>
    <thead>
        <tr>
            <th>Sr#</th>
            <th>Bus No</th>
            <th>Route</th>
            <th>Sale</th>
            <th>Expense</th>
            <th>Net Sale</th>
            <th>Received Bank</th>

            @foreach ($data['dynamicTypes'] as $type)
                <th>{{ $type }}</th>
            @endforeach

            <th>Net Cash</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($data['merges'] as $key => $item)
            <tr>
                <td>{{ $key + 1 }}</td>
                <td>{{ $item['bus_no'] }}</td>
                <td>{{ $item['route'] }}</td>
                <td>{{ number_format($item['sale']) }}</td>
                <td>{{ number_format($item['expense']) }}</td>
                <td>{{ number_format($item['net_sale']) }}</td>
                <td>{{ number_format($item['total_received_bank']) }}</td>

                @foreach ($data['dynamicTypes'] as $type)
                    <td>{{ number_format($item['types'][$type] ?? 0) }}</td>
                @endforeach

                <td>{{ number_format($item['net_cash']) }}</td>
            </tr>
        @endforeach
    </tbody>

    <tfoot>
        <tr>
            <td colspan="3">TOTAL</td>
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

<div class="summary-container">

    <!-- LEFT SIDE -->
    <div class="summary-left">
        <div class="summary-box">
            <div class="summary-title">TOTAL CREDIT EXPENSES</div>

            @php
                $groupedExpenses = collect($data['expenses'])
                    ->groupBy(fn($e) => $e['expense_category']['name'])
                    ->map(fn($items) => $items->sum('amount'));
            @endphp

            @foreach ($groupedExpenses as $name => $totalAmount)
                <div class="summary-row">
                    <span>{{ $name }}</span>
                    <span>{{ number_format($totalAmount) }}</span>
                    <div class="clear"></div>
                </div>
            @endforeach

            <div class="summary-row highlight">
                <span>Total Credit</span>
                <span>{{ number_format($data['expenses']->sum('amount')) }}</span>
                <div class="clear"></div>
            </div>
        </div>
    </div>

    <!-- RIGHT SIDE -->
    <div class="summary-right">
        <div class="summary-box">
            <div class="summary-title">GRAND SUMMARY</div>

            <div class="summary-row">
                <span>Total Gross Sale</span>
                <span>{{ number_format($data['merges']->sum('sale')) }}</span>
                <div class="clear"></div>
            </div>

            <div class="summary-row">
                <span>Total Expenses</span>
                <span class="negative">- {{ number_format($data['merges']->sum('expense')) }}</span>
                <div class="clear"></div>
            </div>

            <div class="summary-row highlight">
                <span>Net Sale</span>
                <span>{{ number_format($data['merges']->sum('net_sale')) }}</span>
                <div class="clear"></div>
            </div>

            <div class="summary-row">
                <span>Bank Cash</span>
                <span class="negative">- {{ number_format($data['totalReceivedBank']) }}</span>
                <div class="clear"></div>
            </div>

            <div class="summary-row highlight">
                <span>KT Commission</span>
                <span>{{ number_format($data['totalKtCommission']) }}</span>
                <div class="clear"></div>
            </div>

            <div class="summary-row highlight">
                <span>Net Payable</span>
                <span>
                    {{ number_format(
                        $data['merges']->sum('net_cash')
                        + $data['totalCounterIncome']
                        - $data['totalCounterExpense']
                        - $data['totalReceivedBank']
                        + $data['expenses']->sum('amount')
                        + $data['totalKtCommission']
                    ) }}
                </span>
                <div class="clear"></div>
            </div>

        </div>
    </div>

</div>

</body>
</html>