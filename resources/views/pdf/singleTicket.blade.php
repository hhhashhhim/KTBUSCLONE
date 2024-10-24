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
        #background {
            position: absolute;
            z-index: 0;
            background: white;
            display: block;
            min-height: 50%; 
            min-width: 100%; /* Ensure the container is wide enough */
            color: yellow;
        }

        #info {
            position: relative; /* Set to relative or another value higher than 0 */
            z-index: 1; /* Higher z-index to make sure content is on top */
        }

        #bg-text {
            color: lightgrey;
            font-size: 70px;
            white-space: nowrap; /* Ensures "E-Ticket" stays on one line */
            position: absolute;
            top: 25%; /* Position the top as needed */
            left: 50%; /* Position the left at the center */
            transform: translate(-50%, -50%) rotate(-45deg); /* Rotate the text by 45 degrees */
            -webkit-transform: translate(-50%, -50%) rotate(-45deg); /* For older browsers */
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
<!-- $data['tickets'] -->
<body>
<div id="background">
    <p id="bg-text">E-Ticket</p>
</div>
<div id="info">
    <div class="companyname"><span>{{isset($data['format']->name) ? $data['format']->name : "Kainat Travels"}}</span></div>
    <div class="companyAddress">
        <span>{{ auth()->user()->terminal->address }}</span>
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
    <div class="custinfo" id="custinfo">
        <hr/>
        <br>
        <div id="barcode-area">
            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Tracking Id</p>
                <p class="my-5 float-right">{{ "@".$data['tickets'][0]['invoice_id'] }}</p>
            </div>
            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Customer Name :</p>
                <p class="my-5 float-right">{{ ucfirst($data['tickets'][0]->customer->name)}}</p>
            </div>
            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Seat No :</p>
                <p class="my-5 float-right">{{ implode(",", collect($data['tickets'])->pluck('seat_no')->toArray()) }}</p>
            </div>
            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Bus Class:</p>
                <p class="my-5 float-right">{{ ucfirst($data['tickets'][0]->busClass->name) }}</p>
            </div>
            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">From :</p>
                <p class="my-5 float-right">{{ ucfirst($data['tickets'][0]->departure_city->name) }}</p>
            </div>
            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">To :</p>
                <p class="my-5 float-right">{{ ucfirst($data['tickets'][0]->destination_city->name) }}</p>
            </div>

            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Departure Date :</p>
                <p class="my-5 float-right">{{ date('d/m/Y', strtotime($data['tickets'][0]['acutal_time'])) }}</p>
            </div>

            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Departure Time :</p>
                <p class="my-5 float-right">{{ date('h:i A', strtotime($data['tickets'][0]['acutal_time'])) }}</p>
            </div>

            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Booking Date :</p>
                <p class="my-5 float-right">{{ date('d/m/Y h:i A', strtotime($data['tickets'][0]['booked_time'])) }}</p>
            </div>

            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Fare :</p>
                <p class="my-5 float-right">{{ collect($data['tickets'])->sum('seat_fare') + collect($data['tickets'])->sum('schedule_discount') + collect($data['tickets'])->sum('terminal_discount') }}</p>
            </div>
            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Discount :</p>
                <p class="my-5 float-right">{{ (collect($data['tickets'])->sum('discount') ?? 0) + collect($data['tickets'])->sum('schedule_discount') + collect($data['tickets'])->sum('terminal_discount') }}</p>
            </div>
            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Total Fare :</p>
                <p class="my-5 float-right">{{( collect($data['tickets'])->sum('seat_fare') - ( $data['tickets'])->sum('discount') ?? 0) }}</p>
            </div>
        </div>
        <br>
        <br>
        <hr>

        <div style="text-align: center;">
            <h3 style="font-weight: 900;"> Terms & Condition Applied!</h3>
            <p>{{ isset($data['format']->terms_condition) ? $data['format']->terms_condition : "Refreshment,WIFI upto 350MB, And MOD is Complimentary Refreshment,WIFI Bus will not drop passengers without Company TerminalBus will not drop" }}</p>
            <p> &#169; {{isset($data['format']->footer_text) ? $data['format']->footer_text : "Rights Reserved by Kainat Travels"}}</p>
        </div>
    </div>
</div>
</body>
</html>
