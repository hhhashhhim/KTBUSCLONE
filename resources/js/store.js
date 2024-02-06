import { createStore } from 'vuex';

const store = createStore({
    state(){
        return{
            deletingObj:{
                url:"",
                data:"",
                index:-1,
                isDeleted:false,
            },
            user:JSON.parse(localStorage.getItem("user")),
            token:localStorage.getItem("token"),
            main_url:process.env.MIX_MAIN_URL,
            api_url:process.env.MIX_API_URL,
            permissions: JSON.parse(localStorage.getItem("user")) && JSON.parse(localStorage.getItem("user")).role ? JSON.parse(localStorage.getItem("user")).role.permissions : [],
            companyModules:false,
        }
    },

    
    getters:{
        getDeletingObj(state){
            return state.deletingObj;
        },
        user(state){
            return state.user;
        }
    },
    mutations:{
        setDeleteObj(state,obj){
            state.deletingObj=obj;
        },
        async updateUser(state,user){
            state.user=user;
            if (user.is_super_admin != 1 && user.role) {
                // let companyPermissions = user.company.modules;
                // let modules = [];
                // companyPermissions.forEach(permission => {
                //     for(const test in permission){
                //         modules.push(test);
                //     }
                // });
                // state.companyModules = modules;
                state.permissions = user.role.permissions;            
            }else{
                state.permissions = state.companyModules = []
            }
        },
        updateAppUrl(state,obj){
            state.app_url=obj;
        },
        // updatePermissions(state,obj){
        //     state.permissions=obj.permissions;
        // }
    }
});
export default store;