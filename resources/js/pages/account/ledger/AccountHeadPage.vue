<template>
    <section>
        <div class="card">
            <div class="card-header justify-content-between">
                <h4>Account Heads</h4>
                <a href="#" data-toggle="modal" :data-target="'#'+formID"
                    class="btn btn-primary">
                    Add New Ledger
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive" v-if="tableLoading">
                    <BulletListLoader></BulletListLoader>
                </div>
                <div v-else class="table-responsive">
                    <table class="table table-sm" id="head_table">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Name</th>
                                <th scope="col"><div class="w-50 m-auto">Parent Account</div></th>
                                <th scope="col">Code</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(head, index) in accountHeads" :key="index">
                                <th scope="row">{{ index + 1 }}</th>
                                <td>{{head.name}}</td>
                                <td>
                                    <div class="w-50 m-auto">
                                        <b>Level 1: </b>{{head.level_one.name}} <br>
                                        <b>Level 2: </b>{{head.level_two.name}} <br>
                                        <b>Level 3: </b>{{head.level_three.name}} <br>
                                        <b>Level 4: </b>{{head.level_four.name}}
                                    </div>
                                </td>
                                <td>
                                    {{head.level_one.code}}-
                                    {{head.level_two.code}}-
                                    {{head.level_three.code}}-
                                    {{head.level_four.code}}-
                                    {{head.code}}
                                </td>
                                <td>
                                    <button
                                            title="Edit Group" :data-target="'#' + editFormID"
                                            data-toggle="modal" @click="editHead(head)"
                                            class=" text-light btn btn-primary mx-1">
                                        <i class="far fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <Add
            heading="Add Ledger"
            :errors="this.validationErrors"
            :success="success"
            :formID="formID"
        >
            <div class="row">
                <div class="form-group col-md-3">
                    <label>Tier 1 <span class="text-danger">*</span></label>
                    <select class="form-control"
                            @change="getSecondLevel(addData.first_level)"
                            v-model="addData.first_level">
                        <option value="0" selected>Select Departure City</option>
                        <option v-for="(single, i) in firstLevel"
                                :value="single.id"
                                :key="i"
                        >
                            {{ single.name }}
                        </option>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label>Tier 2 <span class="text-danger">*</span></label>
                    <select class="form-control"
                            @change="getThirdLevel(addData.second_level)"
                            v-model="addData.second_level">
                        <option value="0" selected>Select Departure City</option>
                        <option v-for="(single, i) in secondLevel"
                                :value="single.id"
                                :key="i"
                        >
                            {{ single.name }}
                        </option>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label>Tier 3 <span class="text-danger">*</span></label>
                    <select class="form-control"
                            @change="getFourthLevel(addData.third_level)"
                            v-model="addData.third_level">
                        <option value="0" selected>Select Departure City</option>
                        <option v-for="(single, i) in thirdLevel"
                                :value="single.id"
                                :key="i"
                        >
                            {{ single.name }}
                        </option>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label>Tier 4 <span class="text-danger">*</span></label>
                    <!-- <select2 v-model="addData.fourth_level" :options="fourthLevel"
                    :settings="{ settingOption: value, settingOption: value, width: '100%' }" 
                        /> -->
                    <select class="form-control"
                            @change="getFourthLevel(addData.third_level)"
                            v-model="addData.fourth_level">
                        <option value="0" selected>Select Departure City</option>
                        <option v-for="(single, i) in fourthLevel"
                                :value="single.id"
                                :key="i"
                        >
                            {{ single.name }}
                        </option>
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label>Ledger Name <span class="text-danger">*</span></label>
                    <input class="form-control" type="text" v-model="addData.name">
                </div>
            </div>
            <template v-slot:button>
                <button type="button" class="btn btn-primary" :disabled="loading" @click="addHead">
                    {{ loading ? 'Loading...' : 'Add New Ledger' }}
                </button>
            </template>
        </Add>
        <Edit
                heading="Edit Ledger"
                :errors="this.validationErrors"
                :success="success"
                :editForm="editFormID"
            >
            <div class="row">
                <div class="form-group col-md-12">
                    <label>Ledger Name <span class="text-danger">*</span></label>
                    <input class="form-control" type="text" v-model="editData.name">
                </div>
            </div>

            <template v-slot:button>
                <button type="button" class="btn btn-primary" :disabled="loading" @click="updateHead">
                    {{ loading ? 'Loading...' : 'Update' }}
                </button>
            </template>
        </Edit>
    </section>
</template>
<script>
import Add from '../../../components/Add.vue';
import Edit from '../../../components/Edit.vue';
export default {
    name: "AccountHeadPage",
    components: {
        Add,
        Edit,
    },
    data() {
        return {
            btnLoading: false,
            tableLoading: false,
            accountHeads: [],
            firstLevel: [],
            secondLevel: [],
            thirdLevel: [],
            fourthLevel: [],
            editData: {},
            formID: "add_data",
            editFormID: "edit_data",
            addDataReset: {},
            addData: {
                name: "",
                first_level: "0",
                second_level: "0",
                third_level: "0",
                fourth_level: "0",
            },
        };
    },
    created() {
        $('.modal').remove();
        this.heads();
        setTimeout(() => {
            $('#first_level').select2();
        }, 500);
        this.addDataReset = { ...this.addData};
    },
    mounted() {
    },
    methods: {
        async heads() {
            this.tableLoading = true;
            const res = await this.callApi("get", "accounts/heads");
            if(res.status == 200)
            {
                this.accountHeads = res.data.accountHeads;
                this.firstLevel = res.data.firstLevel;
                if ($.fn.DataTable.isDataTable("#head_table")) {
                    $('#head_table').DataTable().destroy();
                }
                setTimeout(function () {
                    $("#head_table").DataTable();
                }, 300);
            }
            this.tableLoading = false;
        },
        async addHead() {
            if (this.addData.first_level == 0)
            {
                return swal({
                    title: "Required!",
                    text: "Please select tier 1",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.addData.second_level == 0)
            {
                return swal({
                    title: "Required!",
                    text: "Please select tier 2",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.addData.third_level == 0)
            {
                return swal({
                    title: "Required!",
                    text: "Please select tier 3",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.addData.fourth_level == 0)
            {
                return swal({
                    title: "Required!",
                    text: "Please select tier 4",
                    icon: "error",
                    timer: 2000
                });
            }
            if (!this.addData.name)
            {
                return swal({
                    title: "Required!",
                    text: "Name is required",
                    icon: "error",
                    timer: 2000
                });
            }
            
            this.btnLoading = true;
            const res = await this.callApi("post", "accounts/heads/add", this.addData);
            if (res.status === 201) {
                this.heads();
                this.addData = { ...this.addDataReset};
                $(".modal").click();
                swal({
                    icon: 'success',
                    title: 'Success',
                    text: 'Successfully Added',
                });
            }else {
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
            this.btnLoading = false;
        },
        async editHead(head) {
            this.editData = head;
            $("#editHead").modal('show');
        },
        async updateHead() {
            if(!this.editData.name)
            {
                return swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'Name is required',
                });
            }
        
            this.btnLoading = true;
            const res = await this.callApi("post", "accounts/heads/update", this.editData);
            if (res.status === 200) {
                this.heads();
                $(".modal").click();
                swal({
                    icon: 'success',
                    title: 'Success',
                    text: 'Successfully Updated',
                });
            }else {
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
            this.btnLoading = false;
        },
        async getSecondLevel(id) {
            const res = await this.callApi("get", 'accounts/'+id+'/second');
            if (res.status == 200) {
                this.secondLevel = res.data.secondLevel
            }
        },
        async getThirdLevel(id) {
            const res = await this.callApi("get", 'accounts/groups/'+id+'/third');
            if (res.status == 200) {
                this.thirdLevel = res.data.thirdLevel
            }
        },
        async getFourthLevel(id) {
            const res = await this.callApi("get", 'accounts/groups/'+id+'/fourth');
            if (res.status == 200) {
                this.fourthLevel = res.data.fourthLevel
            }
        },
    }
};
</script>
