import axios from "axios";

const PUBLIC_API_ENDPOINTS = ["login"];

export default {
    data() {
        return {
            validationErrors: []
        }
    },
    methods: {

        isValuePresent(value) {
            return value !== undefined && value !== null && value !== "";
        },
        async ensurePageReady({ requireAuth = true, requiredRouteParams = [] } = {}) {
            if (this.$store?.dispatch) {
                await this.$store.dispatch("initializeAppState");
            }

            if (requireAuth && !this.$store?.state?.token) {
                if (typeof swal === "function") {
                    swal({
                        title: "Session Required",
                        text: "Please sign in again before opening this page.",
                        icon: "error",
                        timer: 2000,
                    });
                }
                return false;
            }

            for (const routeParam of requiredRouteParams) {
                if (!this.isValuePresent(this.$route?.params?.[routeParam])) {
                    if (typeof swal === "function") {
                        swal({
                            title: "Missing Data",
                            text: "This page was opened without the required route data.",
                            icon: "error",
                            timer: 2000,
                        });
                    }
                    return false;
                }
            }

            return true;
        },
        async callApi(method, url, data, config = {}) {
            const isPublicEndpoint = PUBLIC_API_ENDPOINTS.includes(url);

            if (this.$store?.dispatch) {
                await this.$store.dispatch("initializeAppState");
            }

            const token = this.$store?.state?.token;

            if (!isPublicEndpoint && !token) {
                return {
                    status: 401,
                    data: {
                        message: "Authentication token is missing.",
                        errors: {
                            auth: ["Authentication token is missing. Please sign in again."],
                        },
                    },
                };
            }

            try {
                return await axios({
                    ...config,
                    method: method,
                    url: this.$store.state.api_url + "public/api/web/v1/" + url,
                    data: data,
                    headers: {
                        ...(config.headers || {}),
                        ...(token ? { 'Authorization': 'Bearer ' + token } : {}),
                    }
                });
            } catch (error) {
                if(!error.response)
                {
                    return {
                        status: 500,
                        data: {
                            errors: {
                                network: ["Unable to reach the server. Please try again."],
                            },
                        },
                    };
                }
                if(error.response.status == 401)
                {
                    if (!isPublicEndpoint && this.$store?.dispatch) {
                        await this.$store.dispatch("clearAuthState");
                        window.location.href = this.$store.state.main_url;
                    }
                }
                if(error.response.status == 403)
                {
                    setTimeout(() => {
                        window.location.href = this.$store.state.main_url + 'admin/dashboard';
                    }, 500);
                    return swal({
                        title: "OOPS!!!!!",
                        text: "ACCESS DENIED",
                        icon: "error",
                        timer: 2000
                    });
                }
                return error.response
            }
        },
        errorsArray(desc, title = "Ooops") {
            this.validationErrors.push({
                title,
                desc
            });
        },
        checkForSubmenuButtons(ButtonName) {
            let permissions = this.permissions;
            for (let i = 0; i < permissions.length; i++) {
                let innerChildren = permissions[i].childs;
                for (let j = 0; j < innerChildren.length; j++) {
                    if (innerChildren[j].buttons) {
                        for (let k = 0; k < innerChildren[j].buttons.length; k++) {
                            let innerButtons = innerChildren[j].buttons;
                            if (innerButtons[k].name == ButtonName) {
                                return innerButtons[k].allow;
                            }
                        }
                    }
                }
            }
        },
    }
}
