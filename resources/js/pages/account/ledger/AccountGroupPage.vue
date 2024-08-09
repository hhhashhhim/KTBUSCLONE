<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Account Tier 3/4</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="terminalFilter">Tier 2</label>
                                    <select id="terminalFilter" class="form-control" v-model="addData.secondLevel" @change="getThirdLevel($event.target.value)">
                                        <option value="0">Select tier 2</option>
                                        <option v-for="(single, i) in secondLevelData" :key="i" :value="single.id">
                                            {{ single.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="terminalFilter">Tier 3</label>
                                    <select id="terminalFilter" class="form-control" v-model="addData.thirdLevel">
                                        <option value="0">Select tier 3</option>
                                        <option v-for="(single, i) in thirdLevelData" :key="i" :value="single.id">
                                            {{ single.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="terminalFilter">{{addData.thirdLevel == '0' ? 'New Tier 3' : 'New Tier 4'}}</label>
                                    <input type="text" class="form-control" v-model="addData.groupName">
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-primary mt-4 btn-block" type="button" @click="addGroup()"
                                            :disabled="loading">
                                        {{ loading ? 'Loading...' : 'Add' }}
                                    </button>
                                </div>
                            </div>
                            <!-- Table -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table dataTables table-striped table-hover"
                                                       id="group_table">
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Tier 4</th>
                                                        <th>Tier 3</th>
                                                        <th>Tier 2</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(single, i) in fourthLevelData" :key="i">
                                                        <td>{{ i+1 }}</td>
                                                        <td>{{ single.name }}</td>
                                                        <td>{{ single.level_three.name }}</td>
                                                        <td>{{ single.level_two.name }}</td>
                                                        <td>
                                                            <button
                                                                    title="Edit Group" :data-target="'#' + editFormID"
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
            <Edit
                heading="Edit Name"
                :errors="this.validationErrors"
                :success="success"
                :editForm="editFormID"
            >
                <div class="row">
                    <div class="col-md-4">
                        <label for="">Tier 2</label>
                        <select disabled id="" class="form-control" v-model="dataEdit.account_id" @change="getThirdLevel($event.target.value)">
                            <option value="0">Select tier 2</option>
                            <option v-for="(single, i) in secondLevelData" :key="i" :value="single.id">
                                {{ single.name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="">Tier 3</label>
                        <select disabled id="" class="form-control" v-model="dataEdit.parent_id">
                            <option value="0">Select tier 3</option>
                            <option v-for="(single, i) in thirdLevelData" :key="i" :value="single.id">
                                {{ single.name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="">Tier 4 name</label>
                        <input type="text" class="form-control" v-model="dataEdit.name">
                    </div>
                </div>

                <template v-slot:button>
                    <button type="button" class="btn btn-primary" :disabled="loading" @click="updateGroup">
                        {{ loading ? 'Loading...' : 'Update' }}
                    </button>
                </template>
            </Edit>
        </div>
    </section>
</template>

<script>
import Edit from '../../../components/Edit.vue';
export default {
    name: "AccountGroupPage",
    components: {
        Edit,
    },
    data() {
        return {
            addData: {
                secondLevel: "0",
                thirdLevel: "0",
                groupName: "",
            },
            dataEdit: {
                level_three: {
                },
            },
            category: "",
            editFormID: 'edit_group_form',
            fourthLevelData: [],
            thirdLevelData: [],
            secondLevelData: [],
            permissions: [],
            loading: false,
        };
    },
    async created() {
        $('.modal').remove();
        await this.fetchAccountGroups();
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
        async fetchAccountGroups() {

            const resGroups = await this.callApi("post", 'accounts/groups');
            if (resGroups.status == 200) {
                this.fourthLevelData = resGroups.data.fourthLevel
                this.secondLevelData = resGroups.data.secondLevel
            } else {
                console.log(resGroups);
            }

            setTimeout(function () {
                $("#group_table").DataTable();
            }, 300);
        },
        
        async getThirdLevel(id) {

            const res = await this.callApi("post", 'accounts/groups/second', {id:id});
            if (res.status == 200) {
                this.thirdLevelData = res.data.thirdLevel
            } else {
                console.log(res);
            }

            setTimeout(function () {
                $("#group_table").DataTable();
            }, 300);
        },

        clearForm: function () {
            
            this.addData.secondLevel = "0",
            this.addData.thirdLevel = "0",
            this.addData.groupName = "",
            this.thirdLevelData = [];
        },
        
        async addGroup() {
            this.validationErrors = [];
            if (this.addData.secondLevel == "0")
                return swal({
                    title: "Required!",
                    text: "Please Select Tier 2",
                    icon: "error",
                    timer: 2000
                });
            if (this.addData.groupName == "")
                return swal({
                    title: "Required!",
                    text: "Group name is required",
                    icon: "error",
                    timer: 2000
                });

            this.loading = true;
            const resCategory = await this.callApi("post", "accounts/groups/store", this.addData);
            if (resCategory.status == 201) {
                this.loading = false;
                swal({
                    title: "Success",
                    text: "Group Added Successfully!",
                    icon: "success",
                    timer: 2000
                });
                this.clearForm();
                $("#group_table").DataTable().destroy();
                await this.fetchAccountGroups();
            } else {
                if (resCategory.status == 422) {
                    this.loading = false;
                    let errorContent = "";
                    let count = 0;
                    for (const key in resCategory.data.errors) {
                        resCategory.data.errors[key].forEach((element) => {
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
        async edit(fourthLevel) {
            this.dataEdit = fourthLevel;
            this.getThirdLevel(fourthLevel.account_id);
        },
        async updateGroup() {
            this.validationErrors = [];
            
            if (this.dataEdit.name == "")
                return swal({
                    title: "Required!",
                    text: "Group name is required",
                    icon: "error",
                    timer: 2000
            });
            
            this.loading = true;
            const resCategory = await this.callApi("post", "accounts/groups/update", this.dataEdit);
            if (resCategory.status == 200) {
                this.loading = false;
                swal({
                    title: "Success",
                    text: "Group Updated Successfully!",
                    icon: "success",
                    timer: 2000
                });
                $("#group_table").DataTable().destroy();
                await this.fetchAccountGroups();
            } else {
                if (resCategory.status == 422) {
                    this.loading = false;
                    let errorContent = "";
                    let count = 0;
                    for (const key in resCategory.data.errors) {
                        resCategory.data.errors[key].forEach((element) => {
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
};
</script>
<style scoped>
</style>
