<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h4>Commission Closing Detail</h4>
                        </div>
                        <div class="card-body">
                            
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-end">
                                            </div>
                                            <div class="table-responsive">
                                                <table
                                                    class="table table-striped table-hover"
                                                    id="closing_table"
                                                    style="border-collapse: separate;
                                                    border-spacing: 0 10px;"
                                                >
                                                    <thead>
                                                    <tr>
                                                        <th>Bus Number</th>
                                                        <th>Schedule</th>
                                                        <th>Route Name</th>
                                                        <th>Schedule Date</th>
                                                        <th>Schedule Time</th>
                                                        <th v-if="checkForSubmenuButtons('edit-close-booking')">Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <template v-for="(data, i) in closings" :key="i">
                                                        <tr v-for="(close, j) in data" :key="j">
                                                            <td class="h5"
                                                                :class="data.length == 2 ? j == 1 ? 'border-left border-bottom border-success' : 'border-left border-top border-success' : 'border-left border-bottom border-top border-danger'">
                                                                {{ close.bus.bus_number }}
                                                            </td>
                                                            <td class="h5"
                                                                :class="data.length == 2 ? j == 1 ? 'border-bottom border-success' : 'border-top border-success' : 'border-bottom border-top border-danger'">
                                                                {{ close.schedule.name }}
                                                            </td>
                                                            <td class="h5"
                                                                :class="data.length == 2 ? j == 1 ? 'border-bottom border-success' : 'border-top border-success' : 'border-bottom border-top border-danger'">
                                                                {{ close.schedule.route.name }}
                                                            </td>
                                                            <td class="h5"
                                                                :class="data.length == 2 ? j == 1 ? 'border-bottom border-success' : 'border-top border-success' : 'border-bottom border-top border-danger'">
                                                                {{ close.schedule_date }}
                                                            </td>
                                                            <td class="h5"
                                                                :class="data.length == 2 ? j == 1 ? 'border-bottom border-success' : 'border-top border-success' : 'border-bottom border-top border-danger'">
                                                                {{ close.schedule_time }}
                                                            </td>
                                                            <td
                                                                :class="data.length == 2 ? j == 1 ? 'border-bottom border-right border-success' : 'border-right border-top border-success' : 'border-bottom border-right border-top border-danger'">
                                                                <button title="Delete Unclosing"
                                                                        :data-target="'#' + hideFormID" @click="delId = close.id" data-toggle="modal"
                                                                        class="btn btn-danger btn-sm mx-2"
                                                                >
                                                                <i class="far fas fa-trash"></i>
                                                                </button>
                                                                <a v-if="close.account_transaction.length == 0" href="#" class="mr-1 btn btn-success" @click="journalModal(close.id)">Closing</a>
                                                            </td>
                                                        </tr>
                                                    </template>
                                                    <tr v-if="closings.length==0">
                                                        <td class="text-center" colspan="6">No data found</td>
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

            <NewTransaction
                :btnLoading="btnLoading"
                :addData="addData"
                :terminals="terminals"
                :heads="heads"
                ref="childComponentRef"
                @add="addJournalTransaction"
            />

            <Hide :hideForm="hideFormID" confirmationMessage="Are You Sure You want To Delete This Closing ???">
                <template v-slot:button>
                    <button
                        type="button"
                        class="btn btn-danger btn-block"
                       :disabled="loading" @click="hideUnclosing"
                    >
                    {{ loading ? 'Loading...' : 'Yes, I want to Delete' }}
                    </button>
                </template>
            </Hide>
        </div>
    </section>
</template>

<script>

import {mapGetters} from "vuex";
import NewTransaction from '../../components/schedule/NewJournalComponent.vue';
import Hide from "../../components/Hide.vue";
export default {
    name: "unclosing",
    components: {
        NewTransaction,
        Hide
    },
    data() {
        return {
            btnLoading: false,
            loading: false,
            closings: [],
            permissions: [],
            validationErrors: "",
            formID: "schedule_closing_form",
            hideFormID: "hide_schedule_form",
            delId: "",
            seatNo: 0,
            terminals: [],
            heads: [],
            addDataReset: {},
            addData: {
                terminal: "0",
                closeId: "0",
                ledgers: [],
                credits: [],
                debits: [],
                narrations: [],
            },
            success: false,
            errors: false,
        };
    },
    async created() {
        this.addDataReset = JSON.parse(JSON.stringify(this.addData));
        $('.modal').remove();
        const currentRouteName = this.$route.name;
        if (currentRouteName == 'booking-page') {
            window.addEventListener('keydown', this.enterKey);
            window.addEventListener('keydown', this.altM);
        } else {
            window.removeEventListener('keydown', this.enterKey);
            window.removeEventListener('keydown', this.altM);
        }

        this.fetchData();
        this.journalTransactionData();
        this.permissions = this.$store.state.permissions;
    },
    methods: {
        clearForm: function () {
            this.data = {};
        },
        async journalTransactionData() {
            this.tableLoading = true;
            const res = await this.callApi("post", "booking/close/schedule/helper");
            if(res.status == 200)
            {
                this.terminals = res.data.terminals;
                this.heads = res.data.heads;
                if ($.fn.DataTable.isDataTable("#transaction_table")) {
                    $('#transaction_table').DataTable().destroy();
                }
                setTimeout(function () {
                    $("#transaction_table").DataTable();
                }, 300);
            }
            this.tableLoading = false;
        },
        async fetchData() {
            const res = await this.callApi("post", "booking/close/schedule/closing/commission");
            if (res.status == 200) {
                this.closings = res.data.closings;
            } else {
                console.log(res);
            }
        },

        async hideUnclosing() {
            
            this.loading = true;
            const resHide = await this.callApi("post", 'booking/close/schedule/unclosing/hide', {id:this.delId});
            if (resHide.status == 200) {
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "Unclosing Deleted Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.loading = false;
                this.fetchData();
            } else {
                if (resHide.status == 422) {
                    this.loading = false;
                    for (const key in resHide.data.errors) {
                        resHide.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
                setTimeout(() => {
                    this.loading = false
                }, 3000);
            }
        },
        async journalModal(id) {
            this.addData.closeId = id;
            $("#newTransaction").modal('show');
        },
        async addJournalTransaction() {
            for (let i = 0; i < this.addData.credits.length; i++) {
                if (this.addData.ledgers[i] === 0 || (this.addData.credits[i] === 0 && this.addData.debits[i] === 0) || this.addData.narrations[i] === "") {
                    swal({
                        icon: 'error',
                        title: 'Error',
                        text: 'Please fill all field of every row or remove extra row',
                    });
                    return false;
                }
                if (this.addData.credits[i] === this.addData.debits[i]) {
                    swal({
                        icon: 'error',
                        title: 'Error',
                        text: 'Please enter some amount for credit/debit and second one should be 0',
                    });
                    return false;
                }
            }
            if (this.addData.ledgers.length < 2){
                swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please enter at least two record',
                });
                return false;
            }
            if(this.addData.credits.reduce((acc, current) => acc + parseFloat(current), 0) != this.addData.debits.reduce((acc, current) => acc + parseFloat(current), 0))
            {
                swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'Sum of debit and credit should be equal',
                });
                return false;
            }
            if (this.indicateDuplication(this.addData.ledgers))
            {
                swal({
                    icon: 'error',
                    title: 'Error',
                    text: "Please remove duplicate entry",
                });
                return false;
            }
            this.btnLoading = true;
            const res = await this.callApi("post", "booking/close/schedule/closing/commission/store", this.addData);
            if (res.status === 201) {
                this.fetchData();
                this.addData = JSON.parse(JSON.stringify(this.addDataReset));
                this.$refs.childComponentRef.transactionLoop = 1;
                this.$refs.childComponentRef.finalData = {
                    total_amount : 0,
                    credit : 0,
                    debit : 0,
                };
                $(".modal").modal('hide');
                swal({
                    icon: 'success',
                    title: 'Success',
                    text: 'Successfully Added',
                });
            }
            this.btnLoading = false;
        },
        indicateDuplication(data) {
            const values = data.filter((item, index) => data.indexOf(item) !== index)

            if(values.length > 0)
            {
                return true;
            }
            return false
            
        },
    },
    computed: {
        ...mapGetters(["getDeletingObj"]),
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.buses.splice(obj.index, 1);
                $('#closing_table').DataTable().destroy();
                this.fetchBuses();
            }
        },
    },
};
</script>
<style src="@vueform/multiselect/themes/default.css"></style>

