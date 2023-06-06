<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header">
                            <h4>Update Password</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="name">Current Password <span class="text-danger ml-1">*</span></label>
                                    <input type="password" class="form-control" placeholder="" v-model="data.currentPassword">
                                </div>
                                <div class="form-group col-md-6">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="name">New Password <span class="text-danger ml-1">*</span></label>
                                    <input type="password" class="form-control" placeholder="" v-model="data.newPassword">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="name">Confirm Password <span class="text-danger ml-1">*</span></label>
                                    <input type="password" class="form-control" placeholder="" v-model="data.confirmPassword">
                                </div>
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-primary" :disabled="loading" @click="add">
                                        {{ loading ? 'Loading...' : 'Update' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
import {mapGetters} from 'vuex';

export default {
    name: "UpdatePassword",
    data() {
        return {
            validationErrors: [],
            permissions: [],
            data: {
                currentPassword: "",
                newPassword: "",
                confirmPassword: "",
            },
            success: false,
            errors: false,
        }
    },

    methods: {
        clearForm: function () {
            this.data = {};
        },
        async add() {
            this.validationErrors = []
            if (!this.data.currentPassword)
                return swal({
                    title: "Required",
                    text: "Current Password is required",
                    icon: "error",
                    timer: 2000
                });
            if (!this.data.newPassword)
                return swal({
                    title: "Required",
                    text: "New Password is required",
                    icon: "error",
                    timer: 2000
                });
            if (!this.data.confirmPassword)
                return swal({
                    title: "Required",
                    text: "Confirm Password is required",
                    icon: "error",
                    timer: 2000
                });
            this.loading = true
            const res = await this.callApi("post", 'password/update', this.data);
            if (res.status == 200) {
                swal({
                    title: "Success",
                    text: "Updated Succesfuly",
                    icon: "success",
                    timer: 2000
                });
                this.loading = false;
                this.data.currentPassword = "";
                this.data.newPassword = "";
                this.data.confirmPassword = "";
                setTimeout(function () {
                    this.success = "";
                    this.data = "";
                }, 300)
            } else {
                if (res.status == 422) {
                    this.loading = false;
                    let errorContent = "";
                    let count = 0;
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
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
            }
        }
    },
    computed: {
        ...mapGetters(['getDeletingObj'])
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.cities.splice(obj.index, 1)
                $("#city_table").DataTable().destroy();
                this.fetchCities();
            }
        }
    }
}
</script>
