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
            user:false,
            app_url:false,
            permissions:false,
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
            if (user.is_super_admin != 1 && user.role && user.company) {
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
})
export default store;