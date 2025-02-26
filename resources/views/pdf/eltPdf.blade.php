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
            line-height: 1.0;
        }

        .companyname {
            font-weight: 900;
            font-size: 14pt;
            text-transform: uppercase;
            margin-bottom: 10px;
            text-align: center;
            font-family: Franklin Gothic Medium, serif;

        }

        .companyAddress {
            font-weight: 400;
            font-size: 10pt;
            text-align: center;
            font-family: Franklin Gothic Medium, serif;

        }

        #barcode-area {
            text-align: center;

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
        }
        hr {
            border: 2px dashed black;
        }
    </style>
    <script type="text/javascript">

        $(document).ready(function () {
            window.print();
        });

        setTimeout(function(){
            window.close() ;
        }, 1000); //Time before execution
    </script>
    <title>Print ELT</title>
</head>
<body>
    <div id="info">
        <div class="companyname" style="margin-bottom: 15px;"><span>ELT RECIEPT</span></div>
        <div class="custinfo" id="custinfo">
            <div>
                <p class="font-weight-bold float-left">Customer Name :</p>
                <p class="float-right">{{ ucfirst($data['elt']->customer->name) }}</p>
            </div>
            <div class="clear-both">
                <p class="font-weight-bold float-left">Customer Contact :</p>
                <p class="float-right">{{ formatContact($data['elt']->customer->contact) }}</p>
            </div>
            <br>
            <br>
            <br>
            <hr>
            <br>
            <div class="clear-both">
                <p class="font-weight-bold float-left">Seat No :</p>
                <p class="float-right">{{ $data['elt']['seat_no'] }}</p>
            </div>
            <div class="clear-both">
                <p class="font-weight-bold float-left">From :</p>
                <p class="float-right">{{ ucfirst($data['elt']->departure->name) }}</p>
            </div>
            <div class="clear-both">
                <p class="font-weight-bold float-left">To :</p>
                <p class="float-right">{{ ucfirst($data['elt']->destination->name) }}</p>
            </div>
            <div class="clear-both">
                <p class="font-weight-bold float-left">Bus Class :</p>
                <p class="float-right">{{ ucfirst($data['elt']->ticket->seatClass->name??'NA') }}</p>
            </div>
            <div class="clear-both">
                <p class="font-weight-bold float-left">Booking Date :</p>
                <p class="float-right">{{ date('d/m/Y H:i A', strtotime($data['elt']->ticket->created_at))  }}</p>
            </div>

            <div class="clear-both">
                <p class="font-weight-bold float-left">Departure Date :</p>
                <p class="float-right">{{ date('d/m/Y', strtotime($data['elt']->ticket->date)) }}</p>
            </div>

            <div class="clear-both">
                <p class="font-weight-bold float-left">Departure Time :</p>
                <p class="float-right">{{ date('H:i A', strtotime($data['elt']->schedule->time)) }}</p>
            </div>
            <div class="clear-both">
                <p class="font-weight-bold float-left">ELT Weight:</p>
                <p class="float-right">{{ $data['elt']['elt_weight'] }} KG</p>
            </div>
            <div class="clear-both">
                <p class="font-weight-bold float-left">ELT Price:</p>
                <p class="float-right">RS {{ $data['elt']['elt_price'] }}</p>
            </div>
            <br>
            <br>
            <br>
            <hr>
            <br>
            <div class="clear-both">
                <p class="font-weight-bold float-left">Sub Total:</p>
                <p class="float-right">RS {{ $data['elt']['elt_price'] }}</p>
            </div>

            <div class="clear-both">
                <p class="font-weight-bold float-left">Seat Fare :</p>
                <p class="float-right">RS {{ $data['elt']->ticket->seat_fare }}</p>
            </div>
            <div class="clear-both">
                <p class="font-weight-bold float-left">Total :</p>
                <p class="float-right">RS {{ $data['elt']['elt_price'] + $data['elt']->ticket->seat_fare }}</p>
            </div>
            <br>
            <br>
            <br>
            <hr />
            <br>
{{--            {{dd($data['format'])}}--}}

            <div class="companyname" style="margin-bottom: 15px;"><span>{{isset($data['format']->name) ? $data['format']->name : "Kainat Travels"}}</span></div>
            <div class="companyAddress"><span>{{ auth()->user()->terminal->address }}</span>
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
                <div class="fa fa-qrcode" id="barcode-area">
                    <img style="width: 130px !important;"
                         src="{{ asset('/Customers/Elt/'.codeImageElt('Customer Name : '.ucfirst($data['elt']->customer->name)  . ' | ' . 'Customer CNIC : '. formatCNIC($data['elt']->customer->cnic).' | ' . 'Customer Phone : '. formatContact($data['elt']->customer->contact)  .' | '.'Seat No : ' . $data['elt']['seat_no'] .' | '.'Bus Class : ' .ucfirst($data['elt']->ticket->seatClass->name??'NA') . ' | '. 'From : ' . ucfirst($data['elt']->departure->name) . ' | ' . 'To : ' . ucfirst($data['elt']->destination->name)  . ' | ' . 'Departure Date : ' . date('d/m/Y', strtotime($data['elt']->ticket->date)) . ' | '. 'Departure Time : ' .  date('H:i A', strtotime($data['elt']->schedule->time)) . ' | ' . ' Booking Date Time : '. date('d/m/Y H:i A', strtotime($data['elt']->ticket->created_at))  . ' | ' . 'Seat Fare : '. $data['elt']->ticket->seat_fare. ' | ' . 'Elt Price : '. $data['elt']['elt_price'] . ' | ' . 'Total :'. ($data['elt']['elt_price'] + $data['elt']->ticket->seat_fare) )) }}"
                         class="rounded"/>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
