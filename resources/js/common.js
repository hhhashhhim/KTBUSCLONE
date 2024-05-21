import axios from "axios";

export default {
    data() {
        return {
            validationErrors: []
        }
    },
    methods: {

        async callApi(method, url, data) {
            
            try {
                return await axios({
                    method: method,
                    url: this.$store.state.api_url + "public/api/web/v1/" + url,
                    data: data,
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.state.token
                    }
                });
            } catch (error) {
                if(error.response.status == 401)
                {
                    localStorage.removeItem("user");
                    localStorage.removeItem("token");
                    window.location.href = this.$store.state.main_url;
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
