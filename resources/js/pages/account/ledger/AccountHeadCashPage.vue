<template>
    <section>
        <div class="card">
            <div class="card-header justify-content-between">
                <h4>Account Head For Cash</h4>
                <a href="#" data-toggle="modal" :data-target="'#'+formID"
                    class="btn btn-primary">
                    Add Cash Ledger
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive" v-if="tableLoading">
                    <BulletListLoader></BulletListLoader>
                </div>
                <div v-else class="table-responsive">
                    <table class="table table-sm" id="head_cash_table">
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
                            <tr v-for="(head, index) in accountHeadCash" :key="index">
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
                                            data-toggle="modal"  @click="editHeadCash(head)"
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
            heading="Add Cash Ledger"
            :errors="this.validationErrors"
            :success="success"
            :formID="formID"
        >
            <div class="row">
                <div class="form-group col-md-12">
                    <label>Cash Ledger Name <span class="text-danger">*</span></label>
                    <input class="form-control" type="text" v-model="addData.name">
                </div>
            </div>
            <template v-slot:button>
                <button type="button" class="btn btn-primary" :disabled="loading" @click="addHeadCash">
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
                <div class="form-group col-md-12">
                    <label>Bank Name <span class="text-danger">*</span></label>
                    <input class="form-control" type="text" v-model="editData.name">
                </div>
            </div>

            <template v-slot:button>
                <button type="button" class="btn btn-primary" :disabled="loading" @click="updateHeadCash">
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
    name: "AccountHeadCashPage",
    components: {
        Add,
        Edit,
    },
    data() {
        return {
            btnLoading: false,
            tableLoading: false,
            formID: "add_data",
            editFormID: "edit_data",
            accountHeadCash: [],
            editData: {
                head_cash: {},
            },
            addDataReset: {},
            addData: {
                name: "",
            },
        };
    },
    created() {
        $('.modal').remove();
        this.headCash();
        this.addDataReset = { ...this.addData};
    },
    mounted() {
    },
    methods: {
        async headCash() {
            this.tableLoading = true;
            const res = await this.callApi("get", "accounts/heads/cash");
            if(res.status == 200)
            {
                this.accountHeadCash = res.data.accountHeadCash;
                if ($.fn.DataTable.isDataTable("#head_cash_table")) {
                    $('#head_cash_table').DataTable().destroy();
                }
                setTimeout(function () {
                    $("#head_cash_table").DataTable();
                }, 300);
            }
            this.tableLoading = false;
        },
        async addHeadCash() {
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
            const res = await this.callApi("post", "accounts/heads/cash/add", this.addData);
            if (res.status === 201) {
                this.headCash();
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
        async editHeadCash(head) {
            this.editData = head;
            $("#editHeadCash").modal('show');
        },
        async updateHeadCash() {
            if (!this.editData.name)
            {
                return swal({
                    title: "Required!",
                    text: "Name is required",
                    icon: "error",
                    timer: 2000
                });
            }

            this.btnLoading = true;
            const res = await this.callApi("post", "accounts/heads/cash/update", this.editData);
            if (res.status === 200) {
                this.headCash();
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
