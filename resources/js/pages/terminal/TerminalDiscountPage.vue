<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header text-capitalize">
                            <h4>{{ terminal.name }} Terminal Discount</h4>
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
                                                        <th style="width:200px">Route</th>
                                                        <th>Discount</th>
                                                        <th>Apply From</th>
                                                        <th>Apply End</th>
                                                        <th style="width:200px">Action</th>
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
                                                            <input type="text" class="form-control"
                                                                   @keypress="numberValidate($event,{dot:true})"
                                                                   @keyup="saveRow($event, 'second', index)"
                                                                   placeholder="%"
                                                                   :value="postData.discount[index]"
                                                                   :disabled="editAble"/>
                                                        </td>
                                                        <td>
                                                            <input type="date" class="form-control"
                                                                   @change="saveRow($event, 'third', index)"
                                                                   placeholder=""
                                                                   :value="postData.startDate[index]"
                                                                   :disabled="editAble"/>
                                                        </td>
                                                        <td>
                                                            <input type="date" min="0" class="form-control"
                                                                   @change="saveRow($event, 'fourth', index)"
                                                                   placeholder="%"
                                                                   :value="postData.endDate[index]"
                                                                   :disabled="editAble"/>
                                                        </td>
                                                        <td v-if="!editAble">
                                                            <button class="btn btn-outline-primary mx-2"
                                                                    @click="addRow">Add
                                                            </button>
                                                            <button class="btn btn-outline-danger"
                                                                    @click="removeRow($event, index)"
                                                                    v-if="loop != 1">Remove
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
    name: "TerminalDiscountPage",
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
            formID: 'terminal_commission',
            editFormID: 'edit_terminal_commission',
            // deleteFormID:'delete_city_form',
            postData: {
                terminal_id: "",
                route: [],
                discount: [],
                startDate: [],
                endDate: [],
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
        await this.existingDiscounts();
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
        async existingDiscounts() {
            const res = await this.callApi("post", 'terminals/discounts', {terminal_id: this.postData.terminal_id});
            if (res.status == 200) {
                const discounts = res.data.terminalDiscount;
                this.terminal = res.data.terminal;
                if (discounts != "") {
                    this.loop = discounts.length;
                    for (var i = 0; i < discounts.length; i++) {
                        this.postData.route.push(discounts[i].route_id);
                        this.postData.discount.push(discounts[i].discount);
                        this.postData.startDate.push(discounts[i].start_date);
                        this.postData.endDate.push(discounts[i].end_date);
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
                this.postData.discount[index] = event.target.value;
            }
            if (fieldName == "third") {
                this.postData.startDate[index] = event.target.value;
            }
            if (fieldName == "fourth") {
                this.postData.endDate[index] = event.target.value;
            }

            console.log(this.postData);
        },
        addRow() {
            this.loop++;
        },
        removeRow(event, index) {
            this.postData.route.splice(index, 1);
            this.postData.discount.splice(index, 1);
            this.postData.startDate.splice(index, 1);
            this.postData.endDate.splice(index, 1);
            this.loop--;


            console.log(this.postData);
        },
        closeTab() {
            window.close();
        },
        async add() {

            // validation for empty data
            if (!this.postData.terminal_id || this.postData.route.length == 0 || this.postData.discount.length == 0 ||
                this.postData.startDate.length == 0 || this.postData.endDate.length == 0) {
                return swal({
                    title: "Error",
                    text: "Please Fill All Field",
                    icon: "error",
                    timer: 2000
                });
            }

            // check if any index is empty or null in object
            for (var i = 0; i < this.postData.route.length; i++) {
                if (!this.postData.route[i] || !this.postData.discount[i] || !this.postData.startDate[i] ||
                    !this.postData.endDate[i]) {
                    return swal({
                        title: "Error",
                        text: "Please Fill All Field Or Remove Extra",
                        icon: "error",
                        timer: 2000
                    });
                }
            }

            this.loading = true;
            const res = await this.callApi("post", "terminals/discounts/store", this.postData);
            if (res.status === 200) {
                this.loading = false;
                // $('#expense').DataTable().destroy();
                this.postData.route = [];
                this.postData.discount = [];
                this.postData.startDate = [];
                this.postData.endDate = [];

                this.loop = 0;
                this.editAble = true;
                swal({
                    title: "Success",
                    text: "Discount Updated",
                    icon: "success",
                    timer: 2000
                });
                this.fetchData();
                this.existingDiscounts();
                this.loading = false;
                setTimeout(() => this.closeTab(), 1000);
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
        numberValidate(event, { dot = false, maxLen = null, negative = false, comma = false } = {}) {
        
            const charCode = event.charCode;
            const value = event.target.value.toString().replace(/,/g, '');
            
            // Allow numbers (48-57), dot (46), and control keys (0)
            if ((charCode >= 48 && charCode <= 57) || charCode === 0) {
        
                // Check the length if it's not null
                if (maxLen !== null && value.length >= maxLen) {
                    event.preventDefault();
                    return false;
                }

                return true;
            }
            // Accept dot
            if (dot && charCode === 46) {
                // Allow only one dot
                if (value.includes('.')) {
                    event.preventDefault();
                    return false;
                }
        
                // Check the length if it's not null
                if (maxLen !== null && value.length >= maxLen) {
                    event.preventDefault();
                    return false;
                }
                return true;
            }
            // Accept negative value
            if (negative && charCode === 45) {
                if (value.includes('-') || value.length !== 0) {
                    event.preventDefault();
                    return false;
                }
                return true;
            }
        
            event.preventDefault();
            return false;
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
                this.existingDiscounts();
            }
        }
    }
}
</script>
