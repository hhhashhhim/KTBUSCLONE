<template>
  <section class="section">
    <BasicPopup
      :heading="'Booking Options'"
      :errors="this.validationErrors"
      :formID="formID"
    >
      
      
      
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
