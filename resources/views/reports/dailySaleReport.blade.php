<!DOCTYPE html>
<html>
<head>
    <style>
        @page {
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
            font-weight: 900;
            font-size: 20pt;
            text-transform: uppercase;
            margin-top: -15px;
            margin-bottom: 10px;
            text-align: center;
            font-family: sans-serif, Verdana, Arial;
        }

        #table1 {
            padding: 10px;
            font-size: 10pt !important;
            border-collapse: collapse;
            width: 100% !important;
        }

        #table2 {
            border: 1px solid black;
            padding: 10px;
            font-size: 10pt !important;
            border-collapse: collapse;
            width: 100% !important;
            text-align: center;
        }

        #table3 {
            border: 1px solid black;
            padding: 10px;
            font-size: 10pt !important;
            border-collapse: collapse;
            width: 100% !important;
            text-align: center;
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

        .countPassenger {
            margin-top: 18px !important;
        }
    </style>
    <script src="{{ asset('/assets/js/jquery.min.js') }}"></script>
    {{--    <script type="text/javascript">--}}

    {{--        $(document).ready(function () {--}}
    {{--            window.print();--}}
    {{--        });--}}
    {{--    </script>--}}
    <title>Daily Summery Report</title>
</head>
<body>
<table border="2" id="table1" style="text-transform: capitalize;">
    <tr>
        <th class="centerTH">Route:</th>
    </tr>
</table>
</body>
</html>
