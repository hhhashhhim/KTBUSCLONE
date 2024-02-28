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
                                <div class="alert alert-danger alert-dismissible fade show" role="alert" v-if="error">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"
                                        @click="error = !error">
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
                                                <table class="table table-striped table-hover" id="maintenance_table">
                                                    <thead>
                                                        <tr>
                                                            <th>Evidence</th>
                                                            <th>Buss Number/Name</th>
                                                            <th>Part</th>
                                                            <th>Company Paid</th>
                                                            <th>Maintenance Type</th>
                                                            <th>Detail</th>
                                                            <th>Date</th>
                                                            <th v-if="checkForSubmenuButtons('edit-maintenance')">Action
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(data, i) in mainData" :key="i">
                                                            <td>
                                                                <a :href="($store.state.app_url + 'uploads/maintenance/' + data.evidence)"
                                                                    target="_blank">
                                                                    <img :src="($store.state.app_url + 'uploads/maintenance/' + data.evidence)"
                                                                        style="width:70px;height:70px;" alt="">
                                                                </a>
                                                            </td>
                                                            <td>{{ data.bus_name.bus_number }} </td>
                                                            <td>{{ data.part_name.name }} </td>
                                                            <td>{{ data.company_paid }} </td>
                                                            <td>{{ data.maintenance_type == 1 ? 'Irregular' : 'Due' }} </td>
                                                            <td>{{ data.detail }} </td>
                                                            <td>{{ data.time }} </td>
                                                            <td v-if="checkForSubmenuButtons('edit-maintenance')">
                                                                <button v-if="checkForSubmenuButtons('edit-maintenance')"
                                                                    class="btn btn-primary mx-1"
                                                                    data-target="#maintenance_udpate" data-toggle="modal"
                                                                    @click="updateMaintenanceFrom(data)"
                                                                    title="Edit Maintenance">
                                                                    <i class="far fa-edit"></i>
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
            <!-- Add Modal Maintenance-->
            <Edit :heading="'Due Maintenance Update'" :errors="this.validationErrors" :success="success" :editForm="formID">
                <div class="row">
                    <div class=" form-group col-md-6">
                        <label for="city_id">Fleet <span class="text-danger ml-1">*</span></label>
                        <select class="form-control" v-model="postData.fleetId" disabled>
                            <option value="">Select Fleet</option>
                            <option v-for="(fleet, i) in fleets" :key="i" :value="fleet.id">
                                {{ fleet.bus_number }}
                            </option>
                        </select>
                    </div>
                    <div class=" form-group col-md-6">
                        <label for="city_id">Part <span class="text-danger ml-1">*</span></label>
                        <select class="form-control" v-model="postData.partId" disabled>
                            <option value="">Select Part</option>
                            <option v-for="(part, i) in parts" :key="i" :value="part.id">
                                {{ part.name }}
                            </option>
                        </select>
                    </div>
                    <!-- <div class="form-group col-md-6">
                    <label for="name">Current Reading <span class="text-danger ml-1">*</span></label>
                    <input
                        type="number"
                        class="form-control"
                        placeholder="Meter Reading"
                        v-model="postData.currentReading"
                    />
                </div> -->
                    <div class="form-group col-md-6">
                        <label for="name">Total Amount <span class="text-danger ml-1">*</span></label>
                        <input type="number" class="form-control" placeholder="Total Amount" v-model="postData.amount" />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Paid By Company <span class="text-danger ml-1">*</span></label>
                        <input type="number" class="form-control" placeholder="" v-model="postData.companyPaid" />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Evidence</label>
                        <input type="file" class="form-control" placeholder="" @change="evidenceImage($event)" />
                    </div>
                    <div class="form-group col-md-6">
                        <a :href="($store.state.app_url + 'uploads/maintenance/' + postData.evidence)" target="_blank">
                            <img :src="($store.state.app_url + 'uploads/maintenance/' + postData.evidence)"
                                style="width:70px;height:70px;border: 1px solid grey;" alt="">
                            Click to Prview
                        </a>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="refOfHiring">Detail <span class="text-danger ml-1">*</span></label>
                        <textarea class="form-control" v-model="postData.detail">
                        </textarea>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="dueMaintenanceUpdate"
                        :disabled="loading">{{ loading ? 'Loading...' : 'Update' }}
                    </button>
                </template>
            </Edit>
        </div>
    </section>
</template>

<script>

import Add from "../../components/Add.vue";
import Edit from "../../components/Edit.vue";
import { mapGetters } from "vuex";

export default {
    name: "RecordPage",
    components: {
        Add,
        Edit,
    },
    data() {
        return {
            formID: "maintenance_udpate",
            loading: false,
            validationErrors: [],
            mainData: [],
            fleets: [],
            parts: [],
            permissions: [],
            postData: {
                maintenanceId: '',
                fleetId: '',
                partId: '',
                currentReading: '',
                amount: '',
                companyPaid: '',
                evidence: '',
                detail: '',
                maintenanceType: '',
            },
        };
    },
    created() {
        $('.modal').remove();
        this.fetchData();
        this.permissions = this.$store.state.permissions;
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
                this.fleets = maintenanceRes.data.busDrop;
                this.parts = maintenanceRes.data.partDrop;
            }

            setTimeout(() => {
                $('#maintenance_table').DataTable();
            }, 300);
        },
        async updateMaintenanceFrom(data) {
            this.postData.maintenanceType = data.maintenance_type;
            this.postData.maintenanceId = data.id;
            this.postData.fleetId = data.bus_id;
            this.postData.partId = data.part_id;
            this.postData.currentReading = data.bus_name.current_reading;
            this.postData.amount = data.amount;
            this.postData.companyPaid = data.company_paid;
            this.postData.evidence = data.evidence;
            this.postData.detail = data.detail;
        },
        async evidenceImage(e) {
            if (e.target.files[0].name.match(/\.(jpg|jpeg|png|pdf|docx|doc)$/i)) {

                const eviImage = e.target.files[0];
                this.postData.evidence = eviImage;


            } else {
                e.target.value = '';
                this.postData.evidence = '';
                return swal({
                    title: "Invalid Format",
                    text: "Uploaded File must be in .jpg, .jpeg, .png, .pdf, .docx, .doc",
                    icon: "error",
                    timer: 2000
                });
            }
        },
        async dueMaintenanceUpdate() {
            // validation for empty data
            if (!this.postData.fleetId || !this.postData.partId || !this.postData.currentReading || !this.postData.amount ||
                !this.postData.companyPaid || !this.postData.detail) {
                return swal({
                    title: "Error",
                    text: "Please Fill All Field",
                    icon: "error",
                    timer: 2000
                });
            }

            this.loading = true;

            const config = {
                headers: { 'content-type': 'multipart/form-data' }
            }

            let formData = new FormData();
            formData.append('maintenanceId', this.postData.maintenanceId);
            formData.append('fleetId', this.postData.fleetId);
            formData.append('partId', this.postData.partId);
            formData.append('currentReading', this.postData.currentReading);
            formData.append('amount', this.postData.amount);
            formData.append('companyPaid', this.postData.companyPaid);
            formData.append('evidence', this.postData.evidence);
            formData.append('detail', this.postData.detail);
            formData.append('maintenanceType', this.postData.maintenanceType);


            const res = await this.callApi("post", "fleet/maintenance/due/update", formData, config);
            if (res.status === 200) {
                this.loading = false;
                $('#maintenance_table').DataTable().destroy();
                this.postData.maintenanceId = "";
                this.postData.maintenanceType = "";
                this.postData.fleetId = "";
                this.postData.partId = "";
                this.postData.currentReading = "";
                this.postData.amount = "";
                this.postData.companyPaid = "";
                this.postData.evidence = "";
                this.postData.detail = "";
                swal({
                    title: "Success",
                    text: "Maintenance Update",
                    icon: "success",
                    timer: 2000
                });
                await this.fetchData();
                this.loading = false;
            }
            else {
                this.loading = false;
                if (res.status == 422) {
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

.fade-enter,
.fade-leave-to

/* .fade-leave-active below version 2.1.8 */
    {
    opacity: 0;
}

table,
tr,
th,
td,
option,
select,
label,
button,
a,
div,
p {
    font-size: 14px !important;
}

.checkbox-inputs {
    position: relative;
    bottom: 10px;
}
</style>
