<template>
    <section>
        <div class="card">
            <div class="card-header justify-content-between">
                <h4>Account Head For Banks</h4>
                <a href="#" data-toggle="modal" :data-target="'#'+formID"
                    class="btn btn-primary">
                    Add Bank Ledger
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive" v-if="tableLoading">
                    <BulletListLoader></BulletListLoader>
                </div>
                <div v-else class="table-responsive">
                    <table class="table table-sm" id="head_bank_table">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Name</th>
                                <th scope="col">Account Number</th>
                                <th scope="col">IBAN</th>
                                <th scope="col">Address</th>
                                <th scope="col"><div class="w-50 m-auto">Parent Account</div></th>
                                <th scope="col">Code</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(head, index) in accountHeadBanks" :key="index">
                                <th scope="row">{{ index + 1 }}</th>
                                <td>{{head.name}}</td>
                                
                                <td>{{head.head_bank.account_number}}</td>
                                <td>{{head.head_bank.iban}}</td>
                                <td>{{head.head_bank.address}}</td>
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
                                            data-toggle="modal"  @click="editHeadBank(head)"
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
            heading="Add Bank Ledger"
            :errors="this.validationErrors"
            :success="success"
            :formID="formID"
        >
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Bank Name <span class="text-danger">*</span></label>
                    <input class="form-control" type="text" v-model="addData.name">
                </div>
                <div class="form-group col-md-6">
                    <label>Bank Address <span class="text-danger">*</span></label>
                    <input class="form-control" type="text" v-model="addData.address">
                </div>
                <div class="form-group col-md-6">
                    <label>IBAN <span class="text-danger">*</span></label>
                    <input class="form-control" type="text" v-model="addData.iban">
                </div>
                <div class="form-group col-md-6">
                    <label>Account Number <span class="text-danger">*</span></label>
                    <input class="form-control" type="text" v-model="addData.account_number">
                </div>
            </div>
            <template v-slot:button>
                <button type="button" class="btn btn-primary" :disabled="loading" @click="addHeadBank">
                    {{ loading ? 'Loading...' : 'Add New Ledger' }}
                </button>
            </template>
        </Add>
        <Edit
                heading="Edit Bank Ledger"
                :errors="this.validationErrors"
                :success="success"
                :editForm="editFormID"
            >
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Bank Name <span class="text-danger">*</span></label>
                    <input class="form-control" type="text" v-model="editData.name">
                </div>
                <div class="form-group col-md-6">
                    <label>Bank Address <span class="text-danger">*</span></label>
                    <input class="form-control" type="text" v-model="editData.head_bank.address">
                </div>
                <div class="form-group col-md-6">
                    <label>IBAN <span class="text-danger">*</span></label>
                    <input class="form-control" type="text" v-model="editData.head_bank.iban">
                </div>
                <div class="form-group col-md-6">
                    <label>Account Number <span class="text-danger">*</span></label>
                    <input class="form-control" type="text" v-model="editData.head_bank.account_number">
                </div>
            </div>

            <template v-slot:button>
                <button type="button" class="btn btn-primary" :disabled="loading" @click="updateHeadBank">
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
    name: "AccountHeadBankPage",
    components: {
        Add,
        Edit,
    },
    data() {
        return {
            btnLoading: false,
            tableLoading: false,
            accountHeadBanks: [],
            formID: "add_data",
            editFormID: "edit_data",
            editData: {
                head_bank: {},
            },
            addDataReset: {},
            addData: {
                name: "",
                address: "",
                iban: "",
                account_number: "",
            },
        };
    },
    created() {
        $('.modal').remove();
        this.headBanks();
        this.addDataReset = { ...this.addData};
    },
    mounted() {
    },
    methods: {
        async headBanks() {
            this.tableLoading = true;
            const res = await this.callApi("get", "accounts/heads/banks");
            if(res.status == 200)
            {
                this.accountHeadBanks = res.data.accountHeadBanks;
                if ($.fn.DataTable.isDataTable("#head_bank_table")) {
                    $('#head_bank_table').DataTable().destroy();
                }
                setTimeout(function () {
                    $("#head_bank_table").DataTable();
                }, 300);
            }
            this.tableLoading = false;
        },
        async addHeadBank() {
            if (!this.addData.name)
            {
                return swal({
                    title: "Required!",
                    text: "Name is required",
                    icon: "error",
                    timer: 2000
                });
            }
            if (!this.addData.address)
            {
                return swal({
                    title: "Required!",
                    text: "Address is required",
                    icon: "error",
                    timer: 2000
                });
            }
            if (!this.addData.iban)
            {
                return swal({
                    title: "Required!",
                    text: "IBAN is required",
                    icon: "error",
                    timer: 2000
                });
            }
            if (!this.addData.account_number)
            {
                return swal({
                    title: "Required!",
                    text: "Account number is required",
                    icon: "error",
                    timer: 2000
                });
            }
            
            this.btnLoading = true;
            const res = await this.callApi("post", "accounts/heads/banks/add", this.addData);
            if (res.status === 201) {
                this.headBanks();
                this.addData = { ...this.addDataReset};
                $(".modal").click();
                swal({
                    icon: 'success',
                    title: 'Success',
                    text: 'Successfully Added',
                });
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
            this.btnLoading = false;
        },
        async editHeadBank(head) {
            this.editData = head;
            $("#editHeadBank").modal('show');
        },
        async updateHeadBank() {
            if (!this.editData.name)
            {
                return swal({
                    title: "Required!",
                    text: "Name is required",
                    icon: "error",
                    timer: 2000
                });
            }
            if (!this.editData.head_bank.address)
            {
                return swal({
                    title: "Required!",
                    text: "Address is required",
                    icon: "error",
                    timer: 2000
                });
            }
            if (!this.editData.head_bank.iban)
            {
                return swal({
                    title: "Required!",
                    text: "IBAN is required",
                    icon: "error",
                    timer: 2000
                });
            }
            if (!this.editData.head_bank.account_number)
            {
                return swal({
                    title: "Required!",
                    text: "Account number is required",
                    icon: "error",
                    timer: 2000
                });
            }
        
            this.btnLoading = true;
            const res = await this.callApi("post", "accounts/heads/banks/update", this.editData);
            if (res.status === 200) {
                this.headBanks();
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
    }
};
</script>
