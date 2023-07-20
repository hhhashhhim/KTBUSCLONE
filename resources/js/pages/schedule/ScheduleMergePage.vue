<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h4>Merge Buses Record</h4>
                            <!-- <div class="card-header-action">
                                <a href="#" :data-target="'#' + formID" data-toggle="modal" class="btn btn-primary"
                                   @click="clearForm()">
                                    Merge Buses Record
                                </a>
                            </div> -->
                        </div>
                        <div class="card-body">
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table
                                                    class="table table-striped table-hover"
                                                    id="merge_table"
                                                >
                                                    <thead>
                                                    <tr>
                                                        <th>Bus Number</th>
                                                        <th>Departure Schedule</th>
                                                        <th>Departure Date</th>
                                                        <th>Return Date</th>
                                                        <th>Return Schedule</th>
                                                        <th>Merge Sale</th>
                                                        <th v-if="checkForSubmenuButtons('add-expense')">Expense</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(merge, i) in merges" :key="i">
                                                        <td>
                                                            {{ merge.bus.bus_number }}
                                                        </td>
                                                        <td class="bg-blue-grey">
                                                            {{ merge.closing[0].schedule.name }}
                                                        </td>
                                                        <td class="bg-blue-grey">
                                                            {{ merge.schedule_departure_date }}
                                                        </td>
                                                        <td class="bg-dark-gray">
                                                            {{ merge.closing[1].schedule.name }}
                                                        </td>
                                                        <td class="bg-dark-gray">
                                                            {{ merge.schedule_return_date }}
                                                        </td>
                                                        <td class="bg-danger">
                                                            {{ (merge.seat_fare) + (merge.elt) + (merge.refund) - (merge.discount) - (merge.commission) }}
                                                        </td>
                                                        <td v-if="checkForSubmenuButtons('add-expense')">
                                                            <router-link target="_blank" v-if="checkForSubmenuButtons('add-expense')"
                                                                         class="btn btn-success mx-2"
                                                                         :to="{ name:'expense-page', params: { id:merge.id }}"
                                                                         title="Add Expense">
                                                                <i class="fas fa-plus"></i>
                                                            </router-link>
                                                            <router-link target="_blank"
                                                                         v-if="checkForSubmenuButtons('add-expense')"
                                                                         class="btn btn-success mx-2"
                                                                         :to="{ name:'header-link-page', params: { id:merge.id }}"
                                                                         title="header link">
                                                                Link Headers
                                                            </router-link>
                                                        </td>
                                                    </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- END TABLE -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
import Add from "../../components/Add.vue";
import Edit from "../../components/Edit.vue";
import Multiselect from '@vueform/multiselect'
// import Delete from "../../components/Delete.vue";

import {mapGetters} from "vuex";

export default {
    name: "merges",
    components: {
        Add,
        Edit,
        Multiselect
        // Delete,
    },
    data() {
        return {
            loading: false,
            validationErrors: "",
            merges: [],
            // formID: "schedule_closing_form",
            // editFormID: "edit_schedule_closing_form",
            success: false,
            errors: false,
            permissions: [],
        };
    },
    async created() {
        const currentRouteName = this.$route.name;
        if (currentRouteName == 'booking-page') {
            window.addEventListener('keydown', this.enterKey);
            window.addEventListener('keydown', this.altM);
        } else {
            window.removeEventListener('keydown', this.enterKey);
            window.removeEventListener('keydown', this.altM);
        }

        this.fetchData();
        this.permissions = this.$store.state.permissions;
    },

    methods: {
        clearForm: function () {
            this.data = {};
        },
        async fetchData() {
            const res = await this.callApi("post", "booking/close/schedule/merges");
            if (res.status == 200) {
                this.merges = res.data.merges;
            } else {
                console.log(res);
            }
            setTimeout(() => {
                $('#merge_table').DataTable({
                    'order': []
                });
            }, 300);
        },
    },
    computed: {
        ...mapGetters(["getDeletingObj"]),
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.buses.splice(obj.index, 1);
                $('#merge_table').DataTable().destroy();
            }
        },
    },
};
</script>
<style src="@vueform/multiselect/themes/default.css"></style>

