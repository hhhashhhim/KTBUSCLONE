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
                            <div class="row px-2 mb-4">
                                    <div class="col-md-4">
                                        <label for="terminalFilter">Select Bus</label>
                                        <select id="terminalFilter" class="form-control"
                                                v-model="filterData.bus_number"
                                                @change="fetchMerges()"
                                                >
                                            <option value="">Select Bus</option>
                                            <option v-for="(bus, i) in buses" :key="i"
                                                    :value="bus.id">
                                                {{ bus.bus_number }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="fromDate">From Date</label>
                                        <input id="fromDate" type="date" class="form-control"
                                                v-model="filterData.from_date"
                                                @change="fetchMerges()"
                                                >
                                    </div>
                                    <div class="col-md-4">
                                        <label for="fromDate">To Date</label>
                                        <input id="fromDate" type="date" class="form-control"
                                                v-model="filterData.to_date"
                                                @change="fetchMerges()"
                                                >
                                    </div>
                                </div>
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <div v-if="tableLoading">
                                                    <img class="loading-spinner" :src="$store.state.main_url + 'assets/img/loading-spinner.gif'">
                                                </div>
                                                <div v-else>
                                                    <div class="d-flex justify-content-end">
                                                        <form :action="$store.state.api_url + 'api/web/v1/booking/close/schedule/merges/pdf'" method="POST" ref=""
                                                        target="_blank">
                                                            <input type="hidden" name="token" :value="this.$store.state.token">
                                                            <input type="hidden" name="bus_number" :value="this.filterData.bus_number">
                                                            <input type="hidden" name="from_date" :value="this.filterData.from_date">
                                                            <input type="hidden" name="to_date" :value="this.filterData.to_date">
                                                            <input type="submit" value="Print" class="btn btn-dark">
                                                        </form>
                                                    </div>
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
                                                            <th>Closing Date</th>
                                                            <th>Merge Sale</th>
                                                            <th>Merge Expense</th>
                                                            <th>Net Sale</th>
                                                            <th width="200px" v-if="checkForSubmenuButtons('add-expense')">Action</th>
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
                                                            <td>
                                                                {{ merge.closing_date??"N/A" }}
                                                            </td>
                                                            <td>
                                                                {{ (merge.seat_fare) + (merge.elt) + (merge.refund) - (merge.discount) - (merge.commission) }}
                                                            </td>
                                                            <td>
                                                                {{ parseInt(merge.expenses_sum_amount) }}
                                                            </td>
                                                            <td class="bg-danger">
                                                                {{ (merge.seat_fare) + (merge.elt) + (merge.refund) - (merge.discount) - (merge.commission) - (merge.expenses_sum_amount) }}
                                                            </td>
                                                            <td v-if="checkForSubmenuButtons('add-expense')">
                                                                <router-link target="_blank" v-if="checkForSubmenuButtons('add-expense')"
                                                                             class="btn btn-success mx-1"
                                                                             :to="{ name:'expense-page', params: { id:merge.id }}"
                                                                             title="Add Expense">
                                                                    <i class="fas fa-plus"></i>
                                                                </router-link>
                                                                <router-link target="_blank"
                                                                             v-if="checkForSubmenuButtons('add-expense')"
                                                                             class="btn btn-success mx-1"
                                                                             :to="{ name:'header-link-page', params: { id:merge.id }}"
                                                                             title="header link">
                                                                    Link
                                                                </router-link>
                                                                <button title="Closing Date"
                                                                    data-target="#date-modal"
                                                                    data-toggle="modal"
                                                                    @click="closingData.mergeId = merge.id; closingData.closingDate = merge.closing_date"
                                                                    class="btn btn-info mx-1"
                                                                >
                                                                    <i class="far fa-clock"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="6"></td>
                                                            <td><b>{{ totalSale }}</b></td>
                                                            <td><b>{{ totalExpense }}</b></td>
                                                            <td><b>{{ totalSale - totalExpense }}</b></td>
                                                        </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
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
            <!-- closing date -->
            <div class="modal fade" id="date-modal" tabindex="-1" aria-labelledby="addDaysModalLabel"
                 aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content ">
                        <div class="modal-header">
                            <h5 class="modal-title" id="">Closing Date</h5>
                            <button type="button" class="close" @click="close()" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-12 class form-group">
                                    <label for="start">Closing Date <span class="text-danger ml-1">*</span></label>
                                    <input
                                        type="date"
                                        id="start"
                                        class="form-control"
                                        v-model="closingData.closingDate"
                                        
                                    />
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" @click="updateClosingDate()" :disabled="loading">
                                {{ loading ? 'Loading... ' : 'Update Date' }}
                            </button>
                            <button type="button" @click="close()" class="btn btn-secondary" data-dismiss="modal">Close</button>
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
            tableLoading: true,
            validationErrors: "",
            totalSale: 0,
            totalExpense: 0,
            merges: [],
            buses: [],
            closingData: {
                closingDate: "",
                mergeId: "",
            },
            filterData: {
                bus_number: "",
                from_date: "",
                to_date: "",
            },
            // formID: "schedule_closing_form",
            // editFormID: "edit_schedule_closing_form",
            success: false,
            errors: false,
            permissions: [],
        };
    },
    created() {
        $('.modal').remove();
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
        close() {
            $("#date-modal").click();
        },
        async fetchData() {
            this.tableLoading = true;
            const res = await this.callApi("post", "booking/close/schedule/merges");
            if (res.status == 200) {
                this.merges = res.data.merges;
                this.buses = res.data.buses;
                this.tableLoading = false;
            } else {
                console.log(res);
            }
            // setTimeout(() => {
            //     $('#merge_table').DataTable({
            //         'order': []
            //     });
            // }, 300);
        },
        async fetchMerges() {
            this.tableLoading = true;
            const res = await this.callApi("post", "booking/close/schedule/merges",this.filterData);
            if (res.status == 200) {
                this.merges = res.data.merges;
                this.tableLoading = false;
            } else {
                console.log(res);
            }
        },
        async updateClosingDate() {
            if (this.closingData.closingDate == "") {
                return swal({
                    title: "Required!",
                    text: "Date Field is Required ",
                    icon: "error",
                    timer: 2000
                });
            }
            this.loading = true;
            const res = await this.callApi("post", "booking/close/schedule/closing/date/update", this.closingData);
            if (res.status == 200) {
                this.close();
                swal({
                    title: "Success",
                    text: "Update Closing Date Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.loading = false;
                this.fetchMerges();
            } else {
                if (res.status == 422) {
                    this.loading = false;
                    let errorContent = "";
                    let count = 0;
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            errorContent += (
                                (++count) + " - " + //creating serial no.
                                element + // main error
                                "\n" // creating new line
                            );
                        });
                        swal({
                            title: "Error",
                            text: errorContent,
                            icon: "error",
                            timer: 2000
                        });

                    }
                }
            }
        },
    },
    computed: {
        ...mapGetters(["getDeletingObj"]),
    },
    watch: {
        merges(){
            this.totalSale = this.merges.reduce((sum, single) => {
                return sum + parseInt(single.seat_fare) + parseInt(single.elt) + parseInt(single.refund) - parseInt(single.discount) - parseInt(single.commission);
            }, 0);

            this.totalExpense = this.merges.reduce((sum, single) => {
                return sum + parseFloat(single.expenses_sum_amount);
            }, 0);
       },
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.buses.splice(obj.index, 1);
                // $('#merge_table').DataTable().destroy();
            }
        },
    },
};
</script>
<style scoped>
.loading-spinner {
    display: block;
    margin: 0 auto;
    padding: 2em;
  }
</style>

