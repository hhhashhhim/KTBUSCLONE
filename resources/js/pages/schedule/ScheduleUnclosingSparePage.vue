<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h4>Spare Unclosing Detail</h4>
                        </div>
                        <div class="card-body">
                            <form @submit.prevent="fetchData">
    <div class="row px-2 mb-4 align-items-end">
        <div class="col-md-3">
            <label for="terminalFilter">Select Bus</label>
            <select id="terminalFilter" class="form-control"
                    v-model="filterData.bus_number">
                <option value="">Select Bus</option>
                <option v-for="(bus, i) in buses" :key="i" :value="bus.id">
                    {{ bus.bus_number }}
                </option>
            </select>
        </div>

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

       <div class="col-md-3">
  <div class="row">
    <div class="col-6 pr-1">
      <button type="submit" class="btn btn-primary w-100">
        Filter
      </button>
    </div>
    <div class="col-6 pl-1">
      <button type="button" class="btn btn-danger w-100" @click="resetFilters">
        Reset
      </button>
    </div>
  </div>
</div>

    </div>
</form>
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <!-- <div class="d-flex justify-content-end">
                                                <button class="btn btn-primary" :disabled="loading" @click="mergeSchedule()">
                                                    Merge Schedule
                                                </button>
                                            </div> -->
                                            <div class="table-responsive">
                                                <table
                                                    class="table table-striped table-hover"
                                                    id="closing_table"
                                                   
                                                >
                                                    <thead>
                                                    <tr>
                                                        <th>Bus Number</th>
                                                        <th>Schedule</th>
                                                        <th>Route Name</th>
                                                        <th>Schedule Date</th>
                                                        <th>Schedule Time</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(close, j) in closings" :key="j">
                                                        <td>
                                                            {{ close.bus.bus_number }}
                                                        </td>
                                                        <td>
                                                            {{ close.schedule.name }}
                                                        </td>
                                                        <td>
                                                            {{ close.schedule.route.name }}
                                                        </td>
                                                        <td>
                                                            {{ close.schedule_date }}
                                                        </td>
                                                        <td>
                                                            {{ close.schedule_time }}
                                                        </td>
                                                        <td>
                                                            <button title="Revert Unclosing"
                                                                    :data-target="'#' + hideFormID" @click="delId = close.id" data-toggle="modal"
                                                                    class="btn btn-primary btn-sm mx-2"
                                                            >
                                                            Revert
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <tr v-if="closings.length==0">
                                                        <td class="text-center" colspan="6">No data found</td>
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
            <Hide :hideForm="hideFormID" confirmationMessage="Are You Sure You want To Revert This Closing ???">
                <template v-slot:button>
                    <button
                        type="button"
                        class="btn btn-danger btn-block"
                       :disabled="loading" @click="revertUnclosing"
                    >
                    {{ loading ? 'Loading...' : 'Yes, I want to Revert' }}
                    </button>
                </template>
            </Hide>
        </div>
    </section>
</template>

<script>

import {mapGetters} from "vuex";
import Hide from "../../components/Hide.vue";
export default {
    name: "ScheduleUnclosingSparePage",
    components: {
        Hide
    },
    data() {
        return {
            loading: false,
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
              filterData: {
                  bus_number: "",
                  from_date: "",
                  to_date: ""
                },
                 buses: [],
        };
    },
    async created() {
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
       async fetchData() {
    const res = await this.callApi(
        "post",
        "booking/close/schedule/unclosing/spare",
        this.filterData
    );
    if (res.status === 200) {

        // Step 1: destroy existing DataTable if exists
        if ($.fn.dataTable.isDataTable("#closing_table")) {
            $("#closing_table").DataTable().destroy();
        }

        // Step 2: update Vue data
        this.closings = res.data.closings;
        this.buses = res.data.buses || [];

        // Step 3: Initialize DataTable only if data exists
        this.$nextTick(() => {
            if (this.closings.length > 0) {
                $("#closing_table").DataTable({
                    pageLength: 10,
                    responsive: true,
                    autoWidth: false,
                    ordering: true
                });
            }
        });
    } else {
        console.log(res);
    }
},
resetFilters() {
  this.filterData = {
    bus_number: "",
    from_date: "",
    to_date: ""
  };
  this.fetchData();
},
        async revertUnclosing() {
            
            this.loading = true;
            const resHide = await this.callApi("post", 'booking/close/schedule/unclosing/revert', {id:this.delId});
            if (resHide.status == 200) {
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "Unclosing Deleted Successfully",
                    icon: "success",
                    timer: 2000
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
                    this.loading = false
                }, 3000);
            }
        },
    },
    computed: {
        ...mapGetters(["getDeletingObj"]),
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.buses.splice(obj.index, 1);
                $('#closing_table').DataTable().destroy();
                this.fetchBuses();
            }
        },
    },
};
</script>
<style src="@vueform/multiselect/themes/default.css"></style>

