import axios from "axios";

export default {
    data(){
        return {
            validationErrors:[]
        }
    },
    methods:{

        async callApi( method , url , data ){
            var format =/^\/[a-z]+$/i;
            try {
                return await axios({
                    method:method,
                    // url: format.test(url) ? '/api'+url : '/api/'+url,
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
