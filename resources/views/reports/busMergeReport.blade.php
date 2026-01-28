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
            float: right;
            width: 250px;
            border: 2px solid black;
            padding: 10px;
            background-color: #f9f9f9;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            font-size: 11pt;
            border-bottom: 1px dotted #ccc;
        }

        .summary-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 12pt;
            margin-top: 5px;
            border-top: 2px solid black;
        }

        /* Clearfix for the float */
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        .summary-box-left {
            float: left;
            width: 250px;
            border: 2px solid black;
            padding: 10px;
            background-color: #ffffff;
        }

        /* Maintain the existing summary-row styles for consistency */
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            font-size: 11pt;
            border-bottom: 1px dotted #ccc;
        }
    </style>
    <title>Daily Summary Report</title>
</head>

<body>

    <div style="border: 2px solid black; padding: 15px 3px 5px 3px !important;">
        <div id="info">
            <div class="companyName">
                <span>CLOSING SUMMARY FOR {{ date('d-M-Y', strtotime($data['closing_date'])) }}</span>
            </div>
        </div>
        <br>

        <table border="2">
            <thead>
                <tr>
                    <th>Sr NO</th>
                    <th>Bus NO</th>
                    <th>Sale</th>
                    <th>Expense</th>
                    <th>Net Sale</th>
                    {{-- Dynamic Headers for Portals --}}
                    @foreach ($data['dynamicTypes'] as $type)
                        <th>{{ $type }}</th>
                    @endforeach
                    <th>Net Cash</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data['merges'] as $key => $item)
                    <tr>
                        <td class="text-center">{{ $key + 1 }}</td>
                        <td>{{ $item['bus_no'] }}</td>

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
                    <td colspan="2" class="text-center">Total</td>

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
                    TOTAL CREDIT EXPENSES
                </div>

                @foreach ($data['expenses'] as $expense)
                    <div class="summary-row">
                        <span>{{ $expense['expense_category']['name'] }}</span>
                        <span>{{ number_format($expense['amount']) }}</span>
                    </div>
                @endforeach

                {{-- Total Row --}}
                <div class="summary-row"
                    style="border-top: 2px solid black; font-weight: bold; margin-top: 5px; background-color: #f2f2f2;">
                    <span>Total Credit:</span>
                    <span>{{ number_format($data['expenses']->sum('amount')) }}</span>
                </div>

            </div>
            <div class="summary-box">
                <div style="text-align: center; font-weight: bold; text-decoration: underline; margin-bottom: 10px;">
                    GRAND SUMMARY
                </div>

                <div class="summary-row">
                    <span>Total Gross Sale:</span>
                    <span>{{ number_format($data['merges']->sum('sale')) }}</span>
                </div>
              
                <div class="summary-row">
                    <span>Total Expenses:</span>
                    <span style="color: red;">- {{ number_format($data['merges']->sum('expense')) }}</span>
                </div>

                <div class="summary-row">
                    <span>Net Sale:</span>
                    <span>{{ number_format($data['merges']->sum('net_sale')) }}</span>
                </div>
                  <div class="summary-row">
                    <span>Counter Expenses:</span>
                    <span>{{ number_format($data['counterExpense']) }}</span>
                </div>
                @foreach ($data['dynamicTypes'] as $type)
                    <div class="summary-row">
                        <span>Total {{ $type }}:</span>
                        <span style="color: red;">-
                            {{ number_format($data['merges']->sum(fn($m) => $m['types'][$type] ?? 0)) }}</span>
                    </div>
                @endforeach

 <div class="summary-row"
                    style="border-top: 2px solid black; font-weight: bold; margin-top: 5px; background-color: #f2f2f2;">
                    <span>Total Credit:</span>
                    <span>{{ number_format($data['expenses']->sum('amount')) }}</span>
                </div>
<div class="summary-row" style="background-color: #eee;">
                    <span>NET CASH:</span>
                    <span>{{ number_format($data['merges']->sum('net_cash') - $data['counterExpense']) }}</span>
                </div>
                <div class="summary-row" style="background-color: #eee;">
                    <span>Net Payable:</span>
                    @php
                    $expenses = $data['expenses']->sum('amount');
                    $mergesMinusCounter = $data['merges']->sum('net_cash') - $data['counterExpense'];

                    // If mergesMinusCounter is negative, subtract from expenses, else add
                    $total = $mergesMinusCounter < 0 
                            ? $expenses - abs($mergesMinusCounter) 
                            : $expenses + $mergesMinusCounter;
                @endphp

<span>
    {{ $total >= 0 ? '+' : '-' }}{{ number_format(abs($total)) }}
</span>


<span>{{ $formattedTotal }}</span>

                </div>
            </div>
        </div>
    </div>

</body>

</html>
