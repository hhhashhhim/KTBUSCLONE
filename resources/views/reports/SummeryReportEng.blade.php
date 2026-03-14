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
    </style>

    <title> Daily Summary Report</title>
</head>

<body>
    <div style="border: 2px solid black; padding: 15px 3px 5px 3px !important;">
        <div id="info">
            <div class="companyName"><span>(City Name) Closing {{ date('d/m/Y') }}</span></div>
        </div>
        <br>

        <table border="2">
            <tr>
                <th>Sr NO</th>
                <th>Bus NO</th>
                @foreach (getDynamicHeaders() as $header)
                    <th>{{ $header->name }}</th>
                @endforeach
                {{-- <th>Net Cash</th> --}}
            </tr>
            @php
                $totalHeaders = [];
                $totalTerminals = [];
                $totalNetCash = 0;
            @endphp
            <!-- Raw Data -->
            @foreach (getDynamicHeaders() as $singleHeader)
                @php
                    array_push($totalHeaders, 0);
                @endphp
            @endforeach
           
            @foreach ($data as $key => $single)
                @php
                    $singleRowNet = 0;
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
                    
                    {{-- <td>{{ $singleRowNet }}</td> --}}
                </tr>
                {{-- @php
                    $totalNetCash += $singleRowNet;
                @endphp --}}
            @endforeach
            <!-- Total Row -->
            <tr>
                <th></th>
                <th></th>
                @foreach ($totalHeaders as $j)
                    <th>{{ $j }}</th>
                @endforeach
              
                {{-- <th> {{ $totalNetCash }}</th> --}}
            </tr>
        </table>
    </div>
</body>

</html>
