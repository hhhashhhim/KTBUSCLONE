<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header">
                            <h4>Report Headers Link</h4>
                            <!-- <div class="card-header-action">
                                <a href="#" data-toggle="modal" :data-target="'#'+formID" @click="clearForm()" class="btn btn-primary">
                                    Add New Category
                                </a>
                            </div> -->
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
                                                        <th>Header</th>
                                                        <th>Value</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(item,index) in headers" :key="index">
                                                        <td>
                                                            {{saveRow(item.id,"first",index)}}
                                                            <select class="form-control rounded-0"
                                                                :disabled="editAble" :value="postData.headers[index]">
                                                                <option :value="item.id" :key="i"
                                                                    >
                                                                    {{ item.name }}
                                                                </option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            {{saveRow(postData.values[index]??0,"second",index)}}
                                                            <input type="number" min="0" class="form-control"
                                                            :value="postData.values[index]"
                                                                :disabled="editAble" @keyup="saveRow($event,'third',index)"/>
                                                        </td>
                                                    </tr>
                                                    </tbody>
                                                </table>

                                                <div class="d-flex justify-content-end">
                                                    <button type="button" class="btn btn-outline-success mr-4"
                                                            @click="add" :disabled="loading" v-if="!editAble">
                                                        {{ loading ? 'Loading...' : 'Save' }}
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary mr-4"
                                                            @click="editAble=false" :disabled="loading" v-else>Edit
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

            <!--Daily Summery Report Form-->
            <form :action="$store.state.app_url + 'print/pdf/daily/summary/report'" method="POST"
                  ref="refDailySummaryReport"
                  target="_blank">
                <input type="hidden" name="_token" v-bind:value="csrf">
                <input type="hidden" name="ticket_merge_id" :value="this.postData.ticket_merge_id">
            </form>


            <!-- Add Modal -->
            <!-- <Add
            heading="Add New Category"
            :errors="this.validationErrors"
            :success="success"
            :formID="formID"
            >
                <div class="form-group">
                    <label for="name">Name <span class="text-danger ml-1">*</span></label>
                    <input type="text" class="form-control" placeholder="Enter Category Name" v-model="data.name">
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" :disabled="loading" @click="add">{{ loading ? 'Loading...': 'Add New Category' }}</button>
                </template>
            </Add> -->

            <!-- Add Modal -->
            <!-- <Edit
            heading="Edit Category Name"
            :errors="this.validationErrors"
            :success="success"
            :editForm="editFormID"
            >
                <div class="form-group">
                    <label for="name">Name <span class="text-danger ml-1">*</span></label>
                    <input type="text" class="form-control" placeholder="Enter Category Name" v-model="dataEdit.name">
                </div>

                <template v-slot:button>
                    <button type="button" class="btn btn-primary" :disabled="loading" @click="update">{{ loading ? 'Loading...': 'Update Category' }}</button>
                </template>
            </Edit> -->

            <!-- Add Modal -->
            <!-- <Delete :deleteForm="deleteFormID" confirmationMessage="Are You Sure You want To Delete This City ???" /> -->

        </div>
    </section>


</template>

<script>
// import Add from '../../components/Add.vue';
// import Edit from '../../components/Edit.vue';
// import Delete from '../../components/Delete.vue';
import {mapGetters} from 'vuex';

export default {
    name: "HeaderLink",
    components: {
        // Add,
        // Edit,
        // Delete,
    },
    data() {
        return {
            csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            validationErrors: [],
            editAble: false,
            headers: [],
            loading: false,
            formID: 'expense_form',
            editFormID: 'edit_expense_form',
            // deleteFormID:'delete_city_form',
            totalAmount: 0,
            postData: {
                ticket_merge_id: "",
                headers: [],
                values: [],
            },
            // dataEdit:{
            //     id:"",
            //     name:"",
            // },
            // delId:"",
            success: false,
            errors: false,
            loop: 1,
        }
    },
    async created() {
         this.fetchData();
         this.existingExpenses();
        setTimeout(function () {
            $("#expense_table").DataTable();
        }, 300);
    },
    methods: {
        clearForm: function () {
            this.data = {};
        },
        async fetchData() {
            const res = await this.callApi("post", 'expenses/categories');
            if (res.status == 200) {
                this.headers = res.data;
            }

            this.postData.ticket_merge_id = this.$route.params.id;
        },
        async existingExpenses() {
            // const res = await this.callApi("post", 'expenses', {ticket_merge_id: this.postData.ticket_merge_id});
            // if (res.status == 200) {
            //     const expenses = res.data;
            //     if (expenses != "") {
            //         this.loop = expenses.length;
            //         for (var i = 0; i < expenses.length; i++) {
            //             this.postData.category.push(expenses[i].expense_category_id);
            //             this.postData.description.push(expenses[i].description);
            //             this.postData.amount.push(expenses[i].amount);
            //             this.postData.invoice.push(expenses[i].invoice);
            //         }
            //     } else {
            //         this.loop = 1;
            //         this.editAble = true;
            //     }
            // }
        },
        saveRow(value, fieldName, index) {
            
            if (fieldName == "first") {
                this.postData.headers[index] = value;
            }
            if (fieldName == "second") {
                this.postData.values[index] = parseFloat(value != "" ? value: 0);
                }
            if (fieldName == "third") {
                this.postData.values[index] = parseFloat(event.target.value != "" ? value.target.value: 0);
            }
        },
        async add() {


            this.loading = true;
            const res = await this.callApi("post", "expenses/store", this.postData);
            if (res.status === 200) {
                this.loading = false;
                // $('#expense').DataTable().destroy();
                this.postData.category = [];
                this.postData.description = [];
                this.postData.amount = [];
                this.postData.invoice = [];
                this.loop = 0;
                this.editAble = true;
                swal({
                    title: "Success",
                    text: "Expense Saved",
                    icon: "success",
                    timer: 2000
                });
                this.$refs.refDailySummaryReport.submit();
                 this.fetchData();
                 this.existingExpenses();
                this.loading = false;
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
    },
    computed: {
        ...mapGetters(['getDeletingObj'])
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.cities.splice(obj.index, 1)
                $("#expense_table").DataTable().destroy();
                this.fetchData();
                this.existingExpenses();
            }
        }
    }
}
</script>
