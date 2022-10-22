<template>
  <section class="section">
    <BasicPopup
      :heading="'Reschedule Seats'"
      :errors="this.validationErrors"
      :success="success"
      :formID="formID"
    >
      <div class="row">
        <div class="col-md-12 table-responsive">
          <table class="table table-striped table-hover" id="booking-table">
            <thead>
              <tr>
                <th>Sr No.</th>
                <th>Customer Name</th>
                <th>CNIC Number</th>
                <th>Cell Number</th>
                <th>Date</th>
                <th>Ticket Booked By</th>
                <th>No. of Tickets</th>
                <!-- <th>Action</th> -->
              </tr>
            </thead>
            <tbody>
              <tr v-for="(booking, i) in details" :key="i">
                <td>{{ parseInt(i) }}</td>
                <td>{{ booking.customer?booking.customer.name:"N/A" }}</td>
                <td>{{ cnicFormat(booking.customer.cnic) }}</td>
                <td>{{ booking.customer.contact }}</td>
                <td>{{ booking.date }}</td>
                <td>{{ booking.added_by?booking.added_by.name:"N/A" }}</td>
                <td>{{ booking.count }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </BasicPopup>
  </section>
</template>
<script>
import BasicPopup from "../../../components/BasicPopup.vue";

export default {
  name: "DetailsModal",
  props: ["formID", "details"],
  components: {
    BasicPopup,
  },
  methods:{
    cnicFormat: function (string) {
      return string.replace(/(\d{5})(\d{7})(\d{1})/, "$1-$2-$3");
    },
  }
};
</script>