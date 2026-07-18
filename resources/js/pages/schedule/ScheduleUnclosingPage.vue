<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h4>Schedule Unclosing Detail</h4>
                        </div>
                        <div class="card-body">
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <form @submit.prevent="fetchData(true)">
                                                <div class="row px-2 mb-4 align-items-end">
                                                    <div class="col-md-3">
                                                        <label for="terminalFilter">Select Bus</label>
                                                        <select2 v-model="filterData.bus_number" :options="busOptions"
                                                            :settings="{ width: '100%', placeholder: 'Select Bus', allowClear: true }" />
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label>Select Route</label>
                                                        <select2 v-model="filterData.dropdownRoute" :options="routeOptions"
                                                            :settings="{ multiple: true, width: '100%', placeholder: 'Select Route', allowClear: true }" />
                                                    </div>
                                                    <!-- <div class="col-md-3">
                                                        <label>Route</label>
                                                        <select2 v-model="filterData.route" :options="route"
                                                            :settings="{ multiple: true, width: '100%', placeholder: 'Select Route', allowClear: true }" />
                                                    </div> -->
                                                    <div class="col-md-3">
                                                        <label for="fromDate">Schedule Date</label>
                                                        <input id="fromDate" type="date" class="form-control"
                                                            v-model="filterData.from_date">
                                                    </div>

                                                    <div class="col-md-3">
                                                        <label for="toDate">Return Date</label>
                                                        <input id="toDate" type="date" class="form-control"
                                                            v-model="filterData.to_date">
                                                    </div>

                                                    <div class="col-md-12 mt-3">
                                                        <div class="row">
                                                            <div class="col-6 pr-1">
                                                                <button type="submit" class="btn btn-primary w-100">
                                                                    Filter
                                                                </button>
                                                            </div>
                                                            <div class="col-6 pl-1">
                                                                <button type="button" class="btn btn-danger w-100"
                                                                    @click="resetFilters">
                                                                    Reset
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>
                                            </form>
                                            <div class="d-flex justify-content-end">
                                                <button class="btn btn-primary d-flex align-items-center"
                                                    :disabled="loading" @click="fetchMergedData">
                                                    <span v-if="loading" class="spinner-border spinner-border-sm mr-2"
                                                        role="status" aria-hidden="true"></span>

                                                    <span>
                                                        {{ loading ? 'Merging...' : 'Merge Schedule' }}
                                                    </span>
                                                </button>


                                                <!-- <button class="btn btn-primary" :disabled="loading" @click="mergeSchedule()">
                                                    Merge Schedule
                                                </button> -->
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover" id="closing_table" style="
                                                        border-collapse: separate;
                                                        border-spacing: 0 10px;
                                                    ">
                                                    <thead>
                                                        <tr>
                                                            <th>Bus Number</th>
                                                            <th>Schedule</th>
                                                            <th>Route Name</th>
                                                            <th>
                                                                Schedule Date
                                                            </th>
                                                            <th>
                                                                Schedule Time
                                                            </th>
                                                            <th>٘Merge</th>
                                                            <th v-if="
                                                                checkForSubmenuButtons(
                                                                    'edit-close-booking'
                                                                )
                                                            ">
                                                                Action
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <template v-for="(
data, i
                                                            ) in closings" :key="i">
                                                            <tr v-for="(
close, j
                                                                ) in data" :key="j">
                                                                <td class="h5" :class="data.length ==
                                                                    2
                                                                    ? j ==
                                                                        1
                                                                        ? 'border-left border-bottom border-success'
                                                                        : 'border-left border-top border-success'
                                                                    : 'border-left border-bottom border-top border-danger'
                                                                    ">
                                                                    {{
                                                                        close
                                                                            .bus
                                                                            .bus_number
                                                                    }}
                                                                </td>
                                                                <td class="h5" :class="data.length ==
                                                                    2
                                                                    ? j ==
                                                                        1
                                                                        ? 'border-bottom border-success'
                                                                        : 'border-top border-success'
                                                                    : 'border-bottom border-top border-danger'
                                                                    ">
                                                                    {{
                                                                        close
                                                                            .schedule
                                                                            .name
                                                                    }}
                                                                </td>
                                                                <td class="h5" :class="data.length ==
                                                                    2
                                                                    ? j ==
                                                                        1
                                                                        ? 'border-bottom border-success'
                                                                        : 'border-top border-success'
                                                                    : 'border-bottom border-top border-danger'
                                                                    ">
                                                                    {{
                                                                        close
                                                                            .schedule
                                                                            .route
                                                                            .name
                                                                    }}
                                                                </td>
                                                                <td class="h5" :class="data.length ==
                                                                    2
                                                                    ? j ==
                                                                        1
                                                                        ? 'border-bottom border-success'
                                                                        : 'border-top border-success'
                                                                    : 'border-bottom border-top border-danger'
                                                                    ">
                                                                    {{
                                                                        close.schedule_date
                                                                    }}
                                                                </td>
                                                                <td class="h5" :class="data.length ==
                                                                    2
                                                                    ? j ==
                                                                        1
                                                                        ? 'border-bottom border-success'
                                                                        : 'border-top border-success'
                                                                    : 'border-bottom border-top border-danger'
                                                                    ">
                                                                    {{
                                                                        close.schedule_time
                                                                    }}
                                                                </td>
                                                                <td class="h5" :class="data.length ==
                                                                    2
                                                                    ? j ==
                                                                        1
                                                                        ? 'border-bottom border-success'
                                                                        : 'border-top border-success'
                                                                    : 'border-bottom border-top border-danger'
                                                                    ">
                                                                    <input type="checkbox" id="femaleCheckBox" :checked="addData.mergeIds.includes(
                                                                        close.ticket_merge_id
                                                                    )
                                                                        " @click="
                                                                            changeClosingId(
                                                                                close
                                                                            )
                                                                            " name="" />
                                                                </td>
                                                                <td :class="data.length ==
                                                                    2
                                                                    ? j ==
                                                                        1
                                                                        ? 'border-bottom border-right border-success'
                                                                        : 'border-right border-top border-success'
                                                                    : 'border-bottom border-right border-top border-danger'
                                                                    ">
                                                                    <button title="Delete Unclosing" :data-target="'#' +
                                                                        hideFormID
                                                                        " @click="
                                                                            delId =
                                                                            close.id
                                                                            " data-toggle="modal"
                                                                        class="btn btn-danger btn-sm mx-2">
                                                                        <i class="far fas fa-trash"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        </template>
                                                        <tr v-if="
                                                            closings.length ==
                                                            0
                                                        ">
                                                            <td class="text-center" colspan="6">
                                                                No data found
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
            <Hide :hideForm="hideFormID" confirmationMessage="Are You Sure You want To Delete This Closing ???">
                <template v-slot:button>
                    <button type="button" class="btn btn-danger btn-block" :disabled="loading" @click="hideUnclosing">
                        {{ loading ? "Loading..." : "Yes, I want to Delete" }}
                    </button>
                </template>
            </Hide>
        </div>
    </section>
    <Closing :data="closingData" :routes="routes" :banks="banks" :busIds="busIds" :mergeIds="mergeIds"
        :addData="addData" @closingSaved="resetClosingSelection" @fetchData="fetchData($event)" />
</template>

<script>
import { mapGetters } from "vuex";
import Hide from "../../components/Hide.vue";
import Closing from "../../components/closing/ClosingComponent.vue";
export default {
    name: "unclosing",
    components: {
        Hide,
        Closing,
    },
    data() {
        return {
            loading: false,
            filterData: {
                bus_number: "",
                from_date: "",
                to_date: "",
                dropdownRoute: [],
            },
            applyDateFilter: false,
            dropdownRoute: [],
            closings: [],
            permissions: [],
            validationErrors: "",
            formID: "schedule_closing_form",
            hideFormID: "hide_schedule_form",
            delId: "",
            seatNo: 0,
            addData: {
                mergeIds: [],
                busIds: [],
            },
            success: false,
            errors: false,
            closingData: {},
            buses: [],
            banks: [],
            busIds: [],
            mergeIds: [],
            routes: {
                start: "",
                return: ""
            }
        };
    },
    async created() {
        $(".modal").remove();
        const currentRouteName = this.$route.name;
        if (currentRouteName == "booking-page") {
            window.addEventListener("keydown", this.enterKey);
            window.addEventListener("keydown", this.altM);
        } else {
            window.removeEventListener("keydown", this.enterKey);
            window.removeEventListener("keydown", this.altM);
        }

        this.fetchData();
        this.fetchRoute();  // load route on component mount
        this.permissions = this.$store.state.permissions;
    },
    methods: {
        async fetchRoute() {
            try {
                const res = await this.callApi("post", "booking/close/schedule/merges/route");

                this.dropdownRoute = res.data?.routes || res.data?.routes || [];

            } catch (error) {
                console.error("Error fetching route:", error);
            }
        },
        async fetchMergedData() {
            if (this.loading) return;

            this.loading = true;
            this.validationErrors = [];

            // ❌ Validation: same buses
            if (this.addData.busIds[0] !== this.addData.busIds[1]) {
                swal({
                    title: "Required",
                    text: "Please select same buses",
                    icon: "error",
                    timer: 2000,
                });
                this.loading = false;
                return;
            }

            // ❌ Validation: two schedules
            if (this.addData.mergeIds.length !== 2) {
                swal({
                    title: "Required",
                    text: "Please select two schedules",
                    icon: "error",
                    timer: 2000,
                });
                this.loading = false;
                return;
            }

            try {
                const res = await this.callApi(
                    "post",
                    "booking/close/schedule/unclosing/data",
                    this.addData
                );

                if (res.status === 200) {
                    this.closingData = res.data.data;
                    this.banks = res.data.banks;
                    this.busIds = res.data.busIds;
                    this.mergeIds = res.data.mergeIds;
                    this.routes = {
                        start: res.data.startRoute,
                        return: res.data.returnRoute,
                    };


                    // ✅ Open modal after success
                    $("#exampleModal").modal("show");
                } else {
                    console.log(res);
                }
            } catch (err) {
                console.error(err);
            } finally {
                // ✅ Always stop loader
                this.loading = false;
            }
        },
        clearForm: function () {
            this.data = {};
        },
        resetClosingSelection() {
            this.addData.mergeIds = [];
            this.addData.busIds = [];
            this.mergeIds = [];
            this.busIds = [];
            this.banks = [];
            this.closingData = {};
            this.routes = { start: "", return: "" };
        },
        changeClosingId: function (close) {
            if (this.addData.mergeIds.includes(close.ticket_merge_id)) {
                this.addData.mergeIds.splice(
                    this.addData.mergeIds.indexOf(close.ticket_merge_id),
                    1
                );
                this.addData.busIds.splice(
                    this.addData.busIds.indexOf(close.bus_id),
                    1
                );
            } else {
                if (this.addData.mergeIds.length == 2) {
                    this.closings = [];
                    this.fetchData();
                    return swal({
                        title: "Required",
                        text: "You can't select more than two",
                        icon: "error",
                        timer: 2000,
                    });
                }
                this.addData.mergeIds.push(close.ticket_merge_id);
                this.addData.busIds.push(close.bus_id);
            }
            console.log(this.addData);
        },
        async fetchData(applyDateFilter = this.applyDateFilter) {
            try {
                this.applyDateFilter = applyDateFilter;
                const payload = { ...this.filterData };

                if (!applyDateFilter) {
                    payload.from_date = "";
                    payload.to_date = "";
                }

                const res = await this.callApi(
                    "post",
                    "booking/close/schedule/unclosing",
                    payload
                );

                if (res.status === 200) {
                    this.closings = res.data.closings;
                    this.buses = res.data.buses;
                } else {
                    console.log(res);
                }
            } catch (e) {
                console.error(e);
            }
        },
        resetFilters() {
            this.filterData = {
                bus_number: "",
                from_date: "",
                to_date: "",
                dropdownRoute: [],
            };
            this.applyDateFilter = false;
            this.fetchData(false);
        },

        async hideUnclosing() {
            this.loading = true;
            const resHide = await this.callApi(
                "post",
                "booking/close/schedule/unclosing/hide",
                { id: this.delId }
            );
            if (resHide.status == 200) {
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "Unclosing Deleted Successfully",
                    icon: "success",
                    timer: 2000,
                });
                this.loading = false;
                this.fetchData();
            } else {
                if (resHide.status == 422) {
                    this.loading = false;
                    for (const key in resHide.data.errors) {
                        resHide.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
                setTimeout(() => {
                    this.loading = false;
                }, 3000);
            }
        },

        async mergeSchedule() {
            this.validationErrors = [];
            if (this.addData.busIds[0] != this.addData.busIds[1])
                return swal({
                    title: "Required",
                    text: "Please select same buses",
                    icon: "error",
                    timer: 2000,
                });
            if (this.addData.mergeIds.length != 2)
                return swal({
                    title: "Required",
                    text: "Please select two schedule",
                    icon: "error",
                    timer: 2000,
                });
            this.loading = true;
            const res = await this.callApi(
                "post",
                "booking/close/schedule/closing/merge",
                this.addData
            );
            if (res.status == 200) {
                swal({
                    title: "Success",
                    text: "Schedule Merge Successfully",
                    icon: "success",
                    timer: 2000,
                });
                setTimeout(() => {
                    this.loading = false;
                }, 1000);
                this.addData.mergeIds = [];
                this.fetchData();
            } else {
                if (res.status == 422) {
                    this.loading = false;
                    let errorContent = "";
                    let count = 0;
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            errorContent +=
                                ++count +
                                " - " + //creating serial no.
                                element + // main error
                                "\n"; // creating new line
                        });
                        swal({
                            title: "Error",
                            text: errorContent,
                            icon: "error",
                            timer: 2000,
                        });
                    }
                }
            }
        },
        editSchedule(schedule) { },
    },
    computed: {
        ...mapGetters(["getDeletingObj"]),
        busOptions() {
            return this.buses.map(bus => ({
                id: bus.id,
                text: bus.bus_number,
            }));
        },
        routeOptions() {
            return this.dropdownRoute.map(route => ({
                id: route.id,
                text: route.name,
            }));
        },
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.buses.splice(obj.index, 1);
                $("#closing_table").DataTable().destroy();
                this.fetchBuses();
            }
        },
    },
};
</script>
<style src="@vueform/multiselect/themes/default.css"></style>
