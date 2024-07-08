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
                                    <label for="terminalFilter">Sub Account</label>
                                    <select id="terminalFilter" class="form-control" v-model="addData.secondLevel" @change="getThirdLevel()">
                                        <option value="0">Select tier 2</option>
                                        <option v-for="(single, i) in secondLevelData" :key="i" :value="single.id">
                                            {{ single.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="terminalFilter">Parent Group</label>
                                    <select id="terminalFilter" class="form-control" v-model="addData.thirdLevel">
                                        <option value="0">Select tier 3</option>
                                        <option v-for="(single, i) in thirdLevelData" :key="i" :value="single.id">
                                            {{ single.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="terminalFilter">Group Name</label>
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
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(single, i) in fourthLevelData" :key="i">
                                                        <td>{{ i+1 }}</td>
                                                        <td>{{ single.name }}</td>
                                                        <td>{{ single.group.name }}</td>
                                                        <td>{{ single.account.name }}</td>
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
        </div>
    </section>
</template>

<script>
import {mapGetters} from "vuex";
import vueMask from "vue-jquery-mask";

export default {
    name: "AccountGroupPage",
    components: {
        vueMask,
    },
    data() {
        return {
            addData: {
                secondLevel: "0",
                thirdLevel: "0",
                groupName: "",
            },
            fourthLevelData: [],
            thirdLevelData: [],
            secondLevelData: [],
            permissions: [],
            loading: false,
        };
    },
    async created() {
        await this.fetchAccountGroups();
        $('.modal').remove();
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

            const resGroups = await this.callApi("post", 'accounts/coa/groups');
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
        
        async getThirdLevel() {

            const res = await this.callApi("post", 'accounts/coa/second/groups', {id:this.addData.secondLevel});
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
            const resCategory = await this.callApi("post", "accounts/coa/group/store", this.addData);
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
    },
    computed: {
        ...mapGetters(['getDeletingObj'])
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.departmentsDetails.splice(obj.index, 1)
                $("#group_table").DataTable().destroy();
                this.fetchCategories();
            }
        }
    }
};
</script>
<style scoped>
</style>
