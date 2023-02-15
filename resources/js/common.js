import axios from "axios";

export default {
    data(){
        return {
            validationErrors:[]
        }
    },
    methods:{

        async callApi( method , url , data ){
            try {
                return await axios({
                    method:method,
                    url: this.$store.state.app_url + url,
                    data:data
                });
            } catch (error) {
                return error.response
            }
        },
        errorsArray(desc,title="Ooops"){
            this.validationErrors.push({
                title,
                desc
            });
        }

    }
}
