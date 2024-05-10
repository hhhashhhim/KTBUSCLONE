<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header">
                            <h4>Office Expenses</h4>
                            <div class="card-header-action">
                                <a href="#" data-toggle="modal" :data-target="'#'+formID" @click="clearForm()"
                                   class="btn btn-primary">
                                    Add Office Expenses
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
                                                       id="office_expenses_table">
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Amount</th>
                                                        <th>Date</th>
                                                        <th>Narration</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(single,i) in officeExpenses" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ single.amount }}</td>
                                                        <td>{{ single.closing_date }}</td>
                                                        <td>{{ single.narration }}</td>
                                                        <td>
                                                            <button
                                                                title="Edit Expenses" :data-target="'#' + editFormID"
                                                                data-toggle="modal" @click="edit(single)"
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
            <Add
                heading="Add Counter Expenses"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row">
                    <div class=" form-group col-md-6">
                        <label for="name">Closing Date <span class="text-danger ml-1">*</span></label>
                        <input type="date" class="form-control" placeholder="Enter Specific Amount"
                               v-model="data.date">
                    </div>
                    <div class=" form-group col-md-6">
                        <label for="name">Amount <span class="text-danger ml-1">*</span></label>
                        <input type="number" class="form-control" placeholder="Enter Specific Amount"
                               v-model="data.amount">
                    </div>
                    <div class=" form-group col-md-12">
                        <label for="name">Narration <span class="text-danger ml-1">*</span></label>
                        <textarea class="form-control" placeholder="Describe Narration"
                                  v-model="data.narration"></textarea>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" :disabled="loading" @click="add()">
                        {{ loading ? 'Loading...' : 'Add Office Expenses' }}
                    </button>
                </template>
            </Add>

            <!-- Add Modal -->
            <Edit
                heading="Edit Office Expenses"
                :errors="this.validationErrors"
                :success="success"
                :editForm="editFormID"
            >
                <div class="row">
                    <div class=" form-group col-md-6">
                        <label for="name">Date <span class="text-danger ml-1">*</span></label>
                        <input type="date" class="form-control" placeholder=""
                               v-model="dataEdit.closing_date">
                    </div>
                    <div class=" form-group col-md-6">
                        <label for="name">Amount <span class="text-danger ml-1">*</span></label>
                        <input type="number" class="form-control" placeholder="Enter Specific Amount"
                               v-model="dataEdit.amount">
                    </div>
                    <div class=" form-group col-md-12">
                        <label for="name">Narration <span class="text-danger ml-1">*</span></label>
                        <textarea class="form-control" placeholder="Describe Narration"
                                  v-model="dataEdit.narration"></textarea>
                    </div>
                </div>

                <template v-slot:button>
                    <button type="button" class="btn btn-primary" :disabled="loadingEdit" @click="update()">
                        {{ loadingEdit ? 'Loading...' : 'Update Office Expenses' }}
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
    name: "OfficeExpensesPage",
    components: {
        Add,
        Edit,
    },
    data() {
        return {
            validationErrors: [],
            officeExpenses: [],
            permissions: [],
            loading: false,
            loadingEdit: false,
            formID: 'counter_expenses',
            editFormID: 'edit_counter_expenses',
            deleteFormID: 'delete_counter_expenses',
            data: {
                date: "",
                amount: "",
                narration: "",
            },
            dataEdit: {
                amount: "",
                narration: "",
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
        clearForm: function () {
            this.data = {};
        },
        async fetchCounterExpenses() {
            const resOfficeExpenses = await this.callApi("post", 'office/expenses');
            if (resOfficeExpenses.status == 200) {
                this.officeExpenses = resOfficeExpenses.data;
            }
            setTimeout(function () {
                $("#office_expenses_table").DataTable();
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
            this.validationErrors = []
            if (!this.data.date)
                return swal({
                    title: "Required",
                    text: "Closing Date is Required",
                    icon: "error",
                    timer: 2000
                });
            if (!this.data.amount)
                return swal({
                    title: "Required",
                    text: "Expenses Amount is Required",
                    icon: "error",
                    timer: 2000
                });
            if (!this.data.narration)
                return swal({
                    title: "Required",
                    text: "Expenses Narration is Required",
                    icon: "error",
                    timer: 2000
                });
            this.loading = true
            const resCounter = await this.callApi("post", 'office/expenses/store', this.data);
            if (resCounter.status == 200) {
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "Expenses Added Successfully",
                    icon: "success",
                    timer: 2000
                });
                $("#office_expenses_table").DataTable().destroy();
                this.loading = false;
                this.fetchCounterExpenses();
                this.clearForm();
            } else {
                if (resCounter.status == 422) {
                    this.loading = false;
                    let errorContent = "";
                    let count = 0;
                    for (const key in resCounter.data.errors) {
                        resCounter.data.errors[key].forEach((element) => {
                            errorContent += ((++count) + " - " + element + "\n");
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
        edit(singleRecord) {
            this.dataEdit = singleRecord;
        },
        async update() {
            this.validationErrors = []
            if (!this.dataEdit.closing_date)
                return swal({
                    title: "Required",
                    text: "Closing Date is Required",
                    icon: "error",
                    timer: 2000
                });
            if (!this.dataEdit.amount)
                return swal({
                    title: "Required",
                    text: "Expenses Amount is Required",
                    icon: "error",
                    timer: 2000
                });
            if (!this.dataEdit.narration)
                return swal({
                    title: "Required",
                    text: "Expenses Narration is Required",
                    icon: "error",
                    timer: 2000
                });
            this.loadingEdit = true;
            const resEdit = await this.callApi("post", 'office/expenses/update', this.dataEdit);
            if (resEdit.status == 200) {
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "Expenses updated Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.loadingEdit = false;
                $("#office_expenses_table").DataTable().destroy();
                this.fetchCounterExpenses();
                setTimeout(() => {
                    $('#edit-modal').modal('hide')
                }, 3000);
            } else {
                if (resEdit.status == 422) {
                    this.loadingEdit = false;
                    let errorContent = "";
                    let count = 0;
                    for (const key in resEdit.data.errors) {
                        resEdit.data.errors[key].forEach((element) => {
                            errorContent += ((++count) + " - " + element + "\n");
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
}
</script>

