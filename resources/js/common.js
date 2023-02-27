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
                    url: this.$store.state.app_url + url,
                    data: data
                });
            } catch (error) {
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
                let innerChilds = permissions[i].childs;
                for (let j = 0; j < innerChilds.length; j++) {
                    if (innerChilds[j].buttons) {
                        for (let k = 0; k < innerChilds[j].buttons.length; k++) {
                            let innerButtons = innerChilds[j].buttons;
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
