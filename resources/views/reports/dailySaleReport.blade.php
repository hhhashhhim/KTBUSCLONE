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

        .fontWightTh {
            font-weight: 100 !important;
        }

        .countPassenger {
            margin-top: 18px !important;
        }
    </style>

    <title> Daily Summary Report</title>
</head>

<body>
<div style="border: 2px solid black; padding: 15px 3px 5px 3px !important;">
    <div id="info">
        <div class="companyName"><span>Kainat Travels</span></div>
    </div>
    <br>

    <table style="border: none;">
        <tr>
            <th class="centerTH">Date</th>
            <th class="fontWightTh">Date</th>
            <th class="centerTH">Bus NO</th>
            <th class="fontWightTh">Bus NO</th>
        </tr>
    </table>
    <br>
    <div style=" display: grid; grid-template-columns: auto auto auto;">
        <!-- City 1 -->
        <div>
            <table border="2" style="text-align: center;">
                <tr>
                    <th colspan="4">City 1</th>
                </tr>
                <tr>
                    <th>Sr No</th>
                    <th>Terminal Name</th>
                    <th>Passenger Count</th>
                    <th>Amount</th>
                </tr>
                <tr>
                    <td>Sr No</td>
                    <td>Terminal Name</td>
                    <td>Passenger Count</td>
                    <td>Amount</td>
                </tr>
                <tr>
                    <td>Sr No</td>
                    <td>Terminal Name</td>
                    <td>Passenger Count</td>
                    <td>Amount</td>
                </tr>
                <tr>
                    <td>Sr No</td>
                    <td>Terminal Name</td>
                    <td>Passenger Count</td>
                    <td>Amount</td>
                </tr>
                <tr>
                    <td>Sr No</td>
                    <td>Terminal Name</td>
                    <td>Passenger Count</td>
                    <td>Amount</td>
                </tr>
                <tr>
                    <td>Sr No</td>
                    <td>Terminal Name</td>
                    <td>Passenger Count</td>
                    <td>Amount</td>
                </tr>
                <tr>
                    <td>Sr No</td>
                    <td>Terminal Name</td>
                    <td>Passenger Count</td>
                    <td>Amount</td>
                </tr>
                <tr>
                    <td>Sr No</td>
                    <td>Terminal Name</td>
                    <td>Passenger Count</td>
                    <td>Amount</td>
                </tr>
                <tr>
                    <td>Sr No</td>
                    <td>Terminal Name</td>
                    <td>Passenger Count</td>
                    <td>Amount</td>
                </tr>
                <tr>
                    <td>Sr No</td>
                    <td>Terminal Name</td>
                    <td>Passenger Count</td>
                    <td>Amount</td>
                </tr>
                <tr>
                    <td>Sr No</td>
                    <td>Terminal Name</td>
                    <td>Passenger Count</td>
                    <td>Amount</td>
                </tr>
            </table>
            <table border="2" style="text-align: center;">
                <tr>
                    <td style="width: 46%">Total</td>
                    <td>Passenger Count Total</td>
                    <td style="width: 17.5%">Total Amount</td>
                </tr>
            </table>
            <table border="2" style="text-align: center;">
                <tr>
                    <td style="width: 46%">City 2 Total</td>
                    <td>City 2 Passenger Count Total</td>
                    <td style="width: 17.5%">Total Amount</td>
                </tr>
            </table>
            <table border="2" style="text-align: center;">
                <tr>
                    <td style="width: 46%">City 2 + city 1 Total</td>
                    <td>City 2 + city 1 Passenger Count Total</td>
                    <td style="width: 17.5%">City 1 + City 2Total Amount</td>
                </tr>
            </table>
            <table border="2" style="text-align: center;">
                <tr>
                    <td style="width: 50%">Expenses</td>
                    <td style="width: 50%">Expenses Total</td>
                </tr>
            </table>
            <table border="2" style="text-align: center;">
                <tr>
                    <td style="width: 50%">Savings/Profit</td>
                    <td style="width: 50%">(City 1 + City 2) - Expenses</td>
                </tr>
            </table>
        </div>
        <!-- City 2 -->
        <div>
            <table border="2" style="text-align: center;border-left: none;border-right: none;">
                <tr>
                    <th colspan="4" style="border-left: none !important; border-right: none !important;">City 2</th>
                </tr>
                <tr >
                    <th style="border-left: none !important;">Sr No</th>
                    <th style="border:1px solid rgb(80, 79, 79) !important">Terminal Name</th>
                    <th style="border:1px solid rgb(80, 79, 79) !important">Passenger Count</th>
                    <th style="border-right: none !important;">Amount</th>
                </tr>
                <tr>
                    <td>Sr No</td>
                    <td>Terminal Name</td>
                    <td>Passenger Count</td>
                    <td>Amount</td>
                </tr>
                <tr>
                    <td>Sr No</td>
                    <td>Terminal Name</td>
                    <td>Passenger Count</td>
                    <td>Amount</td>
                </tr>
                <tr>
                    <td>Sr No</td>
                    <td>Terminal Name</td>
                    <td>Passenger Count</td>
                    <td>Amount</td>
                </tr>
                <tr>
                    <td>Sr No</td>
                    <td>Terminal Name</td>
                    <td>Passenger Count</td>
                    <td>Amount</td>
                </tr>
                <tr>
                    <td>Sr No</td>
                    <td>Terminal Name</td>
                    <td>Passenger Count</td>
                    <td>Amount</td>
                </tr>
                <tr>
                    <td>Sr No</td>
                    <td>Terminal Name</td>
                    <td>Passenger Count</td>
                    <td>Amount</td>
                </tr>
                <tr>
                    <td>Sr No</td>
                    <td>Terminal Name</td>
                    <td>Passenger Count</td>
                    <td>Amount</td>
                </tr>
                <tr>
                    <td>Sr No</td>
                    <td>Terminal Name</td>
                    <td>Passenger Count</td>
                    <td>Amount</td>
                </tr>
                <tr>
                    <td>Sr No</td>
                    <td>Terminal Name</td>
                    <td>Passenger Count</td>
                    <td>Amount</td>
                </tr>
                <tr>
                    <td>Sr No</td>
                    <td>Terminal Name</td>
                    <td>Passenger Count</td>
                    <td>Amount</td>
                </tr>
            </table>
            <table border="2" style="text-align: center;">
                <tr>
                    <td style="width: 46%">Total</td>
                    <td>Passenger Count Total</td>
                    <td style="width: 17.5%">Total Amount</td>
                </tr>

            </table>
        </div>
        <!-- City3 -->
        <div>
            <table border="2" style="text-align: center;">
                <tr>
                    <th colspan="2">Expenses</th>
                </tr>
                <tr>
                    <th>Expenses Details</th>
                    <th>Amount</th>
                </tr>
                <tr>
                    <td>Expenses Details</td>
                    <td>Amount</td>
                </tr>
                <tr>
                    <td>Expenses Details</td>
                    <td>Amount</td>
                </tr>
                <tr>
                    <td>Expenses Details</td>
                    <td>Amount</td>
                </tr>
                <tr>
                    <td>Expenses Details</td>
                    <td>Amount</td>
                </tr>
                <tr>
                    <td>Expenses Details</td>
                    <td>Amount</td>
                </tr>
                <tr>
                    <td>Expenses Details</td>
                    <td>Amount</td>
                </tr>
                <tr>
                    <td>Expenses Details</td>
                    <td>Amount</td>
                </tr>
                <tr>
                    <td>Expenses Details</td>
                    <td>Amount</td>
                </tr>
                <tr>
                    <td>Expenses Details</td>
                    <td>Amount</td>
                </tr>
                <tr>
                    <td>Expenses Details</td>
                    <td>Amount</td>
                </tr>

            </table>
            <table border="2" style="text-align: center;">
                <tr>
                    <td style="width: 46%">Total</td>
                    <td style="width: 17.5%">Total Amount</td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
