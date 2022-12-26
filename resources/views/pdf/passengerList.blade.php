{{--{{dd($data, $data_terms)}}--}}
<html>
<head>
    <meta charset="utf-8">
    <script src="{{ asset('assets/js/app.min.js') }}"></script>

    <style type="text/css">

        @page {
            /*size: 76mm 120mm;*/
            transform: rotate(-90deg);
            padding: 0;
        }

        .p {
            margin-left: 5px;
        }

        body {
            /*margin: 0 auto;*/
            /*margin: 200px,20px;*/
            /*font-size: 7pt;*/
            font-family: Verdana, Arial, sans-serif;
        }


        #info {
            /*width: 58mm;*/
        }

        #custinfo {
            line-height: 2.5;
        }

        .companyname {
            font-weight: 900;
            font-size: 16pt;
            text-transform: uppercase;
            margin-bottom: 10px;
            text-align: center;
            font-family: sans-serif, Verdana, Arial;
        }

        .companyAddress {
            font-weight: 400;
            font-size: 11pt;
            margin-bottom: 10px;
            text-align: center;
            font-family: sans-serif, Verdana, Arial;
        }

        #barcode-area {
            text-align: center;
            /*top: 7.6cm;*/
            /*left: 3.5cm;*/
        }

        #barcode-hint {
            position: relative;
            bottom: 2mm;
        }

        table {
            border-collapse: collapse;
            text-align: center;
        }

        table, td, th {
            border: 1px solid black;
        }

        #Pax {
            font-size: 9px;
            line-height: 0.5;
        }
    </style>
</head>
<body>
{{--{{dd($data)}}--}}

<div style="font-weight: 700; text-align: center !important; margin-bottom: 10px !important; font-size: 10pt;"><span>KAINAT TRAVELS</span></div>
<div>
    <div class="clear-both">
        <p class="float-left w-25 text-center" style="margin: 2px;">Card #</p>
        <p class="float-left w-50" style="border-bottom: 1px dotted #fff; margin: 2px;"></p>
        <p class="float-left w-25 text-center" style="margin: 2px;">33333-3333333-3</p>
    </div>
    <div class="clear-both">
        <p class="float-left w-25 text-center" style="margin: 2px;">Phone</p>
        <p class="float-left w-50" style="border-bottom: 1px dotted #fff; margin: 2px;"></p>
        <p class="float-left w-25 text-center" style="margin: 2px;">0300-0000000</p>
    </div>
</div>
<p style="width:100%;  text-align: center; margin:0">
    .......................................................................................................
</p>

<div id="info">
    <!-- <div class="companyAddress">
        <span>{{$data_terms->address}}</span>
        <div><span><b>UAN(24/7):</b>{{$data_terms->uan}}</span></div>
        <div><span><b>Phone:</b>{{$data_terms->phone}}</span></div>
    </div> -->
    <div class="custinfo" id="custinfo">
        <div>
            <h3 class="float-left w-25 text-center">Items</h3>
            <p class="float-left w-50" style="border-bottom: 1px dotted #fff;"></p>
            <h3 class="float-left w-25 text-center">Price</h3>
        </div>
        <div class="clear-both">
            <p class="float-left w-25 text-center">Seat No</p>
            <p class="float-left w-50" style="border-bottom: 1px dotted #000;"></p>
            <p class="float-left w-25 text-center">asas</p>
        </div>
        <div class="clear-both">
            <p class="float-left w-25 text-center">Bus No</p>
            <p class="float-left w-50" style="border-bottom: 1px dotted #000;"></p>
            <p class="float-left w-25 text-center">Malik ajay </p>
        </div>
        <div class="clear-both">
            <p class="float-left w-25 text-center">Data</p>
            <p class="float-left w-50" style="border-bottom: 1px dotted #000;"></p>
            <p class="float-left w-25 text-center">dsfsdf</p>
        </div>
        <div class="clear-both">
            <p class="float-left w-25 text-center">Time</p>
            <p class="float-left w-50" style="border-bottom: 1px dotted #000;"></p>
            <p class="float-left w-25 text-center">asdfdsf</p>
        </div>
        <div class="clear-both">
            <p class="float-left w-25 text-center">Booking</p>
            <p class="float-left w-50" style="border-bottom: 1px dotted #000;"></p>
            <p class="float-left w-25 text-center">cxasfdsf</p>
        </div>
        <div class="clear-both">
            <p class="float-left w-25 text-center">Fare</p>
            <p class="float-left w-50" style="border-bottom: 1px dotted #000;"></p>
            <p class="float-left w-25 text-center">900</p>
        </div>
    </div>
</div>
<p style="width:100%;  text-align: center; margin:0">
    .......................................................................................................
</p>

<div>
    <div class="clear-both">
        <h4 class="float-left w-25 text-center" style="margin: 2px;">SUBTOTAL</h4>
        <p class="float-left w-50" style="border-bottom: 1px dotted #fff; margin: 2px;"></p>
        <h4 class="float-left w-25 text-center" style="margin: 2px;">432</h4>
    </div>
    <div class="clear-both">
        <h4 class="float-left w-25 text-center" style="margin: 2px;">TAX</h4>
        <p class="float-left w-50" style="border-bottom: 1px dotted #fff; margin: 2px;"></p>
        <h4 class="float-left w-25 text-center" style="margin: 2px;">18</h4>
    </div>
    <div class="clear-both">
        <h4 class="float-left w-25 text-center" style="margin: 2px;">TOATAL</h4>
        <p class="float-left w-50" style="border-bottom: 1px dotted #fff; margin: 2px;"></p>
        <h4 class="float-left w-25 text-center" style="margin: 2px;">450</h4>
    </div>
</div>
<p style="width:100%;  text-align: center; margin:0">
    .......................................................................................................
</p>

<div class="clear-both">
    <div class="text-center">
        <div class="companyName" style="margin: 10px 0px;"><span>Kainat Travels</span></div>
        <div>
            Test Terminal, islamabad.Test Terminal, islamabad.
        </div>
    </div>
    <div class="text-center">

        <img style="width: 50px !important; height: 50px !important; margin: 10px;"
             src="data:image/png;base64,{{ base64_encode(QrCode::format('svg')->style('round')->generate('Customer Name : '. 'bfhjgf' . ' | ' . 'Customer CNIC : '.'dfsjhfds' .' | ' . 'Customer Phone # : '.'bhjxfd' .' | '.'Seat No : ' . 'dsbfgd' . ' | '.'Bus No : ' . 'Bus No' . ' | '. 'From : ' . 'jdsfg' . ' | ' . 'To : ' . 'fdhjdsf' . ' | ' . 'Departure Date : ' . 'sgffndbgjkd' . ' | '. 'Departure Time : ' . 'kmshgfkjdsg' . ' | ' . ' Booking Date & Time : '.  'dsjfhdsb' . ' | ' . 'Fare : 900')) }}"
             class="rounded img-thumbnail"/>

    </div>
</div>
<!-- <div class="clear-both" style="text-align: center;">
    <h5 style="text-decoration: underline;"><b>Terms & Conditions Applied</b></h5>
    <h5>{{$data_terms->terms_condition}}</h5>
    <h4><b>&copy; Rights Reserved By Kainat Travels</b></h4>
</div> -->

</body>
</html>

