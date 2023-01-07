<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>All Bookings</h4>
                        </div>
                        <div class="card-body">
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="CNIC">CNIC</label>
                                                        <vue-mask id="CNIC"
                                                                  class="form-control"
                                                                  v-model="filterForm.cnicFilter"
                                                                  mask="00000-0000000-0"
                                                                  :raw="false"
                                                                  :options="options"
                                                        >
                                                        </vue-mask>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="phone">Cell #</label>
                                                        <vue-mask id="phone"
                                                                  class="form-control"
                                                                  v-model="filterForm.phoneFilter"
                                                                  mask="0000-0000000"
                                                                  :raw="false"
                                                                  :options="optionsContact"
                                                        >
                                                        </vue-mask>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="name">Name</label>
                                                        <input id="name" type="text" class="form-control"
                                                               v-model="filterForm.nameFilter">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Route</label>
                                                        <select id="routeFilter" class="form-control"
                                                                v-model="filterForm.routeFilter">
                                                            <option value="0">---Select Route---</option>
                                                            <option v-for="(route, i) in routes" :key="i"
                                                                    :value="route.id">
                                                                {{ route.name }}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="terminalsFilter">Terminals</label>
                                                        <select id="terminalsFilter" class="form-control"
                                                                v-model="filterForm.terminalFilter">
                                                            <option value="0">---Select Terminal---</option>
                                                            <option v-for="(terminal, i) in terminals" :key="i"
                                                                    :value="terminal.id">
                                                                {{ terminal.name }}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="busFilter">Bus #</label>
                                                        <select id="busFilter" class="form-control"
                                                                v-model="filterForm.busFilter">
                                                            <option value="0">---Select Bus #---</option>
                                                            <option v-for="(bus, i) in buses" :key="i"
                                                                    :value="bus.id">
                                                                {{ bus.bus_number }}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="dateFilter">Date</label>
                                                        <input type="date" class="form-control" id="dateFilter"
                                                               v-model="filterForm.dateFilter">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="statusFilter">Status</label>
                                                        <select id="statusFilter" class="form-control"
                                                                v-model="filterForm.statusFilter">
                                                            <option value="0">---Select Status---</option>
                                                                    <option value="advanced booking">Booked</option>
                                                            <option value="booked">Issued</option>
                                                            <option value="cancel">Cancelled</option>
                                                            <option value="reschedule">Reschedule Ticket</option>
                                                            <option value="over-issue">Over Issue Ticket</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-if="showAllBooking">
                                                <div class="table-responsive">
                                                    <table
                                                        class="table table-striped table-hover"
                                                        id="discount_table"
                                                    >
                                                        <thead>
                                                        <tr>
                                                            <th>Sr No.</th>
                                                            <th>Name</th>
                                                            <th>Percentage</th>
                                                            <th>Flat Amount</th>
                                                            <th>Status</th>
                                                            <th>Added By</th>
                                                            <th>Action</th>
                                                        </tr>
                                                        </thead>
                                                        <tbody>
                                                        <tr v-for="(discount, i) in discounts" :key="i">
                                                            <td>{{ i + 1 }}</td>
                                                            <td>{{ discount.name }}</td>
                                                            <td v-if="discount.percentage">{{
                                                                    discount.percentage
                                                                }}{{ discount.type == 'percentage' ? '%' : '' }}
                                                            </td>
                                                            <td v-else>N/A</td>
                                                            <td v-if="discount.flat">{{ discount.flat }}</td>
                                                            <td v-else>N/A</td>
                                                            <td>{{
                                                                    discount.is_active == 1 ? 'Active' : 'InActive'
                                                                }}
                                                            </td>
                                                            <td>{{ discount.added_by.name }}</td>
                                                            <td>
                                                                <button :data-target="'#' + editFormID"
                                                                        data-toggle="modal"
                                                                        @click="edit(discount)"
                                                                        class="btn btn-primary mx-1">
                                                                    <i class="far fa-edit"></i>
                                                                </button>
                                                                <button :data-target="'#' + deleteFormID"
                                                                        data-toggle="modal"
                                                                        @click="deleteModal(discount,i)"
                                                                        class="btn btn-danger">
                                                                    <i class="far fa-trash-alt"></i>
                                                                </button>
                                                            </td>
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
        </div>
    </section>
</template>

<script>
import Add from "../../components/Add.vue";
import Edit from "../../components/Edit.vue";
import Delete from "../../components/Delete.vue";
import {mapGetters} from "vuex";
import vueMask from "vue-jquery-mask";

export default {
    name: "AllBookingPage",
    components: {
        Add,
        Edit,
        Delete,
        vueMask,
    },
    data() {
        return {
            options: {
                placeholder: "xxxxx-xxxxxxx-x",
            },
            optionsContact: {
                placeholder: "03xx-xxxxxxx",
            },
            loading: false,
            showAllBooking: false,
            routes: [],
            terminals: [],
            buses: [],
            validationErrors: [],
            filterForm: {
                terminalFilter: 0,
                routeFilter: 0,
                busFilter: 0,
                statusFilter: 0,
            }
        };
    },
    async created() {
        await this.fetchRoutes();
        await this.fetchTerminals();
        await this.fetchBus();
    },
    methods: {
        async fetchRoutes() {
            const resRoute = await this.callApi("post", "allBooking/routes");
            console.log(resRoute);
            if (resRoute.status == 200) {
                this.routes = resRoute.data;

            } else {
                this.filterForm.routeFilter = 0;
            }
        },
        async fetchTerminals() {
            const resTerminal = await this.callApi("post", "allBooking/terminals");
            console.log(resTerminal);
            if (resTerminal.status == 200) {
                this.terminals = resTerminal.data;
            } else {
                this.filterForm.terminalFilter = 0;
            }
        },
        async fetchBus() {
            const resBuses = await this.callApi("post", "allBooking/buses");
            console.log(resBuses);
            if (resBuses.status == 200) {
                this.buses = resBuses.data;
            } else {
                this.filterForm.busFilter = 0;
            }

        },
    },
    computed: {
        ...mapGetters(['getDeletingObj'])
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.discounts.splice(obj.index, 1)
                $('#discount_table').DataTable().destroy();
            }
        }
    }
};
</script>
<style scoped>
</style>
