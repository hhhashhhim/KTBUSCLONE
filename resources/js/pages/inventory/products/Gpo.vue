<template>
    <div>
      <div style="page-break-before:always">&nbsp;</div>
      <div id="info">
        <div class="companyname"><span>Kainat Travels</span></div>
        <div class="companyAddress">
          <span>123</span>
          <div><span><b>UAN(24/7) : </b>03-111-777-333</span></div>
          <div><span><b>Phone : </b> 03067001600</span></div>
        </div>
  
        <div class="custinfo" id="custinfo">
          <div id="barcode-area">
            <div class="clear-both">
              <p class="font-weight-bold float-left">Customer Name :</p>
              <p class="float-right">TEST</p>
            </div>
            <div class="clear-both">
              <p class="my-5 font-weight-bold float-left">Seat No :</p>
              <p class="my-5 float-right">123</p>
            </div>
            <div class="clear-both">
              <p class="my-5 font-weight-bold float-left">Bus Class:</p>
              <p class="my-5 float-right">TEST</p>
            </div>
            <div class="clear-both">
              <p class="my-5 font-weight-bold float-left">From :</p>
              <p class="my-5 float-right">Fsd</p>
            </div>
            <div class="clear-both">
              <p class="my-5 font-weight-bold float-left">To :</p>
              <p class="my-5 float-right">Lhr</p>
            </div>
            <div class="clear-both">
              <p class="my-5 font-weight-bold float-left">Departure Date :</p>
              <p class="my-5 float-right">12 Mar 25</p>
            </div>
            <div class="clear-both">
              <p class="my-5 font-weight-bold float-left">Departure Time :</p>
              <p class="my-5 float-right">12 AM</p>
            </div>
            <div class="clear-both">
              <p class="my-5 font-weight-bold float-left">Booking Date :</p>
              <p class="my-5 float-right">12 Mar 2025</p>
            </div>
            <div class="clear-both">
              <p class="my-5 font-weight-bold float-left">Fare :</p>
              <p class="my-5 float-right">200.00</p>
            </div>
            <div class="clear-both">
              <p class="my-5 font-weight-bold float-left">Discount :</p>
              <p class="my-5 float-right">100.00</p>
            </div>
            <div class="clear-both">
              <p class="my-5 font-weight-bold float-left">Total Fare :</p>
              <p class="my-5 float-right">20100.00</p>
            </div>
          </div>
  
          <div style="text-align: center;">
            <h6> Terms & Condition Applied!</h6>
            <p>1: Arrive at the terminal 30 mins before departure. Bus will not be delayed.</p>
            <p>2: Allowed luggage is up to 30kg; extra luggage will be charged.</p>
            <p>3: WiFi up to 350MB, refreshments, and multimedia services are non-claimable.</p>
            <p>4: Bus will not pick/drop passengers outside terminals.</p>
            <p>5: Keep personal belongings safe; the company is not responsible for loss or damage.</p>
            <p> &#169; Rights Reserved by Kainat Travels</p>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  export default {
    mounted() {
      window.print();
    }
  };
  </script>
  
  <style scoped>
  @page {
    size: 72mm 210mm;
    transform: rotate(-90deg);
    padding: 0;
    margin: 10px 8px 10px 8px;
  }   
  
  body {
    margin: 10px 8px;
    font-size: 7pt;
    font-family: "Franklin Gothic Medium", serif;
  }
  
  .companyname {
    font-weight: 900;
    font-size: 16pt;
    text-transform: uppercase;
    text-align: center;
  }
  
  .companyAddress {
    font-weight: 400;
    font-size: 12pt;
    margin-bottom: 10px;
    text-align: center;
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
  
  .font-weight-bold {
    font-weight: 700;
  }
  
  .my-5 {
    margin-bottom: 0;
  }
  </style>
  