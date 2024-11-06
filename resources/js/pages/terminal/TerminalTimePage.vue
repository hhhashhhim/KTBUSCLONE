<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header text-capitalize">
                            <h4>{{ terminal.name }} Time</h4>
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
                                                        <th>Route</th>
                                                        <th>Time Difference</th>
                                                        <th>Name</th>
                                                        <th>Show in list</th>
                                                        <th style="width: 200px">Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(i, index) in loop" :key="index">
                                                        <td>
                                                            <!-- {{ items[0] ? items[0].price : '' }} -->
                                                            <select class="form-control rounded-0"
                                                                    @change="saveRow($event, 'first', index)"
                                                                    :value="postData.route[index]" :disabled="editAble">
                                                                <option value="" selected>Select Route</option>
                                                                <option v-for="(route, i) in routes"
                                                                        :value="route.id" :key="i">
                                                                    {{ route.name }}  ({{ route.via??'n/a' }})
                                                                </option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control"
                                                                   @keyup="saveRow($event, 'second', index)"
                                                                   placeholder=""
                                                                   :value="postData.time[index]"
                                                                   :disabled="editAble"/>
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control" :value="postData.name[index]" @change="saveRow($event, 'third', index)" :disabled="editAble">
                                                        </td>
                                                        <td>
                                                            <input type="checkbox" :checked="postData.show[index]" @change="saveRow($event, 'fourth', index)" :disabled="editAble">
                                                        </td>
                                                        <td v-if="!editAble">
                                                            <button class="btn btn-outline-primary mx-2"
                                                                    @click="addRow">Add
                                                            </button>
                                                            <button class="btn btn-outline-danger"
                                                                    @click="removeRow($event, index)"
                                                                    >Remove
                                                            </button>
                                                        </td>
                                                        <td v-else></td>
                                                    </tr>
                                                    </tbody>
                                                </table>
                                                <div class="d-flex justify-content-end">
                                                    <button type="button" class="btn btn-outline-success mr-4"
                                                            @click="add" :disabled="loading" v-if="!editAble">{{
                                                            loading ?
                                                                'Loading...' : 'Save'
                                                        }}
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary mr-4"
                                                            @click="editAble = false" :disabled="loading" v-else>Edit
                                                    </button>
                                                    <button type="button" class="btn btn-outline-primary mr-4"
                                                            @click="editAble=true"
                                                            v-if="!editAble && postData.route.length != 0">Cancel
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
        </div>
    </section>


</template>

<script>
// import Add from '../../components/Add.vue';
// import Edit from '../../components/Edit.vue';
// import Delete from '../../components/Delete.vue';
import {mapGetters} from 'vuex';

export default {
    name: "TerminalTimePage",
    components: {
        // Add,
        // Edit,
        // Delete,
    },
    data() {
        return {
            validationErrors: [],
            editAble: true,
            routes: [],
            terminal: [],
            loading: false,
            formID: 'terminal_time',
            editFormID: 'edit_terminal_time',
            // deleteFormID:'delete_city_form',
            postData: {
                terminal_id: "",
                name: [],
                route: [],
                time: [],
                show: [],
            },
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
        $(".modal").click();
        await this.fetchData();
        await this.existingTimes();
        setTimeout(function () {
            $("#discount_table").DataTable();
        }, 300);
    },

    methods: {
        clearForm: function () {
            this.data = {};
        },
        async fetchData() {
            this.postData.terminal_id = this.$route.params.id;

            const res = await this.callApi("post", 'terminals/routes');
            if (res.status == 200) {
                this.routes = res.data;
            }

        },
        async existingTimes() {
            const res = await this.callApi("post", 'terminals/times', {terminal_id: this.postData.terminal_id});
            if (res.status == 200) {
                const time = res.data.terminalTimes;
                this.terminal = res.data.terminal;
                if (time != "") {
                    this.loop = time.length;
                    for (var i = 0; i < time.length; i++) {
                        this.postData.route.push(time[i].route_id);
                        this.postData.time.push(time[i].time_difference);
                        this.postData.name.push(time[i].display_name);
                        this.postData.show.push(time[i].show == 1 ? true : false);
                    }
                } else {
                    this.loop = 1;
                    this.editAble = false;
                }
            }
        },
        saveRow(event, fieldName, index) {
            // const getRowNumber = event.target.parentElement.parentElement.rowIndex;
            if (fieldName == "first") {
                this.postData.route[index] = event.target.value;
            }
            if (fieldName == "second") {
                this.postData.time[index] = event.target.value;
            }
            if (fieldName == "third") {
                this.postData.name[index] = event.target.value;
            }
            if (fieldName == "fourth") {
                this.postData.show[index] = event.target.checked;
            }
        },
        addRow() {
            this.loop++;
        },
        removeRow(event, index) {
            this.postData.route.splice(index, 1);
            this.postData.time.splice(index, 1);
            this.postData.name.splice(index, 1);
            this.postData.show.splice(index, 1);
            this.loop--;
        },
        closeTab() {
            window.close();
        },
        async add() {

            // validation for empty data
            // if (!this.postData.terminal_id || this.postData.route.length == 0 || this.postData.time.length == 0) {
            //     return swal({
            //         title: "Error",
            //         text: "Please Fill All Field",
            //         icon: "error",
            //         timer: 2000
            //     });
            // }

            // check if any index is empty or null in object
            for (var i = 0; i < this.postData.route.length; i++) {
                if (!this.postData.route[i] || !this.postData.time[i] || !this.postData.name[i]) {
                    return swal({
                        title: "Error",
                        text: "Please Fill All Field Or Remove Extra",
                        icon: "error",
                        timer: 2000
                    });
                }
            }

            this.loading = true;
            const res = await this.callApi("post", "terminals/times/store", this.postData);
            if (res.status === 200) {
                this.loading = false;
                // $('#expense').DataTable().destroy();
                this.postData.route = [];
                this.postData.time = [];
                this.postData.name = [];
                this.postData.show = [];

                this.loop = 0;
                this.editAble = true;
                swal({
                    title: "Success",
                    text: "Time Updated",
                    icon: "success",
                    timer: 2000
                });
                this.fetchData();
                this.existingTimes();
                this.loading = false;
                // setTimeout(() => this.closeTab(), 1000);
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
                $("#discount_table").DataTable().destroy();
                this.fetchData();
                this.existingTimes();
            }
        }
    }
}
</script>
