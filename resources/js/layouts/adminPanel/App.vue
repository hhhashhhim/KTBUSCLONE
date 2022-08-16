<template>

    <div>
        <div class="main-wrapper main-wrapper-1" v-if="$store.state.user">
            <div class="navbar-bg"></div>
            <NavBar />            
            <SideBar />            
            
            <!-- Main Content -->
            <div class="main-content">
                
                <router-view></router-view>
                <SettingSideBar />            
                
            </div>
        </div>
        <div v-else>
            <router-view></router-view>
        </div>
    </div>
</template>
<script>
import NavBar from "./NavBar.vue";
import Login from "../../pages/auth/Login.vue";
import SideBar from "./SideBar.vue";
import SettingSideBar from "./SettingSideBar.vue";
export default {
    props:['user','app_url','permissions'],
    name:"App",
    components:{
        NavBar,
        SideBar,
        SettingSideBar,
        Login,
    },
    created(){
        if (this.user) {
            this.$store.commit('updateUser',this.user);            
        }
        this.$store.commit('updateAppUrl',this.app_url);
        

        // .find("users")  

    }
}
</script>