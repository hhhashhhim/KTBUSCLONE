<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header">
                            <h4>Over Issue Report</h4>
                        </div>
                        <div class="card-body">
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <label for="terminalFilter">Terminals</label>
                                                    <select id="terminalFilter" class="form-control"
                                                            v-model="filterCancel.terminal"
                                                            @change="overissueFilter()">
                                                        <option value="0">Select Terminals</option>
                                                        <option v-for="(terminal, i) in terminals" :key="i"
                                                                :value="terminal.id">
                                                            {{ terminal.name }}
                                                        </option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="fromDate">From Date </label>
                                                    <input id="fromDate" type="date" class="form-control"
                                                           v-model="filterCancel.fromDate" @change="overissueFilter()">
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="toDate">To Date </label>
                                                    <input id="toDate" type="date" class="form-control"
                                                           v-model="filterCancel.toDate" @change="overissueFilter()">
                                                </div>
                                                <div class="col-md-2">
                                                    <button class="btn btn-primary mt-4 py-2"
                                                            @click="getPdfPrint()">Print
                                                        Report
                                                    </button>
                                                </div>
                                                <!--Print Confirmation Cancel report-->
                                                <form
                                                    :action="$store.state.api_url + 'api/web/v1/print/pdf/over-issue/report'"
                                                    method="POST" ref="refOverissue"
                                                    target="_blank">
                                                    <input type="hidden" name="token" :value="this.$store.state.token">
                                                    <input type="hidden" name="terminal"
                                                           :value="this.filterCancel.terminal">
                                                    <input type="hidden" name="fromDate"
                                                           :value="this.filterCancel.fromDate">
                                                    <input type="hidden" name="toDate"
                                                           :value="this.filterCancel.toDate">
                                                </form>
                                            </div>
                                            <div class="row mt-2">
                                                <div class="col-md-12">
                                                    <div class="table-responsive">
                                                        <div v-if="tableLoading">
                                                            <img class="loading-spinner" :src="$store.state.main_url + 'assets/img/loading-spinner.gif'">
                                                        </div>
                                                        <table class="table text-center" v-else>
                                                            <thead>
                                                            <tr>
                                                                <th width="200px">Bus Time</th>
                                                                <th>Terminal Name</th>
                                                                <th>Seat No</th>
                                                                <th>Type</th>
                                                                <th>Passenger Name</th>
                                                                <th>Cell NO</th>
                                                                <th>Total Fare</th>
                                                                <th>Remarks</th>
                                                                <th>Over Issue By</th>
                                                                <th width="200px">Over Issue Time</th>
                                                            </tr>
                                                            </thead>

                                                            <tbody>
                                                            <tr v-for="(filter,i) in filters" :key="i"
                                                                :class="filter.badge">
                                                                <td>{{ filter.bus_time }}</td>
                                                                <td>{{ filter.terminal_name }}</td>
                                                                <td>{{ filter.seat_no }}</td>
                                                                <td>{{ filter.type }}</td>
                                                                <td>{{ filter.passenger_name }}</td>
                                                                <td>{{ filter.passenger_contact }}</td>
                                                                <td>{{ filter.total_fare }}</td>
                                                                <td>{{ filter.overissue_reason }}</td>
                                                                <td>{{ filter.overissue_by }}</td>
                                                                <td>{{ filter.overissue_time }}</td>
                                                            </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
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
export default {
    name: "OverIssuePage",
    data() {
        return {
            csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            terminals: [],
            filters: [],
            refundFilters: [],
            tableLoading: true,
            filterCancel: {
                terminal: 0,
                fromDate: '',
                toDate: '',
            },
        }
    },
    async created() {
        $('.modal').remove();
        this.fetchFilters();
        this.overissueFilter();
        const currentRouteName = this.$route.name;
        if (currentRouteName == 'booking-page') {
            window.addEventListener('keydown', this.enterKey);
            window.addEventListener('keydown', this.altM);
        } else {
            window.removeEventListener('keydown', this.enterKey);
            window.removeEventListener('keydown', this.altM);
        }
    },

    methods: {
        async fetchFilters() {
            const resTerminals = await this.callApi("post", 'over-issue/getTerminals');
            if (resTerminals.status == 200) {
                this.terminals = resTerminals.data;
            }

        },
        async overissueFilter() {
            this.tableLoading = true;
            const resFetchData = await this.callApi("post", 'over-issue/fetchFilterData', this.filterCancel);
            console.log(resFetchData);
            if (resFetchData.status == 200) {
                this.filters = resFetchData.data;
                this.tableLoading = false;
            }

        },
        getPdfPrint: function () {
            this.$refs.refOverissue.submit();
        }
    },

}
</script>
<style scoped>
table, th, td {
    border: 1px solid #b9b9b9;
    border-collapse: collapse;
}

.red {
    background-color: #ec3030;
}

.yellow {
    background-color: #bdbd02;
}

.white {
    background-color: #FFFFFF;
}

.green {
    background-color: #03b203;
}

.loading-spinner {
    display: block;
    margin: 0 auto;
    padding: 2em;
  }
</style>
