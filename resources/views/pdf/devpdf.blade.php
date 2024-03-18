@foreach($data['tickets']  as $key => $single)
    <!DOCTYPE html>
<html>

<head>
    <script src="{{ asset('/assets/js/jquery.min.js') }}"></script>
    <!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
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
            /* margin-bottom: 10px; */
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
    <div class="companyname"><span>Kainat Travels</span></div>
    <div class="companyAddress">
        <span>{{ isset($data['format']->address) ? $data['format']->address : auth()->user()->terminal->address }}</span>
        <div><span><b>UAN(24/7) : </b>03-111-777-333</span></div>
        <div><span><b>Phone : </b> {{ isset($data['format']->contact) ?  formatContact($data['format']->contact) : formatContact(auth()->user()->terminal->contact) }}</span>
        </div>
    </div>
    @if($data['duplicate'] == 1)
        <div style="text-align: center; border:2px dashed black;"><h3>Duplicate Ticket</h3></div>
    @endif
    <div class="custinfo" id="custinfo">
        <div id="barcode-area">
            <img style="width: 100px !important;"
                 src="{{ asset('/Customers/Qrs/'.codeImage('Customer Name : '.ucfirst($data['tickets'][$key]->customer->name) . ' | ' . 'Customer CNIC : '. formatCNIC($data['tickets'][$key]->customer->cnic).' | ' . 'Customer Phone : '. formatContact($data['tickets'][$key]->customer->contact) .' | '.'Seat No : ' . $data['tickets'][$key]['seat_no'] .' | '.'Bus Class : ' .ucfirst($data['tickets'][$key]->seatClass->name) . ' | '. 'From : ' . ucfirst($data['tickets'][$key]->departure_city->name) . ' | ' . 'To : ' . ucfirst($data['tickets'][$key]->destination_city->name) . ' | ' . 'Departure Date : ' . date('d/m/Y', strtotime($data['tickets'][$key]['date'])) . ' | '. 'Departure Time : ' . date('H:i A', strtotime($data['tickets'][$key]->schedule->time)) . ' | ' . ' Booking Date Time : '.  date('d/m/Y H:i A', strtotime($data['tickets'][$key]['created_at'])) . ' | ' . 'Fare :'. $data['tickets'][$key]['seat_fare'])) }}"
                 class="rounded"/>

            <div class="clear-both">
                <p class="font-weight-bold float-left">Customer Name :</p>
                <p class="float-right">{{ ucfirst($data['tickets'][$key]->customer->name)}}</p>
            </div>
            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Seat No :</p>
                <p class="my-5 float-right">{{ $data['tickets'][$key]['seat_no'] }}</p>
            </div>
            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Bus Class:</p>
                <p class="my-5 float-right">{{ ucfirst($data['tickets'][$key]->seatClass->name) }}</p>
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
                <p class="my-5 float-right">{{ date('H:i A', strtotime($data['tickets'][$key]['acutal_time'])) }}</p>
            </div>

            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Booking Date :</p>
                <p class="my-5 float-right">{{ date('d/m/Y H:i A', strtotime($data['tickets'][$key]['created_at'])) }}</p>
            </div>

            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Fare :</p>
                <p class="my-5 float-right">{{ $data['tickets'][$key]['seat_fare'] }}</p>
            </div>
            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Discount :</p>
                <p class="my-5 float-right">{{ $data['tickets'][$key]['discount'] ?? 0 }}</p>
            </div>
            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Total Fare :</p>
                <p class="my-5 float-right">{{( $data['tickets'][$key]['seat_fare']) - ( $data['tickets'][$key]['discount'] ?? 0) }}</p>
            </div>
        </div>

        <div style="text-align: center;">
            <h6> Terms & Condition Applied!</h6>
            @if(isset($data['format']->terms_condition))
            <p>$data['format']->terms_condition</p>
            @else
            <p class="my-5">1:Arrive terminal 30 before departure Bus will not delayed for passenger.</p>
            <p class="my-5">2: Per person allowed luggage is upto 30kg only,Commercial or additional luggage will booked additionally.</p>
            <p class="my-5">3: Wifi upto 350mb,Refreshment/Food & Multimedia services are Complementary & non claimable.</p>
            <p class="my-5">4:For passenger safety Bus will not pick/drop passengers from Roadside or outside Company Terminal</p>
            <p class="my-5">5: Keep your personal belongings Safe Company is not responsible for any loss or damage.</p>
            @endif

         
            <p> &#169; Rights Reserved by Kainat Travels</p>
        </div>

        <div style="page-break-before:always">&nbsp;</div>
        <div class="custinfo" id="custinfo">
            <div class="clear-both">
                <p class="font-weight-bold float-left">Seat No :</p>
                <p class="float-right">{{ $data['tickets'][$key]['seat_no'] }}</p>
            </div>
            <div class="clear-both">
                <p class="my-5 font-weight-bold float-left">Bus Class:</p>
                <p class="my-5 float-right">{{ ucfirst($data['tickets'][$key]->seatClass->name) }}</p>
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
                <p class="my-5 float-right">{{ date('H:i A', strtotime($data['tickets'][$key]['acutal_time'])) }}</p>
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
    </div>
</div>
</body>
</html>
@endforeach
