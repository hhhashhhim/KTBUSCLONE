<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header text-capitalize">
                            <h4>{{ terminal.name }} Terminal Commission</h4>
                        </div>
                        <div class="card-body">
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">

                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-striped">
                                                    <thead>
                                                    <tr>
                                                        <th style="width:200px">Route</th>
                                                        <th>Fix Commission</th>
                                                        <th>Flat Commission</th>
                                                        <th>Percentage Commission</th>
                                                        <th>KT Commission</th>
                                                        <th style="width:200px">Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(i, index) in loop" :key="index">
                                                        <td>
                                                            <!-- {{ items[0] ? items[0].price : '' }} -->
                                                            <select class="form-control rounded-0"
                                                                    @change="saveRow($event, 'first', index)"
                                                                    :value="postData.route[index]" :disabled="editAble">
                                                                <option value="" selected>Select Route</option>
                                                                <option v-for="(route, i) in routes"
                                                                        :value="route.id" :key="i">
                                                                    {{ route.name }}  ({{ route.via??'n/a' }})
                                                                </option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control"
                                                                   @keypress="numberValidate($event,{dot:true})"
                                                                   @keyup="saveRow($event, 'second', index)"
                                                                   placeholder="Rs"
                                                                   :value="postData.fixCommission[index]"
                                                                   :disabled="editAble"/>
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control border-secondary"
                                                                   @keypress="numberValidate($event,{dot:true})"
                                                                   @keyup="saveRow($event, 'third', index)"
                                                                   placeholder="Rs"
                                                                   :value="postData.flatCommission[index]"
                                                                   :disabled="editAble"/>
                                                        </td>
                                                        <td>
                                                            <input type="text" min="0"
                                                                   @keypress="numberValidate($event,{dot:true})"
                                                                   class="form-control border-secondary"
                                                                   @keyup="saveRow($event, 'fourth', index)"
                                                                   placeholder="%"
                                                                   :value="postData.percentCommission[index]"
                                                                   :disabled="editAble"/>
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control"
                                                                   @keypress="numberValidate($event,{dot:true})"
                                                                   @keyup="saveRow($event, 'fifth', index)"
                                                                   placeholder="%"
                                                                   :value="postData.adjustmentCommission[index]"
                                                                   :disabled="editAble"/>
                                                        </td>
                                                        <td v-if="!editAble">
                                                            <button class="btn btn-outline-primary mx-2"
                                                                    @click="addRow">Add
                                                            </button>
                                                            <button class="btn btn-outline-danger"
                                                                    @click="removeRow($event, index)"
                                                                    v-if="loop != 1">Remove
                                                            </button>
                                                        </td>
                                                        <td v-else></td>
                                                    </tr>
                                                    </tbody>
                                                </table>
                                                <div class="d-flex justify-content-end">
                                                    <button type="button" class="btn btn-outline-success mr-4"
                                                            @click="add" :disabled="loading" v-if="!editAble">{{
                                                            loading ?
                                                                'Loading...' : 'Save'
                                                        }}
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary mr-4"
                                                            @click="editAble = false" :disabled="loading" v-else>Edit
                                                    </button>
                                                    <button type="button" class="btn btn-outline-primary mr-4"
                                                            @click="editAble=true"
                                                            v-if="!editAble && postData.route.length != 0">Cancel
                                                    </button>
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
// import Add from '../../components/Add.vue';
// import Edit from '../../components/Edit.vue';
// import Delete from '../../components/Delete.vue';
import {mapGetters} from 'vuex';

export default {
    name: "TerminalCommissionPage",
    components: {
        // Add,
        // Edit,
        // Delete,
    },
    data() {
        return {
            validationErrors: [],
            editAble: true,
            routes: [],
            terminal: [],
            loading: false,
            formID: 'terminal_commission',
            editFormID: 'edit_terminal_commission',
            postData: {
                terminal_id: "",
                route: [],
                fixCommission: [],
                flatCommission: [],
                percentCommission: [],
                adjustmentCommission: [],
            },
            success: false,
            errors: false,
            loop: 1,
        }
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
        $(".modal").click();
        this.fetchData();
        this.existingCommissions();
        setTimeout(function () {
            $("#commission_table").DataTable();
        }, 300);
    },

    methods: {
        clearForm: function () {
            this.data = {};
        },
        async fetchData() {
            this.postData.terminal_id = this.$route.params.id;

            const res = await this.callApi("post", 'terminals/routes');
            if (res.status == 200) {
                this.routes = res.data;
            }

        },
        async existingCommissions() {
            const res = await this.callApi("post", 'terminals/commissions', {terminal_id: this.postData.terminal_id});
            if (res.status == 200) {
                const commissions = res.data.terminalCommission;
                this.terminal = res.data.terminal;
                if (commissions != "") {
                    this.loop = commissions.length;
                    for (var i = 0; i < commissions.length; i++) {
                        this.postData.route.push(commissions[i].route_id);
                        this.postData.fixCommission.push(commissions[i].fix_commission);
                        this.postData.flatCommission.push(commissions[i].flat_commission);
                        this.postData.percentCommission.push(commissions[i].percentage_commission);
                        this.postData.adjustmentCommission.push(commissions[i].adjustment_commission);
                    }
                } else {
                    this.loop = 1;
                    this.editAble = false;
                }
            }
        },
        saveRow(event, fieldName, index) {
            // const getRowNumber = event.target.parentElement.parentElement.rowIndex;
            if (fieldName == "first") {
                this.postData.route[index] = event.target.value;
            }
            if (fieldName == "second") {
                this.postData.fixCommission[index] = event.target.value;
            }
            if (fieldName == "third") {
                this.postData.flatCommission[index] = event.target.value;
            }
            if (fieldName == "fourth") {
                this.postData.percentCommission[index] = event.target.value;
            }
            if (fieldName == "fifth") {
                this.postData.adjustmentCommission[index] = event.target.value;
            }
        },
        addRow() {
            this.loop++;
        },
        removeRow(event, index) {
            this.postData.route.splice(index, 1);
            this.postData.fixCommission.splice(index, 1);
            this.postData.flatCommission.splice(index, 1);
            this.postData.percentCommission.splice(index, 1);
            this.postData.adjustmentCommission.splice(index, 1);
            this.loop--;
        },
        closeTab() {
            window.close();
        },
        async add() {

            // validation for empty data
            if (!this.postData.terminal_id || this.postData.route.length == 0 || this.postData.fixCommission.length == 0 ||
                this.postData.flatCommission.length == 0 || this.postData.percentCommission.length == 0 || this.postData.adjustmentCommission.length == 0) {
                return swal({
                    title: "Error",
                    text: "Please Fill All Field",
                    icon: "error",
                    timer: 2000
                });
            }

            // check if any index is empty or null in object
            for (var i = 0; i < this.postData.route.length; i++) {
                if (!this.postData.route[i] || !this.postData.fixCommission[i] || !this.postData.flatCommission[i] ||
                    !this.postData.percentCommission[i] || !this.postData.adjustmentCommission[i]) {
                    return swal({
                        title: "Error",
                        text: "Please Fill All Field Or Remove Extra",
                        icon: "error",
                        timer: 2000
                    });
                }

                if (this.postData.flatCommission[i] != 0 && this.postData.percentCommission[i] != 0) {
                    return swal({
                        title: "Error",
                        text: "Flat Commission or Percentage Commission should be 0 against single route",
                        icon: "error",
                        timer: 5000
                    });
                }
            }

            this.loading = true;
            const res = await this.callApi("post", "terminals/commissions/store", this.postData);
            if (res.status === 200) {
                this.loading = false;
                this.postData.route = [];
                this.postData.fixCommission = [];
                this.postData.flatCommission = [];
                this.postData.percentCommission = [];
                this.postData.adjustmentCommission = [];
                this.loop = 0;
                this.editAble = true;
                swal({
                    title: "Success",
                    text: "Commission Updated",
                    icon: "success",
                    timer: 2000
                });
                 this.fetchData();
                 this.existingCommissions();
                this.loading = false;
                setTimeout(() => this.closeTab(), 1000);
            } else {
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
        numberValidate(event, { dot = false, maxLen = null, negative = false, comma = false } = {}) {
        
            const charCode = event.charCode;
            const value = event.target.value.toString().replace(/,/g, '');
            
            // Allow numbers (48-57), dot (46), and control keys (0)
            if ((charCode >= 48 && charCode <= 57) || charCode === 0) {
        
                // Check the length if it's not null
                if (maxLen !== null && value.length >= maxLen) {
                    event.preventDefault();
                    return false;
                }

                return true;
            }
            // Accept dot
            if (dot && charCode === 46) {
                // Allow only one dot
                if (value.includes('.')) {
                    event.preventDefault();
                    return false;
                }
        
                // Check the length if it's not null
                if (maxLen !== null && value.length >= maxLen) {
                    event.preventDefault();
                    return false;
                }
                return true;
            }
            // Accept negative value
            if (negative && charCode === 45) {
                if (value.includes('-') || value.length !== 0) {
                    event.preventDefault();
                    return false;
                }
                return true;
            }
        
            event.preventDefault();
            return false;
        },
    }
    ,
    computed: {
        ...
            mapGetters(['getDeletingObj'])
    }
    ,
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.cities.splice(obj.index, 1)
                $("#commission_table").DataTable().destroy();
                this.fetchData();
                this.existingCommissions();
            }
        }
    }
}
</script>
