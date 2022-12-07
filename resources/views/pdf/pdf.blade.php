{{--{{dd($data, $data_terms)}}--}}
@foreach($data as $key => $single)
    <!DOCTYPE html>
<html>
<head>
    <script src="{{ asset('assets/js/app.min.js') }}"></script>
    <style>
        @page {
            size: 75mm 150mm;
            transform: rotate(-90deg);
            padding: 0;
            margin: 10px;
        }

        body {
            margin: 10px 10px 10px 10px;
            font-size: 6pt;
            font-family: Verdana, Arial, sans-serif;
        }

        #custinfo {
            line-height: 1.2;
        }

        .companyName {
            font-weight: 900;
            font-size: 13pt;
            text-transform: uppercase;
            margin-top: -9px;
            text-align: center;
            font-family: sans-serif, Verdana, Arial;
        }

        .companyAddress {
            font-weight: 400;
            font-size: 10pt;
            margin-bottom: 05px;
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

        .font-weight-bold {
            font-weight: 700;
        }

        .float-left {
            float: left;
        }

        .float-right {
            float: right;
        }

        .clear-both {
            clear: both;
            white-space: nowrap;

        }
        .kt-bottom{
         position: fixed;
         left:23%;
         bottom:1%;
        }
        .table-data{
            line-height:9px;
        }
    </style>
    <title>Print Ticket</title>
</head>
<body>
{{--{{dd($data)}}--}}

<div id="info">
    <div class="companyName"><span>Kainat Travels</span></div>
    <div class="companyAddress">
        <span>{{$data_terms->address}}</span>
        <div><span><b>UAN(24/7) : </b>{{format_uan($data_terms->uan)}}</span></div>
        <div><span><b>Phone : </b>{{format_phone($data_terms->phone)}}</span></div>
    </div>
    @if($duplicate == 1)
    <div style="text-align: center;" >
        <span class="text-uppercase font-weight-bold"><u><h1>(Duplicate Ticket)</h1></u></span>
    </div>
    @endif
    <div class="custinfo" id="custinfo">
        <div id="barcode-area">
            <img
                src="data:image/png;base64,{{ base64_encode(QrCode::size(100)->format('svg')->style('round')->generate('Customer Name : '. $data[$key]['customer']->name . ' | ' . 'Customer CNIC : '.format_cnic($data[$key]['customer']->cnic) .' | ' . 'Customer Phone # : '.format_phone($data[$key]['customer']->contact ).' | '.'Seat No : ' . $data[$key]->seat_no . ' | '.'Bus No : ' . 'Bus No' . ' | '. 'From : ' . $data[$key]['departure_city']->name . ' | ' . 'To : ' . $data[$key]['destination_city']->name . ' | ' . 'Departure Date : ' . date('d/m/Y', strtotime($data[$key]->date)) . ' | '. 'Departure Time : ' . date('H:i A', strtotime($data[$key]['schedule']->time)) . ' | ' . ' Booking Date & Time : '.  date('d/m/Y H:i A', strtotime($data[$key]->created_at)) . ' | ' . 'Fare : 900')) }}"
                class="rounded"/>
        </div>
        <div class="table-data">
            <p class="font-weight-bold float-left">Name :</p>
            <p class="float-right">{{ $data[$key]['customer']->name }}</p>
        </div>
        <div class="clear-both table-data">
            <p class="font-weight-bold float-left">Seat No :</p>
            <p class="float-right">{{ $data[$key]->seat_no }}</p>
        </div>
        <div class="clear-both table-data">
            <p class="font-weight-bold float-left">Bus Class :</p>
            <p class="float-right">{{ $data[$key]['schedule']['bus_class']->name }}</p>
        </div>
        <div class="clear-both table-data">
            <p class="font-weight-bold float-left"><span style="text-decoration: underline;">From :</span> &nbsp;
                <span>{{$data[$key]['departure_city']->name}}</span>
            </p>
            <p class="font-weight-bold float-right"><span style="text-decoration: underline;">To :</span>&nbsp;
                <span>{{ $data[$key]['destination_city']->name }}</span>
            </p>
        </div>
        <div class="clear-both table-data">
            <p class="font-weight-bold float-left">Departure Date :</p>
            <p class="float-right">{{date('d/m/Y', strtotime($data[$key]->date))}}</p>
        </div>
        <div class="clear-both table-data">
            <p class="font-weight-bold float-left">Departure Time :</p>
            <p class="float-right">{{ date('H:i A', strtotime($data[$key]['schedule']->time))}} </p>
        </div>
        <div class="clear-both table-data">
            <p class="font-weight-bold float-left">Booking Date :</p>
            <p class="float-right">{{ date('d/m/Y H:i A', strtotime($data[$key]->created_at)) }}</p>
        </div>
        <div class="clear-both table-data">
            <p class="font-weight-bold float-left">Fare : </p>
            <p class="float-right">{{ $data[$key]->seat_fare }}</p>
        </div>
    </div>
</div>
<p style="width:100%;  text-align: center; margin:0">
    .......................................................................................................</p>
<div>
    <div style="text-align: center;">
        <h5 style="text-decoration: underline;"><b>Terms & Conditions Applied</b></h5>
        <h5>{{$data_terms->terms_condition}}</h5>
        <h4 class="kt-bottom"><b>&copy; Rights Reserved By Kainat Travels</b></h4>
    </div>
</div>
<p style="page-break-before: always"></p>
<div class="custinfoverify" id="custinfoverify">

    <div style="width:100%">
        <p style="margin-bottom: 0" class="font-weight-bold float-left"><span style="text-decoration: underline;">Seat # :</span>
            &nbsp;<span>{{ $data[$key]->seat_no }}</span></p>
        <p style="margin-bottom: 0" class="font-weight-bold float-right"><span style="text-decoration: underline;">Bus Class :</span>
            &nbsp;<span>{{ $data[$key]['schedule']['bus_class']->name }}</span>
        </p>
    </div>

    <div style="width:100%" class="clear-both">
        <p style="margin-bottom: 0" class="font-weight-bold float-left">From :
            <span>{{$data[$key]['departure_city']->name}}</span></p>
        <p style="margin-bottom: 0" class="font-weight-bold float-right">To :
            <span>{{$data[$key]['destination_city']->name}}</span></p>
    </div>

    <div class="clear-both">
        <p style="margin-bottom: 0" class="font-weight-bold float-left">Departure Date :</p>
        <p style="margin-bottom: 0"
           class="font-weight-bold float-right">{{date('d/m/Y', strtotime($data[$key]->date))}}</p>
    </div>

    <div class="clear-both">
        <p class="font-weight-bold float-left" style="margin-bottom: 0">Ticket Holder Name :</p>
        <p class="float-right" style="margin-bottom: 0">{{ $data[$key]['customer']->name }}</p>
    </div>

    <div class="clear-both">
        <p class="font-weight-bold float-left" style="margin-bottom: 0">CNIC Number :</p>
        <p class="float-right" style="margin-bottom: 0">{{format_cnic($data[$key]['customer']->cnic)}}</p>
    </div>

    <div class="clear-both">
        <p style="margin-bottom: 0" class="font-weight-bold float-left">Contact # : </p>
        <p style="margin-bottom: 0" class="float-right">{{format_phone($data[$key]['customer']->contact)}}</p>
    </div>

</div>
</body>
</html>
@endforeach
