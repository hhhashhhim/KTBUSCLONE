<template>
    <div>
        <section class="section">
            <div class="container mt-5">
                <div class="row">
                    <div class="col-md-4 mx-auto">
                        <div class="card card-success">
                            <div class="card-header text-center">
                                <img :src="$store.state.main_url + 'assets/img/kt-logo.png'"
                                     style="width:350px !important;" alt="">
                                     <!-- <img :src="$store.state.main_url + 'assets/img/sarlogo.png'"
                                     style="width:130px !important; margin: auto;" alt=""> -->
                            </div>
                            <div class="card-body">
                                <div
                                    class="alert alert-danger alert-dismissible fade show"
                                    role="alert"
                                    v-if="validationErrors.length"
                                >
                                    <button
                                        type="button"
                                        class="close"
                                        data-dismiss="alert"
                                        aria-label="Close"
                                    >
                                        <span aria-hidden="true">&times;</span>
                                        <span class="sr-only">Close</span>
                                    </button>
                                    <!-- {{ errors.length }} -->
                                    <ul style="padding-left:0px;">
                                        <li v-for="(error, i) in validationErrors" :key="i">
                                            {{ error.desc }}
                                        </li>
                                    </ul>
                                </div>
                                <div
                                    class="alert alert-danger alert-dismissible fade show"
                                    role="alert"
                                    v-if="error"
                                >
                                    <button
                                        type="button"
                                        class="close"
                                        data-dismiss="alert"
                                        aria-label="Close"
                                    >
                                        <span aria-hidden="true">&times;</span>
                                        <span class="sr-only">Close</span>
                                    </button>
                                    {{error}}
                                </div>
                                <div
                                    class="alert alert-success alert-dismissible fade show"
                                    role="alert"
                                    v-if="success"
                                >
                                    <button
                                        type="button"
                                        class="close"
                                        data-dismiss="alert"
                                        aria-label="Close"
                                    >
                                        <span aria-hidden="true">&times;</span>
                                        <span class="sr-only">Close</span>
                                    </button>
                                    {{ success }}
                                </div>

                                <form @submit="login">

                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input
                                            type="email"
                                            class="form-control"
                                            tabindex="1"
                                            required
                                            autofocus
                                            v-model="data.email"
                                        />
                                        <div class="invalid-feedback">Please fill in your email</div>
                                    </div>
                                    <div class="form-group">
                                        <div class="d-block">
                                            <label for="password" class="control-label">Password</label>
                                            <!-- <div class="float-right">
                                                <a href="auth-forgot-password.html" class="text-small">
                                                  Forgot Password?
                                                </a>
                                              </div> -->
                                        </div>
                                        <input
                                            type="password"
                                            class="form-control"
                                            tabindex="2"

                                            v-model="data.password"
                                        />
                                        <div class="invalid-feedback">
                                            please fill in your password
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <button
                                            type="submit"
                                            class="btn btn-success btn-lg btn-block"
                                            tabindex="4"
                                        >
                                            Login
                                        </button>
                                    </div>

                                </form>

                            </div>
                        </div>
                        <div class="mt-5 text-muted text-center">
                            Don't have an account?
                            <a href="auth-register.html">Contact Adminstrative</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
<script>
export default {
    name: 'Dashboard',
    data() {
        return {
            data: {
                email: "",
                password: "",
            },
            success: false,
            errors: [],
            error: "",
        };
    },

    methods: {
        async login(e) {
            e.preventDefault();
            this.validationErrors = [];
            if (this.data.email == "")
                return this.errorsArray("Email is Required", "Email");
            if (this.data.password == "")
                return this.errorsArray("Password is Required", "Password");
            
            const res = await this.callApi("post", "login", this.data);
          
            if (res.status == 201) {
                
                if (res.data.user) {
                    localStorage.setItem("user",JSON.stringify(res.data.user));
                    localStorage.setItem("token",res.data.token);
                    this.data.email = this.data.password = "";
                    window.location.href = this.$store.state.main_url + "admin/dashboard";
                }
               
            } else if(res.status == 404){
                this.error = "These credentials do not match our records.";
            } else {
                if (res.status == 422) {
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
                if (res.status == 401) {
                    this.errorsArray(res.data.message);
                }
            }
        },
    }
};
</script>
