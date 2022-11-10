<template>
    <section class="section">
        <BasicPopup
            :heading="'View Booking Details'"
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
                            <th>Remarks</th>
                            <th>Cell Number</th>
                            <th>CNIC Number</th>
                            <th>Seat No</th>
                            <th>Ticket Booked By</th>
                            <th>Booking Date</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(booking, i) in details" :key="i">
                            <td>{{ parseInt(i) + 1 }}</td>
                            <td>{{ booking.customer.name }}</td>
                            <td>{{ booking.remarks != null ? booking.remarks : "N/A" }}</td>
                            <td>{{ phoneFormat(booking.customer.contact) }}</td>
                            <td>{{ cnicFormat(booking.customer.cnic) }}</td>
                            <td>{{ booking.seat_no}}</td>
                            <td>{{ booking.added_by.name }}</td>
                            <td>{{ booking.date }}</td>
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
    props: ["formID", "details", "deleteFormID"],
    components: {
        BasicPopup,
    },
    methods: {
        cnicFormat: function (string) {
            return string.replace(/(\d{5})(\d{7})(\d{1})/, "$1-$2-$3");
        },
        phoneFormat: function (string) {
            return string.replace(/(\d{4})(\d{7})/, "$1-$2");
        },

    },
};
</script>
