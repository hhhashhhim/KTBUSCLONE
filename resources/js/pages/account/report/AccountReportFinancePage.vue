<template>
    <section>
        <div class="card">
            <div class="card-header justify-content-between">
                <h4>Finance Reports</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2 text-center">
                        <button class="btn btn-block btn-info rounded-0" @click="abstep = 1; filter = true">
                            Receipt
                        </button>
                    </div>
                    <div class="col-md-2 text-center">
                        <button class="btn btn-block btn-info rounded-0" @click="abstep = 2; filter = true">
                            General Ledger
                        </button>
                    </div>
                    <div class="col-md-2 text-center">
                        <button class="btn btn-block btn-info rounded-0" @click="abstep = 3; filter = true">
                            Ledger
                        </button>
                    </div>
                    <div class="col-md-2 text-center">
                        <button class="btn btn-block btn-info rounded-0" @click="abstep = 4; filter = true">
                            General journal
                        </button>
                    </div>
                    <div class="col-md-2 text-center">
                        <button class="btn btn-block btn-info rounded-0" @click="abstep = 5; filter = true">
                            Trial Sheet
                        </button>
                    </div>
                    <div class="col-md-2 text-center">
                        <button class="btn btn-block btn-info rounded-0" @click="abstep = 6; filter = true">
                            Daily Report
                        </button>
                    </div>
                    <div class="col-md-2 text-center mt-4">
                        <button class="btn btn-block btn-info rounded-0" @click="showAlert()">
                            P/L Statement
                        </button>
                    </div>
                    <div class="col-md-2 text-center mt-4">
                        <button class="btn btn-block btn-info rounded-0" @click="showAlert()">
                            Balance Sheet
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" v-if="abstep==1 && filter == true">
            <div class="card-header justify-content-between">
                <h4>Receipt Filter</h4>
            </div>
            <div class="card-body">
                <div>
                    <div class="row">
                        <div class="form-group col-md-3">
                            <label>Terminal</label>
                            <select class="form-control" v-model="filterData.terminal">
                                <option value="0">Select Bus Class</option>
                                <option
                                    v-for="(terminal, i) in terminals"
                                    :key="i"
                                    :value="terminal.id"
                                >
                                    {{ terminal.name }}
                                </option>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label>From</label>
                            <input type="date" class="form-control" v-model="filterData.from"/>
                        </div>
                        <div class="form-group col-md-3">
                            <label>To</label>
                            <input type="date" class="form-control" v-model="filterData.to"/>
                        </div>
                        <div class="form-group col-md-3">
                            <button type="button" class="btn btn-block btn-primary"
                            style="margin-top: 1.9rem !important"
                            :class="{ 'disabled btn-progress': btnLoading }" @click=receiptReport()>Filter</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card" v-if="abstep==2 && filter == true">
            <div class="card-header justify-content-between">
                <h4>General Ledger</h4>
            </div>
            <div class="card-body">
                <div>
                    <div class="row">
                        <div class="form-group col-md-3">
                            <label>Terminal</label>
                            <select class="form-control" v-model="filterData.terminal">
                                <option value="0">Select Bus Class</option>
                                <option
                                    v-for="(terminal, i) in terminals"
                                    :key="i"
                                    :value="terminal.id"
                                >
                                    {{ terminal.name }}
                                </option>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label>General Ledger <span class="text-danger">*</span></label>
                            <select class="form-control" v-model="filterData.level_four">
                                <option value="0">Select Bus Class</option>
                                <option
                                    v-for="(fourth, i) in fourth_level"
                                    :key="i"
                                    :value="fourth.id"
                                >
                                    {{ fourth.name }}
                                </option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label>From</label>
                            <input type="date" class="form-control" v-model="filterData.from"/>
                        </div>
                        <div class="form-group col-md-2">
                            <label>To</label>
                            <input type="date" class="form-control" v-model="filterData.to"/>
                        </div>
                        <div class="form-group col-md-2">
                            <button type="button" class="btn btn-block btn-primary"
                            style="margin-top: 1.9rem !important"
                            :class="{ 'disabled btn-progress': btnLoading }" @click=generalLedgerReport()>Filter</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card" v-if="abstep==3 && filter == true">
            <div class="card-header justify-content-between">
                <h4>Ledger</h4>
            </div>
            <div class="card-body">
                <div>
                    <div class="row">
                        <div class="form-group col-md-3">
                            <label>Terminal</label>
                            <select class="form-control" v-model="filterData.terminal">
                                <option value="0">Select Bus Class</option>
                                <option
                                    v-for="(terminal, i) in terminals"
                                    :key="i"
                                    :value="terminal.id"
                                >
                                    {{ terminal.name }}
                                </option>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Ledger <span class="text-danger">*</span></label>
                            <select class="form-control" v-model="filterData.head">
                                <option value="0">Select Bus Class</option>
                                <option
                                    v-for="(head, i) in heads"
                                    :key="i"
                                    :value="head.id"
                                >
                                    {{ head.name }}
                                </option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label>From</label>
                            <input type="date" class="form-control" v-model="filterData.from"/>
                        </div>
                        <div class="form-group col-md-2">
                            <label>To</label>
                            <input type="date" class="form-control" v-model="filterData.to"/>
                        </div>
                        <div class="form-group col-md-2">
                            <button type="button" class="btn btn-block btn-primary"
                            style="margin-top: 1.9rem !important"
                            :class="{ 'disabled btn-progress': btnLoading }" @click=ledgerReport()>Filter</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" v-if="abstep==4 && filter == true">
            <div class="card-header justify-content-between">
                <h4>General Journal</h4>
            </div>
            <div class="card-body">
                <div>
                    <div class="row">
                        <div class="form-group col-md-3">
                            <label>Terminal</label>
                            <select class="form-control" v-model="filterData.terminal">
                                <option value="0">Select Bus Class</option>
                                <option
                                    v-for="(terminal, i) in terminals"
                                    :key="i"
                                    :value="terminal.id"
                                >
                                    {{ terminal.name }}
                                </option>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label>From</label>
                            <input type="date" class="form-control" v-model="filterData.from"/>
                        </div>
                        <div class="form-group col-md-3">
                            <label>To</label>
                            <input type="date" class="form-control" v-model="filterData.to"/>
                        </div>
                        <div class="form-group col-md-3">
                            <button type="button" class="btn btn-block btn-primary"
                            style="margin-top: 1.9rem !important"
                            :class="{ 'disabled btn-progress': btnLoading }" @click=journalReport()>Filter</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card" v-if="abstep==5 && filter == true">
            <div class="card-header justify-content-between">
                <h4>Trial Sheet</h4>
            </div>
            <div class="card-body">
                <div>
                    <div class="row">
                        <div class="form-group col-md-3">
                            <label>Terminal</label>
                            <select class="form-control" v-model="filterData.terminal">
                                <option value="0">Select Bus Class</option>
                                <option
                                    v-for="(terminal, i) in terminals"
                                    :key="i"
                                    :value="terminal.id"
                                >
                                    {{ terminal.name }}
                                </option>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label>From</label>
                            <input type="date" class="form-control" v-model="filterData.from"/>
                        </div>
                        <div class="form-group col-md-3">
                            <label>To</label>
                            <input type="date" class="form-control" v-model="filterData.to"/>
                        </div>
                        <div class="form-group col-md-3">
                            <button type="button" class="btn btn-block btn-primary"
                            style="margin-top: 1.9rem !important"
                            :class="{ 'disabled btn-progress': btnLoading }" @click=trialSheetReport()>Filter</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" v-if="abstep==6 && filter == true">
            <div class="card-header justify-content-between">
                <h4>Daily Report</h4>
            </div>
            <div class="card-body">
                <div>
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label>Terminal</label>
                            <select class="form-control" v-model="filterData.terminal">
                                <option value="0">Select Bus Class</option>
                                <option
                                    v-for="(terminal, i) in terminals"
                                    :key="i"
                                    :value="terminal.id"
                                >
                                    {{ terminal.name }}
                                </option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Date</label>
                            <input type="date" class="form-control" v-model="filterData.current"/>
                        </div>
                        <div class="form-group col-md-4">
                            <button type="button" class="btn btn-block btn-primary"
                            style="margin-top: 1.9rem !important"
                            :class="{ 'disabled btn-progress': btnLoading }" @click=dailyReport()>Filter</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Receipt -->
        <Receipt
            v-if="abstep==1 && filter == false"
            :btnLoading="btnLoading"
            :voucher="voucher"
            :receipts="receipts"
            ref="childComponentRef"
            @print-voucher="printVoucher"
            @print="printReceiptSlip"
        />
        <!-- General Ledger -->
        <GeneralLedger
            v-if="abstep==2 && filter == false"
            :btnLoading="btnLoading"
            :voucher="voucher"
            :general_ledgers="general_ledgers"
            ref="childComponentRef"
            @print-voucher="printVoucher"
            @print="printAccountLedgerReport"
        />
        <!-- Ledger -->
        <Ledger
            v-if="abstep==3 && filter == false"
            :btnLoading="btnLoading"
            :voucher="voucher"
            :ledgers="ledgers"
            ref="childComponentRef"
            @print-voucher="printVoucher"
            @print="printLedgerReport"
        />
        <!-- General Journal -->
        <Journal
            v-if="abstep==4 && filter == false"
            :btnLoading="btnLoading"
            :voucher="voucher"
            :journals="journals"
            ref="childComponentRef"
            @print-voucher="printVoucher"
            @print="printJournalReport"
        />
        <!-- Trial Sheet -->
        <TrialSheet
            v-if="abstep==5 && filter == false"
            :btnLoading="btnLoading"
            :voucher="voucher"
            :data="data"
            ref="childComponentRef"
            @print="printGeneralTrialSheetReport"
        />
        <!-- Daily Report -->
        <DailyReport
            v-if="abstep==6 && filter == false"
            :btnLoading="btnLoading"
            :voucher="voucher"
            :daily_data="daily_data"
            ref="childComponentRef"
            @print-voucher="printVoucher"
            @print="printDailyReport"
        />
        <!-- External Form Submission -->
        <form :action="`${this.$store.state.api_url}api/web/v1/accounts/transactions/data/receipts/pdf`" method="post" ref="printReceiptPdf" target="_blank">
            <input type="hidden" name="token" :value="this.$store.state.token">
            <input type="hidden" name="terminal" :value="this.filterData.terminal">
            <input type="hidden" name="id" :value="this.voucher.id">
            <input type="hidden" name="type" :value="this.voucher.type">
            <input type="hidden" name="posting_id" :value="this.voucher.posting_id">
        </form>
        
        <!-- External General Ledger -->
        <form :action="`${this.$store.state.api_url}api/web/v1/accounts/transactions/data/general/ledger/pdf`" method="post" ref="printAccountLedgerReportPdf" target="_blank">
            <input type="hidden" name="token" :value="this.$store.state.token">
            <input type="hidden" name="terminal" :value="this.filterData.terminal">
            <input type="hidden" name="level_four" :value="this.filterData.level_four">
            <input type="hidden" name="from" :value="this.filterData.from">
            <input type="hidden" name="to" :value="this.filterData.to">
        </form>
        
        <!-- External Ledger -->
        <form :action="`${this.$store.state.api_url}api/web/v1/accounts/transactions/data/ledger/pdf`" method="post" ref="printLedgerReportPdf" target="_blank">
            <input type="hidden" name="token" :value="this.$store.state.token">
            <input type="hidden" name="terminal" :value="this.filterData.terminal">
            <input type="hidden" name="head" :value="this.filterData.head">
            <input type="hidden" name="from" :value="this.filterData.from">
            <input type="hidden" name="to" :value="this.filterData.to">
        </form>

        <!-- External General Journal -->
        <form :action="`${this.$store.state.api_url}api/web/v1/accounts/transactions/data/general/journal/pdf`" method="post" ref="printJournalReportPdf" target="_blank">
            <input type="hidden" name="token" :value="this.$store.state.token">
            <input type="hidden" name="terminal" :value="this.filterData.terminal">
            <input type="hidden" name="from" :value="this.filterData.from">
            <input type="hidden" name="to" :value="this.filterData.to">
        </form>
        
        <!-- External Trial Report -->
        <form :action="`${this.$store.state.api_url}api/web/v1/accounts/transactions/data/general/trial/pdf`" method="post" ref="printGeneralTrialReportPdf" target="_blank">
            <input type="hidden" name="token" :value="this.$store.state.token">
            <input type="hidden" name="terminal" :value="this.filterData.terminal">
            <input type="hidden" name="from" :value="this.filterData.from">
            <input type="hidden" name="to" :value="this.filterData.to">
        </form>
        
        <!-- External Daily Report -->
        <form :action="`${this.$store.state.api_url}api/web/v1/accounts/transactions/data/daily/report/pdf`" method="post" ref="printDailyReportPdf" target="_blank">
            <input type="hidden" name="token" :value="this.$store.state.token">
            <input type="hidden" name="terminal" :value="this.filterData.terminal">
            <input type="hidden" name="current" :value="this.filterData.current">
        </form>
        <!-- Voucher Pdf -->
        <form :action="`${this.$store.state.api_url}api/web/v1/accounts/transactions/data/pdf`" method="post" ref="transactionFormPdf" target="_blank">
            <input type="hidden" name="token" :value="this.$store.state.token">
            <input type="hidden" name="id" :value="this.voucher.id">
            <input type="hidden" name="type" :value="this.voucher.type">
        </form>
    </section>
</template>
<script>
import Receipt from '../../../components/account/report/finance/ReceiptReportComponent.vue';
import GeneralLedger from '../../../components/account/report/finance/GeneralLedgerReportComponent.vue';
import Ledger from '../../../components/account/report/finance/LedgerReportComponent.vue';
import Journal from '../../../components/account/report/finance/JournalReportComponent.vue';
import TrialSheet from '../../../components/account/report/finance/TrialSheetReportComponent.vue';
import DailyReport from '../../../components/account/report/finance/DailyReportComponent.vue';
export default {
    name: "AccountReportFinancePage",
    components: {
        Receipt,
        GeneralLedger,
        Ledger,
        Journal,
        TrialSheet,
        DailyReport,
    },
    data() {
        return {
            btnLoading: false,
            tableLoading: false,
            abstep: 0,
            filter: false,
            fourth_level: [],
            receipts: [],
            general_ledgers: [],
            ledgers: [],
            journals: [],
            data: [],
            terminals: [],
            voucher: {
                id: null,
                type: null,
                posting_id: null,
            },
            editData: {
            },
            filterDataReset: {},
            filterData: {
                terminal: "0",
                head: "0",
                level_four: "0",
                from: "",
                to: "",
                current: "",
            },
        };
    },
    created() {
        const today = new Date().toISOString().slice(0, 10); // Get the current date in YYYY-MM-DD format
        this.filterData.from = today;
        this.filterData.to = today;
        this.filterData.current = today;
        this.csrf = $('meta[name=csrf-token]').attr('content');
        this.filterDataReset = JSON.parse(JSON.stringify(this.filterData));
        this.helperData();
    },
    mounted() {
    },
    computed: {
    },
    methods: {
        async helperData() {
            this.tableLoading = true;
            const res = await this.callApi("get", "accounts/reports/helper/data");
            if(res.status == 200)
            {
                this.fourth_level = res.data.fourth_level;
                this.heads = res.data.heads;
                this.terminals = res.data.terminals;
                this.journals = res.data.journals;
            }
            this.tableLoading = false;
        },
        async receiptReport() {
            if(!this.filterData.from)
            {
                return swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select from date',
                });
            }
            if(!this.filterData.to)
            {
                return swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select to date',
                });
            }
            this.tableLoading = true;
            const res = await this.callApi("post", "accounts/reports/finance/receipts",this.filterData);
            if(res.status == 200)
            {
                this.filter = false;
                this.abstep = 1;
                this.receipts = res.data.receipts;
                if ($.fn.DataTable.isDataTable("#receipt_table")) {
                    $('#receipt_table').DataTable().destroy();
                }
                setTimeout(function () {
                    $("#receipt_table").DataTable();
                }, 300);
            }
            this.tableLoading = false;
        },
        async generalLedgerReport() {
            if(this.filterData.level_four == 0)
            {
                return swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select general ledger',
                });
            }
            if(!this.filterData.from)
            {
                return swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select from date',
                });
            }
            if(!this.filterData.to)
            {
                return swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select to date',
                });
            }
            this.tableLoading = true;
            const res = await this.callApi("post", "accounts/reports/finance/general/ledger",this.filterData);
            if(res.status == 200)
            {
                this.filter = false;
                this.abstep = 2;
                this.general_ledgers = res.data.general_ledgers;
                if ($.fn.DataTable.isDataTable("#general_table")) {
                    $('#general_table').DataTable().destroy();
                }
                setTimeout(function () {
                    $("#general_table").DataTable({
                        "pageLength": -1, // Show all entries
                        "lengthMenu": [[-1], ["All"]], // Remove the default options and add 'All'
                        "paging": false, // Optionally, remove the pagination controls
                    });
                }, 300);
            }
            this.tableLoading = false;
        },
        async ledgerReport() {
            if(this.filterData.head == 0)
            {
                return swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select ledger',
                });
            }
            if(!this.filterData.from)
            {
                return swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select from date',
                });
            }
            if(!this.filterData.to)
            {
                return swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select to date',
                });
            }
            this.tableLoading = true;
            const res = await this.callApi("post", "accounts/reports/finance/ledger",this.filterData);
            if(res.status == 200)
            {
                this.filter = false;
                this.abstep = 3;
                this.ledgers = res.data.ledgers;
                if ($.fn.DataTable.isDataTable("#ledger_table")) {
                    $('#ledger_table').DataTable().destroy();
                }
                setTimeout(function () {
                    $("#ledger_table").DataTable({
                        "pageLength": -1, // Show all entries
                        "lengthMenu": [[-1], ["All"]], // Remove the default options and add 'All'
                        "paging": false, // Optionally, remove the pagination controls
                    });
                }, 300);
            }
            this.tableLoading = false;
        },
        async journalReport() {
            if(!this.filterData.from)
            {
                return swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select from date',
                });
            }
            if(!this.filterData.to)
            {
                return swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select to date',
                });
            }
            this.tableLoading = true;
            const res = await this.callApi("post", "accounts/reports/finance/general/journal",this.filterData);
            if(res.status == 200)
            {
                this.filter = false;
                this.abstep = 4;
                this.journals = res.data.journals;
                if ($.fn.DataTable.isDataTable("#journal_table")) {
                    $('#journal_table').DataTable().destroy();
                }
                setTimeout(function () {
                    $("#journal_table").DataTable({
                        "pageLength": -1, // Show all entries
                        "lengthMenu": [[-1], ["All"]], // Remove the default options and add 'All'
                        "paging": false, // Optionally, remove the pagination controls
                    });
                }, 300);
            }
            this.tableLoading = false;
        },
        async trialSheetReport() {
            if(!this.filterData.from)
            {
                return swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select from date',
                });
            }
            if(!this.filterData.to)
            {
                return swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select to date',
                });
            }
            this.tableLoading = true;
            const res = await this.callApi("post", "accounts/reports/finance/trial/sheet",this.filterData);
            if(res.status == 200)
            {
                this.filter = false;
                this.abstep = 5;
                this.data = res.data.data;
                if ($.fn.DataTable.isDataTable("#trial_table")) {
                    $('#trial_table').DataTable().destroy();
                }
                setTimeout(function () {
                    $("#trial_table").DataTable({
                        "pageLength": -1, // Show all entries
                        "lengthMenu": [[-1], ["All"]], // Remove the default options and add 'All'
                        "paging": false, // Optionally, remove the pagination controls
                        "ordering": false // Disable sorting
                    });
                }, 300);
            }
            this.tableLoading = false;
        },
        async dailyReport() {
            if(!this.filterData.current)
            {
                return swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select date',
                });
            }
            this.tableLoading = true;
            const res = await this.callApi("post", "accounts/reports/finance/daily/report",this.filterData);
            if(res.status == 200)
            {
                this.filter = false;
                this.abstep = 6;
                this.daily_data = res.data.daily_data;
                if ($.fn.DataTable.isDataTable("#daily_table")) {
                    $('#daily_table').DataTable().destroy();
                }
                setTimeout(function () {
                    $("#daily_table").DataTable({
                        "pageLength": -1, // Show all entries
                        "lengthMenu": [[-1], ["All"]], // Remove the default options and add 'All'
                        "paging": false, // Optionally, remove the pagination controls
                        "ordering": false // Disable sorting
                    });
                }, 300);
            }
            this.tableLoading = false;
        },
        printReceiptSlip() {
            setTimeout(() => {
                this.$refs.printReceiptPdf.submit();
            }, 500);
        },
        printAccountLedgerReport() {
            setTimeout(() => {
                this.$refs.printAccountLedgerReportPdf.submit();
            }, 500);
        },
        printLedgerReport() {
            setTimeout(() => {
                this.$refs.printLedgerReportPdf.submit();
            }, 500);
        },
        printJournalReport() {
            setTimeout(() => {
                this.$refs.printJournalReportPdf.submit();
            }, 500);
        },
        printGeneralTrialSheetReport() {
            setTimeout(() => {
                this.$refs.printGeneralTrialReportPdf.submit();
            }, 500);
        },
        printDailyReport() {
            setTimeout(() => {
                this.$refs.printDailyReportPdf.submit();
            }, 500);
        },
        printVoucher(id,type) {
        this.voucher.id = id;
        this.voucher.type = type;
        setTimeout(() => {
            this.$refs.transactionFormPdf.submit();
        }, 500);
    },
    showAlert() {
      alert('In process'); // Custom alert message
    }
    }
};
</script>
