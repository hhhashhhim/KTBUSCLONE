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
                    url: this.$store.state.app_url + "api/v1/" + url,
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
