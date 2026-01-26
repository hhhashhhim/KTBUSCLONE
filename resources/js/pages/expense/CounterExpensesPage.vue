<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header">
                            <h4>Counter Expenses</h4>
                            <div class="card-header-action">
                                <a href="#" data-toggle="modal" :data-target="'#' + formID" @click="clearForm()"
                                    class="btn btn-primary" v-if="checkForSubmenuButtons('add-counter-expenses')">
                                    Add Counter Expenses
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover"
                                                    id="counter_expenses_table">
                                                    <thead>
                                                        <tr>
                                                            <th>Sr No.</th>
                                                            <th>Attachments</th>
                                                            <th>Amount</th>
                                                            <th>Payment Method</th>
                                                            <th>Narration</th>
                                                            <th v-if="checkForSubmenuButtons('edit-counter-expenses')">
                                                                Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(single, i) in counterExpenses" :key="i">
                                                            <td>{{ i + 1 }}</td>
                                                          <td v-if="single.bill_post">
    <template v-if="isImage(single.bill_post)">
        <a :href="API_URL + 'storage/' + single.bill_post" target="_blank">
            <img :src="API_URL + 'storage/' + single.bill_post"
                 style="width:80px;height:80px;object-fit:cover;" alt="Bill Image">
        </a>
    </template>
    <template v-else>
        <a :href="API_URL + 'storage/' + single.bill_post" target="_blank">
            {{ getFileName(single.bill_post) }}
        </a>
    </template>
</td>

                                                            <td>{{ single.amount }}</td>
                                                            <td>{{ single.payment_method }}</td>
                                                            <td>{{ single.narration }}</td>
                                                            <td v-if="checkForSubmenuButtons('edit-counter-expenses')">
                                                                <button
                                                                    v-if="checkForSubmenuButtons('edit-counter-expenses')"
                                                                    title="Edit Expenses"
                                                                    :data-target="'#' + editFormID" data-toggle="modal"
                                                                    @click="edit(single)"
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
                                </div>
                            </div>
                            <!-- END TABLE -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Modal -->
            <Add heading="Add Counter Expenses" :errors="validationErrors" :success="success" :formID="formID">
                <div class="row">
                    <!-- Amount -->
                    <div class="form-group col-md-4">
                        <label for="amount">Amount <span class="text-danger ml-1">*</span></label>
                        <input type="text" class="form-control" placeholder="Enter Specific Amount"
                            v-model="data.amount" @keypress="isNumber($event)">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Bill Posting <span class="text-danger ml-1">(Optional)</span></label>
                        <input class="form-control" type="file" @change="handleBillPost">
                    </div>

                    <!-- Cash / Bank -->
                    <div class="form-group col-md-4">
                        <label>Payment Method <span class="text-danger ml-1">*</span></label>
                        <div class="d-flex align-items-center mt-2">
                            <div class="form-check mr-4">
                                <input class="form-check-input" type="radio" id="cash" value="cash"
                                    v-model="data.payment_method">
                                <label class="form-check-label" for="cash">Cash</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" id="bank" value="bank"
                                    v-model="data.payment_method">
                                <label class="form-check-label" for="bank">Bank</label>
                            </div>
                        </div>
                    </div>
                    <!-- Narration -->
                    <div class="form-group col-md-12">
                        <label for="narration">Narration <span class="text-danger ml-1">*</span></label>
                        <textarea class="form-control" placeholder="Describe Narration"
                            v-model="data.narration"></textarea>
                    </div>


                </div>

                <!-- Button -->
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" :disabled="loading" @click="add()">
                        {{ loading ? 'Loading...' : 'Add Counter Expenses' }}
                    </button>
                </template>
            </Add>


            <!-- Add Modal -->
           <Edit heading="Edit Counter Expenses" :errors="validationErrors" :success="success"
      :editForm="editFormID">
    <div class="row">

        <!-- Amount -->
        <div class="form-group col-md-4">
            <label>Amount <span class="text-danger ml-1">*</span></label>
            <input type="text" class="form-control" placeholder="Enter Specific Amount"
                   v-model="dataEdit.amount" @keypress="isNumber($event)">
        </div>
           <!-- Bill Image -->
        <div class="form-group col-md-4">
            <label>Bill Posting <span class="text-danger ml-1">(Optional)</span></label>
            <input type="file" class="form-control" @change="handleEditBillPost">
            
            <!-- Show current bill if exists -->
            <!-- <div v-if="dataEdit.bill_post" class="mt-2">
                <a :href="API_URL + 'storage/' + dataEdit.bill_post" target="_blank">
                    <img :src="API_URL + 'storage/' + dataEdit.bill_post"
                         style="width:100px;height:100px;object-fit:cover;" alt="Bill Image">
                </a>
            </div> -->
        </div>
 <!-- Payment Method -->
        <div class="form-group col-md-4">
            <label>Payment Method <span class="text-danger ml-1">*</span></label>
            <div class="d-flex align-items-center mt-2">
                <div class="form-check mr-4">
                    <input type="radio" class="form-check-input" id="edit_cash" value="cash"
                           v-model="dataEdit.payment_method">
                    <label class="form-check-label" for="edit_cash">Cash</label>
                </div>
                <div class="form-check">
                    <input type="radio" class="form-check-input" id="edit_bank" value="bank"
                           v-model="dataEdit.payment_method">
                    <label class="form-check-label" for="edit_bank">Bank</label>
                </div>
            </div>
        </div>

     
        <!-- Narration -->
        <div class="form-group col-md-12">
            <label>Narration <span class="text-danger ml-1">*</span></label>
            <textarea class="form-control" placeholder="Describe Narration"
                      v-model="dataEdit.narration"></textarea>
        </div>
    </div>

    <!-- Button -->
    <template v-slot:button>
        <button type="button" class="btn btn-primary" :disabled="loadingEdit" @click="update()">
            {{ loadingEdit ? 'Loading...' : 'Update Counter Expenses' }}
        </button>
    </template>
</Edit>

        </div>
    </section>


</template>

<script>
import Add from '../../components/Add.vue';
import Edit from '../../components/Edit.vue';

export default {
    name: "CounterExpensesPage",
    components: {
        Add,
        Edit,
    },
    data() {
        return {
            API_URL: process.env.MIX_API_URL,
            validationErrors: [],
            counterExpenses: [],
            permissions: [],
            loading: false,
            loadingEdit: false,
            formID: 'counter_expenses',
            editFormID: 'edit_counter_expenses',
            deleteFormID: 'delete_counter_expenses',
            data: {
                amount: "",
                payment_method: "",
                bill_post: null,
                narration: "",
            },
            dataEdit: {
                amount: "",
                narration: "",
                payment_method: "",
                bill_post: null,
            },
            delId: "",
            success: false,
            errors: false,
        }
    },
    async created() {
        $('.modal').remove();
        await this.fetchCounterExpenses();
        this.permissions = this.$store.state.permissions;
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
         isImage(file) {
        const imageExtensions = ['jpg','jpeg','png','gif','bmp','webp'];
        const name = typeof file == 'string' ? file : file.name;
        const ext = name.split('.').pop().toLowerCase();
        return imageExtensions.includes(ext);
    },
    getFileName(file) {
        // Return filename from path
        return typeof file == 'string' ? file.split('/').pop() : file.name;
    },
        handleBillPost(e) {
            this.data.bill_post = e.target.files[0];
        },
        handleEditBillPost(e) {
    this.dataEdit.bill_post = e.target.files[0];
},
        clearForm: function () {
            this.data = {};
        },
        async fetchCounterExpenses() {
            const resCounterExpenses = await this.callApi("post", 'counter/expenses');
            if (resCounterExpenses.status == 200) {
                this.counterExpenses = resCounterExpenses.data;
            }
            setTimeout(function () {
                $("#counter_expenses_table").DataTable();
            }, 300);
        },
        isNumber: function (evt) {
            evt = (evt) ? evt : window.event;
            var charCode = (evt.which) ? evt.which : evt.keyCode;
            if ((charCode > 31 && (charCode < 48 || charCode > 57)) && charCode !== 46) {
                evt.preventDefault();
            } else {
                return true;
            }
        },
       async add() {
    this.validationErrors = [];

    // ✅ Basic Validations
    if (!this.data.amount) {
        return swal({
            title: "Required",
            text: "Expenses Amount is Required",
            icon: "error",
            timer: 2000
        });
    }

    if (!this.data.narration) {
        return swal({
            title: "Required",
            text: "Expenses Narration is Required",
            icon: "error",
            timer: 2000
        });
    }

    if (!this.data.payment_method) {
        return swal({
            title: "Required",
            text: "Payment Method is Required",
            icon: "error",
            timer: 2000
        });
    }

    this.loading = true;

    try {
        // 🔥 Prepare FormData for files
        const formData = new FormData();
        formData.append('amount', this.data.amount);
        formData.append('narration', this.data.narration);
        formData.append('payment_method', this.data.payment_method);

        // Only append bill_post if a file is selected
        if (this.data.bill_post instanceof File) {
            formData.append('bill_post', this.data.bill_post);
        }

        // Call API
        const resCounter = await this.callApi(
            "post",
            "counter/expenses/store",
            formData,
            { headers: { "Content-Type": "multipart/form-data" } }
        );

        // ✅ Success
        if (resCounter.status === 201) {
            $(".modal").click();

            swal({
                title: "Success",
                text: "Expenses Added Successfully",
                icon: "success",
                timer: 2000
            });

            $("#counter_expenses_table").DataTable().destroy();
            this.fetchCounterExpenses();
            this.clearForm();
        }
    } catch (error) {
        // ✅ Handle Validation Errors from backend
        if (error.response && error.response.status === 422) {
            this.validationErrors = error.response.data.errors || {};
            let errorContent = "";
            let count = 0;

            for (const key in this.validationErrors) {
                this.validationErrors[key].forEach(msg => {
                    errorContent += (++count) + " - " + msg + "\n";
                });
            }

            swal({
                title: "Error",
                text: errorContent,
                icon: "error",
                timer: 3000
            });
        } else {
            // Other errors
            swal({
                title: "Error",
                text: "Something went wrong. Please try again.",
                icon: "error",
                timer: 3000
            });
        }
    } finally {
        this.loading = false; // always turn off loading
    }
},

        edit(singleRecord) {
            this.dataEdit = singleRecord;
        },
        async update() {
    this.validationErrors = [];

    if (!this.dataEdit.amount)
        return swal("Required", "Expenses Amount is Required", "error");

    if (!this.dataEdit.narration)
        return swal("Required", "Expenses Narration is Required", "error");

    if (!this.dataEdit.payment_method)
        return swal("Required", "Payment Method is Required", "error");

    this.loadingEdit = true;

    const formData = new FormData();
    formData.append('id', this.dataEdit.id);
    formData.append('amount', this.dataEdit.amount);
    formData.append('narration', this.dataEdit.narration);
    formData.append('payment_method', this.dataEdit.payment_method);

    if (this.dataEdit.bill_post instanceof File) {
        formData.append('bill_post', this.dataEdit.bill_post);
    }

    const resEdit = await this.callApi(
        "post",
        'counter/expenses/update',
        formData,
        { headers: { "Content-Type": "multipart/form-data" } }
    );

    if (resEdit.status === 200) {
        $(".modal").click();
        swal("Success", "Expenses updated Successfully", "success");
        this.loadingEdit = false;
        $("#counter_expenses_table").DataTable().destroy();
        this.fetchCounterExpenses();
        setTimeout(() => { $('#edit-modal').modal('hide'); }, 3000);
    } else if (resEdit.status === 422) {
        this.loadingEdit = false;
        let errorContent = "";
        let count = 0;
        for (const key in resEdit.data.errors) {
            resEdit.data.errors[key].forEach(el => {
                errorContent += (++count) + " - " + el + "\n";
            });
        }
        swal("Error", errorContent, "error");
    }
},
    },
}
</script>
