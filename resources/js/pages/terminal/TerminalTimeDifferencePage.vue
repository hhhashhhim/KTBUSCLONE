<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Terminal Time Difference</h4>
                            <div class="w-50 d-flex align-items-center">
                                <div class="header-select mx-2">
                                    <label for="fare_class" class="font-weight-bold my-0">Cities</label>
                                    <select v-model="data.city" class="form-control rounded-0 text-capitalize">
                                        <option value="0" selected>Select City</option>
                                        <option v-for="(city,i) in cities" :key="i" :value="city.id"
                                                class="text-capitalize"> {{ city.name }}
                                        </option>
                                    </select>
                                </div>
                                <button class="btn btn-primary mt-4 ml-2" type="button" @click="fetchRecord()"
                                        :disabled="loadingTable">
                                    {{ loadingTable ? 'Loading...' : 'Fetch Record' }}
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <transition name="fade">
                                <div class="alert alert-danger alert-dismissible fade show" role="alert" v-if="error">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"
                                            @click="error=!error">
                                        <span aria-hidden="true">&times;</span>
                                        <span class="sr-only">Close</span>
                                    </button>
                                    Please Enter All Required Fields !!!
                                </div>
                            </transition>
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12 text-center py-5" v-if="loading">
                                    <div class="spinner-grow text-primary" style="width: 6rem; height: 6rem;"
                                         role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </div>
                                <div class="col-12" v-else>
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="table-responsive" v-if="terminals">
                                                <table class="table table-striped table-hover table-bordered">
                                                    <thead>
                                                    <tr v-if="terminals.length == 0">
                                                        <th style="font-size:15px;">No Terminal Data Found.......</th>
                                                    </tr>
                                                    <tr v-else>
                                                        <th></th>
                                                        <th v-for="(terminal,i) in terminals" :key="i"> {{
                                                                terminal.name
                                                            }}
                                                        </th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(single,i) in terminals" :key="i">
                                                        <template
                                                            v-for="(terminal,j) in single.allTerminals"
                                                            :key="j">
                                                            <th v-if="j==0"> {{ terminals[i].name }}</th>
                                                            <td :class="terminal.id==single.id?'bg-danger':'modal-cell'">
                                                                <a
                                                                    href="#" :data-target="'#'+formID"
                                                                    data-toggle="modal"
                                                                    @click="changeInfo(single,terminal, terminal.difference)"
                                                                    v-if="single.id!=terminal.id"
                                                                    class="btn btn-success btn-block modal-btn d-flex flex-column justify-content-between">
                                                                    <span>Time Diff : {{ terminal.difference }}</span>
                                                                </a>
                                                            </td>
                                                        </template>
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
                :heading="from + icon + to"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row">
                    <div class="form-group col-md-12">
                        <label for="time_difference">Time Difference ( e.g HH:MM ) <span
                            class="text-danger ml-1">*</span></label>
                        <vue-mask
                            class="form-control"
                            v-model="data.time_difference"
                            mask="00:00"
                            :raw="false"
                            :options="options">
                        </vue-mask>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="add()" :disabled="loading">
                        {{
                            loading ? 'Loading... ' : (this.data.created ? 'Update Terminals Time' : 'Save Terminal Time')
                        }}
                    </button>
                </template>
            </Add>
        </div>
    </section>
</template>

<script>
import Add from "../../components/Add.vue";
import vueMask from "vue-jquery-mask";
import {mapGetters} from "vuex";

export default {
    name: "TerminalTimeDifference",
    created() {
        $('.modal').remove();
        this.getCities();
    },
    components: {
        Add,
        vueMask,
    },
    data() {
        return {
            loading: false,
            loadingTable: false,
            date: null,
            options: {
                placeholder: 'HH:MM',
            },
            cities: [],
            terminals: [],
            companies: [],
            fetchedData: [],
            validationErrors: [],
            FareClassName: '',
            msg: 1,
            formID: "timeDiff_form",
            data: {
                city: '0',
            },
            from: {},
            to: {},
            success: false,
            error: false,
            icon: '   <i class="fas fa-route mx-1" style="font-size: 20px !important"></i>   ',

        };
    },
    methods: {
        close() {
            $(`#${this.formID}`).click();
        },
        async add() {
            this.validationErrors = [];
            if (this.data.time_difference == '' || typeof this.data.time_difference == 'undefined') {
                return swal({
                    title: "Required!",
                    text: "Time Difference is Required!",
                    icon: "error",
                    timer: 2000
                });
            }
            this.loading = true;
            const resTimeDiff = await this.callApi("post", "terminal_time/store", this.data);
            if (resTimeDiff.status == 200) {
                $(".modal").click();
                this.loading = false;
                this.fetchRecord();
                swal({
                    title: "Success",
                    text: resTimeDiff.data.success[0],
                    icon: "success",
                    timer: 2000
                });
                this.close();
            }
            if (resTimeDiff.status == 422) {
                this.loading = false;
                let errorContent = "";
                let count = 0;
                for (const key in resTimeDiff.data.errors) {
                    resTimeDiff.data.errors[key].forEach((element) => {
                        errorContent += (
                            (++count) + " - " +
                            element +
                            "\n"
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
        },

        async getCities() {
            const resCities = await this.callApi("post", 'terminal_time/cities');
            if (resCities.status == 200) {
                this.cities = resCities.data
            } else {
                console.log(resCities);
            }
        },

        async changeInfo(from, to) {
            this.data.time_difference = '';
            this.from = from.name;
            this.to = to.name;
            this.data.from = from.id
            this.data.to = to.id;
            const resGetTerminal = await this.callApi("post", 'terminal_time/time/check', {
                from: from.id,
                city: this.data.city,
                to: to.id,
            });
            if (resGetTerminal.status == 200) {
                this.data = resGetTerminal.data;
                this.data.city = resGetTerminal.data.city_id;
                this.data.created = 1;
            } else {
                this.data.created = 0;
            }
        },
        async fetchRecord() {
            if (this.data.city == 0) {
                this.terminals.length = 0;
                this.terminals = [];
                return swal({
                    title: "Required",
                    text: "Select Any City",
                    icon: "error",
                    timer: 2000
                });
            }
            this.loadingTable = true;
            const resGetTerminals = await this.callApi("post", "terminal_time/cities/get", {city: this.data.city});
            if (resGetTerminals.status == 200) {
                this.terminals = resGetTerminals.data
                setTimeout(() => {
                    this.loadingTable = false;
                }, 500);
            }
        },

    },
};
</script>
<style scoped>
table, table * {
    font-size: 10px;
}

.modal-cell {
    padding: 0 !important;
    position: relative;
}

.modal-cell .modal-btn {
    height: 100%;
    transition: 0.5s transform;
}

.header-select {
    width: 35%;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 1s;
}

.fade-enter, .fade-leave-to {
    opacity: 0;
}
</style>
