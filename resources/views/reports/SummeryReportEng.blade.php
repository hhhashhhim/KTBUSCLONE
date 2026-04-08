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

        th,
        td {
            padding: 6px;
            vertical-align: top;
            text-align: center;
        }

        .text-left {
            text-align: left;
        }
    </style>

    <title>Daily Summary Report</title>
</head>

<body>
    <div style="border: 2px solid black; padding: 15px 3px 5px 3px !important;">
        <div id="info">
            <div class="companyName">
                <span>(City Name) Closing {{ date('d/m/Y') }}</span>
            </div>
        </div>
        <br>

        <table border="2">
            <tr>
                <th>Sr NO</th>
                <th>Bus NO</th>

                @foreach (getDynamicHeaders() as $header)
                    <th>{{ $header->name }}</th>
                @endforeach

                <th>Description</th>
                {{-- <th>Amount</th> --}}
            </tr>

            @php
                $totalHeaders = [];
                $totalExpenseAmount = 0;
            @endphp

            @foreach (getDynamicHeaders() as $singleHeader)
                @php
                    $totalHeaders[] = 0;
                @endphp
            @endforeach

            @foreach ($data as $key => $single)
                @php
                    $expenseDescriptions = [];
                    $expenseAmount = 0;

                    if (isset($headers_link[$single->id]['expenses'])) {
                        foreach ($headers_link[$single->id]['expenses'] as $exp) {
                            $expenseDescriptions[] = $exp['description'];
                            $expenseAmount += (int) $exp['amount'];
                        }
                    }

                    $totalExpenseAmount += $expenseAmount;
                @endphp

                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>{{ getBusName($single->closing[0]->bus_id) }}</td>

                    @foreach (getDynamicHeaders() as $keyHeader => $singleHeader)
                        @php
                            $headerValue = 0;

                            if (isset($headers_link[$single->id][$singleHeader->id][0])) {
                                $headerValue = (int) $headers_link[$single->id][$singleHeader->id][0]->value;
                            }

                            $totalHeaders[$keyHeader] += $headerValue;
                        @endphp

                        <td>{{ $headerValue }}</td>
                    @endforeach

                    <td class="text-left">
                        @if (count($expenseDescriptions) > 0)
                            {!! implode('<br>', $expenseDescriptions) !!}
                        @else
                            -
                        @endif
                    </td>

                    {{-- <td>{{ $expenseAmount }}</td> --}}
                </tr>
            @endforeach

            <tr>
                <th></th>
                <th>Total</th>

                @foreach ($totalHeaders as $j)
                    <th>{{ $j }}</th>
                @endforeach

                <th></th>
                {{-- <th>{{ $totalExpenseAmount }}</th> --}}
            </tr>
        </table>
    </div>
</body>

</html>
