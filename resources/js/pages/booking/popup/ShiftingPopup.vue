<template>
  <section class="section">
    <BasicPopup
      :heading="'Booking Options'"
      :errors="this.validationErrors"
      :formID="formID"
    >
      <div id="accordion">
        <div class="accordion">
          <div
            class="accordion-header py-4 collapsed"
            role="button"
            data-toggle="collapse"
            data-target="#panel-body-1"
            aria-expanded="false"
          >
            <h3>Make Partial Seat</h3>
          </div>
          <div
            class="accordion-body collapse"
            id="panel-body-1"
            data-parent="#accordion"
            style=""
          >
          
          
            <select class="form-control rounded-0" v-model="partialSchedule">
                <option value="0" selected>Select City</option>
                <option v-for="(city, i) in cities" :value="city.id" :key="i">
                    {{ city.name }}
                </option>
            </select>
          
          </div>
        </div>
        <div class="accordion">
          <div
            class="accordion-header py-4 collapsed"
            role="button"
            data-toggle="collapse"
            data-target="#panel-body-2"
            aria-expanded="false"
          >
            <h3>Over Issue Seat</h3>
          </div>
          <div
            class="accordion-body collapse"
            id="panel-body-2"
            data-parent="#accordion"
            style=""
          >
            Over Issue Section
          </div>
        </div>
        <div class="accordion">
          <div
            class="accordion-header py-4 collapsed"
            role="button"
            data-toggle="collapse"
            data-target="#panel-body-3"
            aria-expanded="false"
          >
            <h3>Shift Seat Booking</h3>
          </div>
          <div
            class="accordion-body collapse"
            id="panel-body-3"
            data-parent="#accordion"
            style=""
          >
            Shift Seat Booking Section
          </div>
        </div>
      </div>
    </BasicPopup>
  </section>
</template>
<script>
import BasicPopup from "../../../components/BasicPopup.vue";

export default {
  name: "BookingOptionsPopup",
  props: ["formID","seatId"],
  components: {
    BasicPopup,
  },
  data() {
    return {
      partialSchedule:0,
    };
  },
  watch:{
    seatId(newValue){
      // const resCnic = await this.callApi("post", "schedule/booking-options", {cnicNumber: this.addForm.customerCNIC});
      this.addForm.contact = resCnic.data.contact;
      this.addForm.customerName = resCnic.data.name;
    }
  }
};
</script>
