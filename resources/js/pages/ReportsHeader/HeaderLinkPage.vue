<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header">
                            <h4>Report expenseHeader Link</h4>
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
                                                    <tr v-for="(item,index) in expenseHeader" :key="index">
                                                        <td>
                                                            {{ expenseSaveRow(item.id, "first", index) }}
                                                            <select class="form-control rounded-0"
                                                                    :disabled="expenseEditAble"
                                                                    :value="expensePostData.expenseHeadIds[index]">
                                                                <option :value="item.id" :key="i"
                                                                >
                                                                    {{ item.name }}
                                                                </option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            {{ expenseSaveRow(expensePostData.values[index] ?? 0, "second", index) }}
                                                            <input type="number" min="0" class="form-control"
                                                                   :value="expensePostData.values[index]"
                                                                   :disabled="expenseEditAble"
                                                                   @keyup="expenseSaveRow($event,'third',index)"/>
                                                        </td>
                                                    </tr>
                                                    </tbody>
                                                </table> 

                                                <div class="d-flex justify-content-end">
                                                    <button type="button" class="btn btn-outline-success mr-4"
                                                            @click="expenseAdd" :disabled="loading" v-if="!expenseEditAble">
                                                        {{ loading ? 'Loading...' : 'Save' }}
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary mr-4"
                                                            @click="expenseEditAble=false" :disabled="loading" v-else>Edit
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
            <form :action="$store.state.api_url + 'api/web/v1/print/pdf/daily/summary/report'" method="POST"
                  ref="refDailySummaryReport"
                  target="_blank">
                <input type="hidden" name="token" :value="this.$store.state.token">
                <input type="hidden" name="ticket_merge_id" :value="this.expensePostData.ticket_merge_id">
            </form>
        </div>
    </section>
</template>

<script>
// import expenseAdd from '../../components/expenseAdd.vue';
// import Edit from '../../components/Edit.vue';
// import Delete from '../../components/Delete.vue';
import {mapGetters} from 'vuex';

export default {
    name: "HeaderLink",
    components: {
        // expenseAdd,
        // Edit,
        // Delete,
    },
    data() {
        return {
            validationErrors: [],
            expenseEditAble: true,
            expenseHeader: [],
            loading: false,
            formID: 'expense_form',
            editFormID: 'edit_expense_form',
            // deleteFormID:'delete_city_form',
            totalAmount: 0,
            expensePostData: {
                ticket_merge_id: "",
                expenseHeadIds: [],
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
        $('.modal').remove();
        const currentRouteName = this.$route.name;
        if (currentRouteName == 'booking-page') {
            window.addEventListener('keydown', this.enterKey);
            window.addEventListener('keydown', this.altM);
        } else {
            window.removeEventListener('keydown', this.enterKey);
            window.removeEventListener('keydown', this.altM);
        }
        this.expensePostData.ticket_merge_id = this.$route.params.id;
        this.fetchExpenseData();
        setTimeout(function () {
            $("#header_table").DataTable();
        }, 300);
    },

    methods: {
        clearForm: function () {
            this.data = {};
        },
        async fetchExpenseData() {
            const res = await this.callApi("post", 'reportsHeader/link/get', {ticket_merge_id: this.expensePostData.ticket_merge_id});
            if (res.status == 200) {
                this.expenseHeader = res.data.expenseHeader;

                if (res.data.links != null) {
                    this.expensePostData.values = [];
                    for (var i = 0; i < res.data.links.length; i++) {
                        this.expensePostData.values.push(res.data.links[i].value);
                    }
                }
            }

            this.expensePostData.ticket_merge_id = this.$route.params.id;
        },
        expenseSaveRow(value, fieldName, index) {

            if (fieldName == "first") {
                this.expensePostData.expenseHeadIds[index] = value;
            }
            if (fieldName == "second") {
                this.expensePostData.values[index] = parseFloat(value != "" ? value : 0);
            }
            if (fieldName == "third") {
                this.expensePostData.values[index] = parseFloat(event.target.value != "" ? value.target.value : 0);
            }
        },
        async expenseAdd() {
            this.loading = true;
            const res = await this.callApi("post", "reportsHeader/link", this.expensePostData);
            if (res.status === 200) {
                this.loading = false;
                // $('#expense').DataTable().destroy();
                this.expensePostData.expenseHeadIds = [];
                this.expensePostData.values = [];
                this.expenseEditAble = true;
                swal({
                    title: "Success",
                    text: "Header Saved",
                    icon: "success",
                    timer: 2000
                });
                this.fetchExpenseData();
                this.loading = false;
                setTimeout(() => {
                    window.close();
                }, 3000);

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
                $("#header_table").DataTable().destroy();
                this.fetchExpenseData();
                this.existingExpenses();
            }
        }
    }
}
</script>
