<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header">
                            <h4>Close Trip Summery Report</h4>
                            <div class="card-header-action">
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="name">Name <span class="text-danger ml-1">*</span></label>
                                                        <input type="text" class="form-control" placeholder="Enter Header Name" v-model="data.name">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="name">Name <span class="text-danger ml-1">*</span></label>
                                                        <input type="text" class="form-control" placeholder="Enter Header Name" v-model="data.name">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <button class="btn btn-primary">Report In English</button>
                                                    <button class="btn btn-secondary">Report In Urdu</button>
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
import Add from '../../components/Add.vue';
import Edit from '../../components/Edit.vue';
// import Delete from '../../components/Delete.vue';
import {mapGetters} from 'vuex';

export default {
    name: "category",
    components: {
        Add,
        Edit,
        // Delete,
    },
    data() {
        return {
            validationErrors: [],
            headers: [],
            loading: false,
            formID: 'reports_header',
            editFormID: 'edit_reports_header',
            data: {
                name: "",
            },
            dataEdit: {
                id: "",
                name: "",
            },
            success: false,
            errors: false,
        }
    },
    async created() {
        this.fetchHeadersData();
    },
    methods: {
        clearForm: function () {
            this.data = {};
        },
        async fetchHeadersData() {
            const resHeaders = await this.callApi("post", 'reportsHeader');
            if (resHeaders.status == 200) {
                this.headers = resHeaders.data;
            }
            setTimeout(function () {
                $("#report_header_table").DataTable();
            }, 300);
        },
        async add() {
            this.validationErrors = []
            if (!this.data.name)
                return swal({
                    title: "Required",
                    text: "Header Name is required",
                    icon: "error",
                    timer: 2000
                });
            this.loading = true
            const res = await this.callApi("post", 'reportsHeader/store', this.data);
            if (res.status == 201) {
                swal({
                    title: "Success",
                    text: "Header Created Successfully Named as  " + res.data.name,
                    icon: "success",
                    timer: 2000
                });
                $("#report_header_table").DataTable().destroy();
                this.loading = false;
                this.fetchHeadersData();
                this.data.name = "";
                setTimeout(function () {
                    this.success = "";
                    this.data = "";
                }, 300)
            } else {
                if (res.status == 422) {
                    this.loading = false;
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
            }
        },
        edit(category) {
            this.dataEdit = category;
        },
        async update() {
            this.validationErrors = []
            if (this.dataEdit.name == "")
                return swal({
                    title: "Required",
                    text: "Header Name is required ",
                    icon: "error",
                    timer: 2000
                });
            this.loading = true;
            const resEdit = await this.callApi("post", 'reportsHeader/update', this.dataEdit);
            if (resEdit.status == 200) {
                swal({
                    title: "Success",
                    text: "Header Name updated Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.loading = false;
                $("#report_header_table").DataTable().destroy();
                this.fetchHeadersData();
                setTimeout(() => {
                    this.success = ""
                    $('#edit-modal').modal('hide')
                }, 3000);
            } else {
                if (resEdit.status == 422) {
                    this.loading = false;
                    for (const key in resEdit.data.errors) {
                        resEdit.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
                setTimeout(() => {
                    this.loading = false
                }, 3000);
            }
        },
        // async deleteModal( city,i ){
        //     const deletingObj = {
        //         url:"cities/delete",
        //         data:city,
        //         index:i,
        //     }
        //     this.$store.commit("setDeleteObj",deletingObj);
        // },
    },
    computed: {
        ...mapGetters(['getDeletingObj'])
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.cities.splice(obj.index, 1)
                $("#report_header_table").DataTable().destroy();
                this.fetchHeadersData();
            }
        }
    }
}
</script>
