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
            font-size: 7pt;
            font-family: Verdana, Arial, sans-serif;
        }


        #info {
            /*width: 58mm;*/
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
            font-family: sans-serif, Verdana, Arial;
        }

        .companyAddress {
            font-weight: 400;
            font-size: 12pt;
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
    </style>
    <title>Print Ticket</title>
</head>
<body>
<div id="info">
    <div class="companyname"><span>Kainat Travels</span></div>
    <div class="companyAddress">

        <span>Main Pirwadhai Mor Peshawar Road Rawalpindi</span>
        <div><span><b>UAN(24/7):</b>  03-111-777-333</span></div>
        <div><span><b>Phone:</b>  03108886286</span></div>
    </div>
    <div class="custinfo" id="custinfo">
        <p style="width:100%;  text-align: center; margin:0">.......................................................................................................</p>
        <div class="fa fa-qrcode" id="barcode-area">
            <img
                src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAGQAAABkCAYAAABw4pVUAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAVkSURBVHhe7ZFBCiQxDAP3/5/eZWAFprDaybQGckiBDpLdwk3+/L0cxX2Qw7gPchj3QQ7jPshh3Ac5jPsgh3Ef5DDugxzGfZDDuA9yGPdBDuM+yGHcBzmM+yCHcR/kMO6DHEbkQf78+fOVHJzXb7rc4fadRDdbUYJIS3fcihyc12+63OH2nUQ3W1GCSMvuQdyfvFC+KkFPdvfJ7v4TkZa3PzB5oXxVgp7s7pPd/SciLTxInhL0E7WjfreaU4S585Sgf0OkxR1ICfqJ2lG/W80pwtx5StC/IdLiDqSE85SYvFBO7cLvaleVoH9DpMUdSAnnKTF5oZzahd/VripB/4ZIizuQEvTC5Q7tu++Y191OwnlK0L8h0uIOpAS9cLlD++475nW3k3CeEvRviLTsHsR9+dVcuDm9WM3dnmN3/4lIy9sfkF/NhZvTi9Xc7Tl295+ItOigXYlT/a4SRFq641YkTvW7SpBpCdH95Efk2zlz6gTugxSdQOSK7uc6iW5WJeiJ23f5xPTd5BNE2nTYJNHNqgQ9cfsun5i+m3yCSJs7TPnu3PlJhHnd3RHpdj5KEGlxB9Vjd+bOTyLM6+6OSLfzUYJIizuIuTxFppwSLiduvvvdtP8NkTZ3GHN5ikw5JVxO3Hz3u2n/G6JtOtAdyrzu1py4PXpRd+vceSfS7XyUJNo2Hcq87tacuD16UXfr3Hkn0u18lCTSxsPqsTUXzOtulXB+N/+W2ll76BNE2tyhzAXzulslnN/Nv6V21h76BJG26VB6x+reKuyT35WY8gSRlulAesfq3irsk9+VmPIEub8v1KOrxKqnCPO6+5RL35Lq6ci2/YcHS2LVU4R53X3KpW9J9XRk2/7Dg6VVpu9256siu3mCn7TqYGqV6bvd+arIbp7gN63/0eH8AeaScLmD+9RbXE+qv5JtAzqYhzOXhMsd3Kfe4npS/ZVImw7jgfSkftNJdLOPHG6P+arINH9DpM0dSE/qN51EN/vI4faYr4pM8zdE2qbD6vGdVum+rRLOMyduzlyeeYJI23RYPb7TKt23VcJ55sTNmcszTxBp44GTHG7P5Q63Vzue5sJ5KkmkrTvySQ6353KH26sdT3PhPJUk2sYDnXcSq36SoBd1t5sL7jklyLT8h4c57yRW/SRBL+puNxfcc0oQaZkOcnPlTm9hT+3ekZjyBJGW6SA3V+70FvbU7h2JKU8QaXEHTrkkulkVWc13vUN7q/vfEGnlgfXop1wS3ayKrOa73qG91f1viLTywHp0l5NUPjH1UaKbVSWJtPGwemyXk1Q+MfVRoptVJYm08TDnnUQ3+2ii++YjQS/q7o5+SaSdhzrvJLrZRxPdNx8JelF3d/RLftu+CH/UeWqCe/XbTg7O6zdVCTItL+EPOU9NcK9+28nBef2mKkGkpTtuRcLlhHsSYe48JZxn/gsi7Tx4VcLlhHsSYe48JZxn/gsi7buHun3lnLt8lel7l4v67ZMSRFp2D3L7yjl3+SrT9y4X9dsnJYi08KB6ZJVwnhLOr2qi+6aTY5rvEGnhQfKUcJ4Szq9qovumk2Oa7xBp4UHylJj8t6z2KqdIt/OkBJEWHlSPrBKT/5bVXuUU6XaelCDSwoPqkVXC+dVccE6J1dxJuDxJpJUH1qOrhPOrueCcEqu5k3B5kkjr7oHcd565cLmYvqOE806/INK6eyD3nWcuXC6m7yjhvNMviLR2x65ITLmjfrMih9vb9QkibTpsV2LKHfWbFTnc3q5PkG27vOY+yGHcBzmM+yCHcR/kMO6DHMZ9kMO4D3IY90EO4z7IYdwHOYz7IIdxH+Qw7oMcxn2Qw7gPchj3QY7i799/1PC6TV6gwP0AAAAASUVORK5CYII="
                class="rounded"/>
        </div>
        <div>
            <p class="font-weight-bold float-left">Name :</p>
            <p class="float-right">Malik ajay </p>
        </div>
        <div class="clear-both">
            <p class="font-weight-bold float-left">Seat No :</p>
            <p class="float-right">1</p>
        </div>
        <div class="clear-both">
            <p class="font-weight-bold float-left">Bus No :</p>
            <p class="float-right">Malik ajay </p>
        </div>
        <div class="clear-both"></div>
        <p style="width:100%;  text-align: center; margin:0">.......................................................................................................</p>

        <div>
            <p class="font-weight-bold float-left">From : &nbsp; <span>City Name</span></p>
            <p class="font-weight-bold float-right">To : &nbsp; <span>Malik ajay</span> </p>
        </div>
        <div class="clear-both">
            <p class="font-weight-bold float-left">Departure Date :</p>
            <p class="float-right">22/11/2022</p>
        </div>
        <div class="clear-both">
            <p class="font-weight-bold float-left">Departure Time :</p>
            <p class="float-right">21:45 PM </p>
        </div>
        <div class="clear-both">
            <p class="font-weight-bold float-left">Booking Date :</p>
            <p class="float-right">22/11/2022 11:32</p>
        </div>
        <div class="clear-both"></div>
        <p style="width:100%;  text-align: center; margin:0">.......................................................................................................</p>

        <div>
            <p class="font-weight-bold float-left">Fare : </p>
            <p class="float-right">900</p>
        </div>
        <div class="clear-both"></div>
    </div>
</div>

<div>
    <div style=" margin-left: 5px;  text-align: center;" >
        <h4 style="text-decoration: underline;"><b>Terms & Conditions Applied</b></h4>
        <h5><b>Refreshment,WIFI upto 350MB And MOD is Complimentary</b></h5>
        <h5><b>Bus will not drop passenger without Company Terminal</b></h5>
        <h4><b>&copy; Rights Reserved By Kainat Travels</b></h4>
    </div>
</div>
<p style="width:100%;  text-align: center; margin:0">...........................................................................</p>
<p style="width:100%;  text-align: center; margin:0">...........................................................................</p>
<p style="page-break-before: always"></p>

<div class="custinfoverify" id="custinfoverify">

    <p style="width:100%;  text-align: center; margin:0">.......................................................................................................</p>

    <div style="width:100%">
        <p style="margin-bottom: 0" class="font-weight-bold float-left">Seat # : &nbsp;<span>1 &nbsp;&nbsp;&nbsp;</span></p>
        <p style="margin-bottom: 0" class="font-weight-bold float-right">Bus No : &nbsp;<span>Bus#&nbsp;&nbsp; </span></p>
    </div>
    <div style="width:100%" class="clear-both">
        <p style="margin-bottom: 0" class="font-weight-bold float-left" >From : <span>Rawalpindi</span></p>
        <p style="margin-bottom: 0" class="font-weight-bold float-right">To :  <span>Faisalabad</span></p>
    </div>

    <div class="clear-both">
        <p style="margin-bottom: 0" class="font-weight-bold float-left" >Departure Date :</p>
        <p style="margin-bottom: 0" class="font-weight-bold float-right">22/11/2022</p>
    </div>
    <div class="clear-both"></div>
    <p style="width:100%;  text-align: center; margin:0">.......................................................................................................</p>
    <div>
        <p class="font-weight-bold float-left" style="margin-bottom: 0">Ticket Holder Name :</p>
        <p class="float-right" style="margin-bottom: 0">Malik ajay </p>
    </div>
    <div class="clear-both">
        <p class="font-weight-bold float-left" style="margin-bottom: 0">CNIC Number :</p>
        <p class="float-right" style="margin-bottom: 0">3320216516699 </p>
    </div>
    <div class="clear-both">
        <p style="margin-bottom: 0" class="font-weight-bold float-left">Contact # : </p>
        <p style="margin-bottom: 0" class="float-right">03157053558</p>
    </div>

</div>


<script type="text/javascript">
    $(document).ready(function () {
        window.print();
    });

</script>
</body>
</html>
