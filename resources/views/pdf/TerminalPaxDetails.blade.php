<!DOCTYPE html>
<html>

<head>
    <script src="{{ asset('assets/js/app.min.js') }}"></script>
    <style>
        @media print {
            @page {
                size: portait
            }
        }

        @page {
            /* transform: rotate(-90deg); */
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
            font-size: 13pt !important;
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
    <title>Terminal Passenger List </title>
</head>

<body>
<div id="info">
    <div class="companyName"><span>Kainat Travels</span></div>
    <div class="companyAddress">
        <div style=" padding-bottom: 5px;"><span style="font-weight:600">Main Pirwadhai Mor Peshawar Road
                    Rawalpindi</span></div>
        <div style="padding-bottom: 5px;"><span><b>UAN(24/7) : </b>03-111-777-333 </span></div>
        <div><span><b>Phone # : </b>03-111-777-333 </span></div>
    </div>
</div>
<br>
<hr>
<br>
<table id="table1">
    <tr>
        <th style="text-align: start;">Route:</th>
        <td style="text-align: end;">Rawalpindi - Karachi</td>
    </tr>
    <tr>
        <th style="text-align: start;">Date:</th>
        <td style="text-align: end;">Tuesday, 13 December 2022 21:00:00</td>
    </tr>
    <tr>
        <th style="text-align: start;">Bus No:</th>
        <td style="text-align: end;">BUSINESS CLASS</td>
    </tr>
</table>
<br>
<hr>
<br>
<table border="2" id="table2">
    <tr>
        <th> Sr # </th>
        <th>Seat # </th>
        <th>Name</th>
        <th>CNIC</th>
        <th>Dept City</th>
        <th>Dest City</th>
        <th>Elt Price</th>
        <th>Ticket Amount</th>
        <th>Terminal Name</th>
        <th>Ticket Booked By</th>

    </tr>
    <tr>
        <td> Sr # </td>
        <td>Seat # </td>
        <td>Name</td>
        <td>CNIC</td>
        <td>Dept City</td>
        <td>Dest City</td>
        <td>Elt Price</td>
        <td>Ticket Amount</td>
        <td>Terminal Name</td>
        <td>Ticket Booked By</td>

    </tr>
    <tr>
        <td> Sr # </td>
        <td>Seat # </td>
        <td>Name</td>
        <td>CNIC</td>
        <td>Dept City</td>
        <td>Dest City</td>
        <td>Elt Price</td>
        <td>Ticket Amount</td>
        <td>Terminal Name</td>
        <td>Ticket Booked By</td>

    </tr>
    <tr>
        <th colspan="9"> Main Total Online Tickets</th>
        <th>0</th>
    </tr>
    <tr>
        <th colspan="9"> Main Total Online Tickets Amount</th>
        <th>0</th>
    </tr>
    <tr>
        <th colspan="9"> Terminal Gross Sale</th>
        <th>0</th>
    </tr>
    <tr>
        <th colspan="9"> Terminal Discount </th>
        <th>0</th>
    </tr>
    <tr>
        <th colspan="9"> Elt Amount</th>
        <th>0</th>
    </tr>
    <tr>
        <th colspan="9"> Terminal Tickets Commission</th>
        <th>0</th>
    </tr>
    <tr>
        <th colspan="9"> Terminal Fixed Commission</th>
        <th>0</th>
    </tr>
    <tr>
        <th colspan="9"> Terminal Ticket Refund</th>
        <th>0</th>
    </tr>
    <tr>
        <th colspan="9"> Main Net Sale </th>
        <th>0</th>
    </tr>
    <tr>
        <th colspan="9"> Bank Deposit Amount </th>
        <th>0</th>
    </tr>
    <tr>
        <th colspan="9">Cash On Bus</th>
        <th>0</th>
    </tr>
</table>
<br>
<hr>
<br>
<table border="2" id="table3">
    <tr>
        <th>Driver One Name</th>
        <td>Shehbaz Haider Ali</td>
        <th>Driver Two Name</th>
        <td>Shehbaz Haider Ali</td>
        <th>Hostess Name</th>
        <td>Hostess Name</td>
    </tr>
</table>
{{--<script type="text/javascript">--}}
{{--    window.onload = function () {--}}
{{--        window.print();--}}
{{--    }--}}
{{--</script>--}}
</body>

</html>
