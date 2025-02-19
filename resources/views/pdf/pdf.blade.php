@foreach($data['tickets']  as $key => $single)
    <!DOCTYPE html>
<html>

<head>
    <script src="{{ asset('/assets/js/jquery.min.js') }}"></script>
    <style>
        @page {
            size: 72mm 210mm;
            transform: rotate(-90deg);
            padding: 0;
            margin: 10px 8px 10px 8px;
        }


        body {
            margin: 10px 8px 10px 8px;
            font-size: 7pt;
            font-family: Franklin Gothic Medium, serif;

        }

        #custinfo {
            line-height: 1.2;
        }

        .companyname {
            font-weight: 900;
            font-size: 16pt;
            text-transform: uppercase;
            margin-bottom: 10px;
            text-align: center;
            font-family: Franklin Gothic Medium, serif;

        }

        .companyAddress {
            font-weight: 400;
            font-size: 12pt;
            margin-bottom: 10px;
            text-align: center;
            font-family: Franklin Gothic Medium, serif;

        }

        #barcode-area {
            text-align: center;
        }

        .font-weight-bold {
            font-weight: 700;
        }
        .my-5
        {
            margin-bottom: 0;
        }
        .float-left {
            float: left;
        }

        .float-right {
            float: right;
        }

        .clear-both {
            clear: both;
        }

        hr {
            border: 2px dashed black;
        }
    </style>
    <script type="text/javascript">
        $(document).ready(function () {
            window.print();
        });

        // setTimeout(function(){
        //     window.close() ;
        // }, 1000); //Time before execution
    </script>
    <title>Print Ticket</title>
</head>

<body>
<div style="page-break-before:always">&nbsp;</div>
<div id="info">
    <div class="companyname"><span>{{isset($data['format']->name) ? $data['format']->name : "Kainat Travels"}}</span></div>
    <div class="companyAddress">
        <span>{{ $data['tickets'][$key]->terminal->address }}</span>
        <div><span><b>UAN(24/7) : </b>{{isset($data['format']->uan) ? $data['format']->uan : "03-111-777-333"}}</span></div>
        @if(isset($data['format']) && $data['format']->show_phone == 1)
            @if(isset($data['format']->phone))
            <div><span><b>Phone : </b> {{ formatContact($data['format']->phone) }}</span>
            </div>
            @endif
        @else
        <div><span><b>Phone : </b> {{ formatContact(auth()->user()->terminal->contact) }} </span>
        </div>
        @endif
    </div>
    @if($data['duplicate'] == 1)
        <div style="text-align: center; border:2px dashed black;"><h3>Duplicate Ticket</h3></div>
    @endif
    <div class="custinfo" id="custinfo">
        <hr/>
        <br>
        <div id="barcode-area">
            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Tracking Id</p>
                <p class="my-5 float-right">{{ "T".$data['tickets'][$key]['id']."@".$data['tickets'][$key]['invoice_id'] }}</p>
            </div>
            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Customer Name :</p>
                <p class="my-5 float-right">{{ ucfirst($data['tickets'][$key]->customer->name)}}</p>
            </div>
            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Seat No :</p>
                <p class="my-5 float-right">{{ $data['tickets'][$key]['seat_no'] }}</p>
            </div>
            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Bus Class:</p>
                <p class="my-5 float-right">{{ ucfirst($data['tickets'][$key]->busClass->name) }}</p>
            </div>
            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">From :</p>
                <p class="my-5 float-right">{{ ucfirst($data['tickets'][$key]->departure_city->name) }}</p>
            </div>
            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">To :</p>
                <p class="my-5 float-right">{{ ucfirst($data['tickets'][$key]->destination_city->name) }}</p>
            </div>

            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Departure Date :</p>
                <p class="my-5 float-right">{{ date('d/m/Y', strtotime($data['tickets'][$key]['acutal_time'])) }}</p>
            </div>

            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Departure Time :</p>
                <p class="my-5 float-right">{{ date('h:i A', strtotime($data['tickets'][$key]['acutal_time'])) }}</p>
            </div>

            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Booking Date :</p>
                <p class="my-5 float-right">{{ date('d/m/Y h:i A', strtotime($data['tickets'][$key]['booked_time'])) }}</p>
            </div>

            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Fare :</p>
                <p class="my-5 float-right">{{ $data['tickets'][$key]['seat_fare'] + $data['tickets'][$key]['schedule_discount'] + $data['tickets'][$key]['terminal_discount'] }}</p>
            </div>
            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Discount :</p>
                <p class="my-5 float-right">{{ ($data['tickets'][$key]['discount'] ?? 0) + $data['tickets'][$key]['schedule_discount'] + $data['tickets'][$key]['terminal_discount'] }}</p>
            </div>
            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Total Fare :</p>
                <p class="my-5 float-right">{{( $data['tickets'][$key]['seat_fare']) - ( $data['tickets'][$key]['discount'] ?? 0) }}</p>
            </div>
        </div>
        <br>
        <br>
        <hr>

        <div style="text-align: center;">
            <h3 style="font-weight: 900;"> Terms & Condition Applied!</h3>
            <p>{{ isset($data['format']->terms_condition) ? $data['format']->terms_condition : "Refreshment,WIFI upto 350MB, And MOD is Complimentary Refreshment,WIFI Bus will not drop passengers without Company TerminalBus will not drop" }}</p>

            <br>
            <p> &#169; {{isset($data['format']->footer_text) ? $data['format']->footer_text : "Rights Reserved by Kainat Travels"}}</p>
        </div>

        @if((isset($data['format']) && $data['format']->show_coupen == 0))
        @else
        <div style="page-break-before:always">&nbsp;</div>
        <div class="custinfo" id="custinfo">
            <div class="clear-both">
                <p class="font-weight-bold float-left">Seat No :</p>
                <p class="float-right">{{ $data['tickets'][$key]['seat_no'] }}</p>
            </div>
            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Bus Class:</p>
                <p class="my-5 float-right">{{ ucfirst($data['tickets'][$key]->busClass->name) }}</p>
            </div>
            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">From :</p>
                <p class="my-5 float-right">{{ ucfirst($data['tickets'][$key]->departure_city->name) }}</p>
            </div>
            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">To :</p>
                <p class="my-5 float-right">{{ ucfirst($data['tickets'][$key]->destination_city->name) }}</p>
            </div>
            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Departure Date :</p>
                <p class="my-5 float-right">{{ date('d/m/Y', strtotime($data['tickets'][$key]['date'])) }}</p>
            </div>
            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Departure Time :</p>
                <p class="my-5 float-right">{{ date('h:i A', strtotime($data['tickets'][$key]['acutal_time'])) }}</p>
            </div>
            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Customer Name :</p>
                <p class="my-5 float-right">{{ ucfirst($data['tickets'][$key]->customer->name)}}</p>
            </div>

            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Customer CNIC :</p>
                <p class="my-5 float-right">{{ formatCNIC($data['tickets'][$key]->customer->cnic)}}</p>
            </div>

            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Customer Contact :</p>
                <p class="my-5 float-right">{{ formatContact($data['tickets'][$key]->customer->contact)}}</p>
            </div>
        </div>
        @endif
    </div>
</div>
</body>
</html>
@endforeach
