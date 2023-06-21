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
        <div class="companyName"><span>Route Fare</span></div>
    </div>
    <br>

    <table border="2">
        <tr>
            <th>City From</th>
            <th>City To</th>
            @foreach($th as $heading)
            <th>
                {{ $heading->name }}
            </th>
            @endforeach
        </tr>
            @foreach($data as $item)
                @foreach($item as $fare)
                <tr>
                    <td> {{ $fare['departure_city'] }}</td>
                    <td> {{ $fare['destination_city'] }}</td>
                    @foreach($th as $heading)
                    <th>
                        {{ $fare[$heading->name."_fare"]??'N/A' }}
                    </th>
                    @endforeach
                </tr>
                @endforeach
            @endforeach
        </tr>
    </table>
</div>
</body>
</html>
