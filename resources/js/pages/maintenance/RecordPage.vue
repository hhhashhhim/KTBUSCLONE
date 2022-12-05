<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Maintenance Record</h4>
                            <div class="card-header-action">
                            </div>
                        </div>
                        <div class="card-body">
                            <transition name="fade">
                                <div
                                    class="alert alert-danger alert-dismissible fade show"
                                    role="alert"
                                    v-if="error"
                                >
                                    <button
                                        type="button"
                                        class="close"
                                        data-dismiss="alert"
                                        aria-label="Close"
                                        @click="error = !error"
                                    >
                                        <span aria-hidden="true">&times;</span>
                                        <span class="sr-only">Close</span>
                                    </button>
                                    Please Enter All Required Fields !!!
                                </div>
                            </transition>
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4></h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table
                                                    class="table table-striped table-hover"
                                                    id="maintenance_table"
                                                >
                                                    <thead>
                                                    <tr>
                                                        <th>Evidence</th>
                                                        <th>Buss Number/Name</th>
                                                        <th>Part</th>
                                                        <th>Company Paid</th>
                                                        <th>Maintenance Type</th>
                                                        <th>Detail</th>
                                                        <th>Date</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(data, i) in mainData" :key="i">
                                                        <td>
                                                            <a
                                                            :href="($store.state.app_url +'uploads/maintenance/'+ data.evidence)"
                                                            target="_blank"
                                                            >
                                                            <img
                                                                :src="($store.state.app_url +'uploads/maintenance/'+ data.evidence)"
                                                                style="width:70px;height:70px;" alt="">
                                                            </a>
                                                        </td>
                                                        <td>{{ data.bus_name.bus_number }} </td>
                                                        <td>{{ data.part_name.name }} </td>
                                                        <td>{{ data.company_paid }} </td>
                                                        <td>{{ data.maintenance_type == 1 ? 'Irregular' : 'Due' }} </td>
                                                        <td>{{ data.detail }} </td>
                                                        <td>{{data.time }} </td>
                                                        <td>
                                                            <button class="btn btn-primary mx-1"
                                                                    data-target="#maintenance_add"
                                                                    data-toggle="modal"
                                                                    @click="dueMaintenanceFrom( data , 0)">
                                                                    <i class="fas fa-plus"></i>
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
import {mapGetters} from "vuex";

export default {
    name: "RecordPage",
    components: {
        Add,
        Edit,
    },
    data() {
        return {
            formID: "maintenance_add",
            readingFormID: "reading_update",
            loading : false,
            validationErrors: [],
            mainData: [],
        };
    },
    created() {
        this.fetchData();
    },
    methods: {
        clearForm: function () {
          this.data = {};
          this.reverseRoute = 1;
        },
        async fetchData() {
            const maintenanceRes = await this.callApi("post", "fleet/maintenance/record");
            if (maintenanceRes.status === 200) {
                
                this.mainData = maintenanceRes.data.mainData;
            }

            setTimeout(() => {
                $('#maintenance_table').DataTable();
            }, 300);
        },
        
    },
    computed: {
        ...mapGetters(["getDeletingObj"]),
        heading: function () {
            return from.name + "<i class='fa fa-user'></i>" + to.name;
        },
    },
};
</script>
<style scoped>
table,
table * {
    font-size: 10px;
}

.modal-cell {
    padding: 0 !important;
    position: relative;
}

.modal-cell .modal-btn {
    height: 100%;
    transition: 0.5s transform;
}

.modal-cell:hover .modal-btn {
    position: absolute;
    z-index: 20;
    transform: scale(1.3) translateY(-20px);
    box-shadow: 0px 0px 10px black;
}

.header-select {
    width: 35%;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 1s;
}

.fade-enter, .fade-leave-to /* .fade-leave-active below version 2.1.8 */
{
    opacity: 0;
}

table, tr, th, td, option, select, label, button, a, div, p {
    font-size: 14px !important;
}

.checkbox-inputs {
    position: relative;
    bottom: 10px;
}
</style>
