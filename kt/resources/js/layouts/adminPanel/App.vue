<template>

    <div>
        <div class="main-wrapper main-wrapper-1" v-if="$store.state.user">
            <div class="navbar-bg"></div>
            <NavBar  v-if="$store.state.user.is_super_admin" />
            <SideBar v-if="$store.state.user.is_super_admin" />          
            <CompanyNavBar v-if="!$store.state.user.is_super_admin" />
            <CompanySideBar v-if="!$store.state.user.is_super_admin" />
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
import SideBar from "./SideBar.vue";
import SettingSideBar from "./SettingSideBar.vue";
import Login from "../../pages/auth/Login.vue";
import CompanyNavBar from "../company/NavBar.vue";
import CompanySideBar from "../company/SideBar.vue";

export default {
    props:['user','app_url','permissions'],
    name:"App",
    components:{
        NavBar,
        SideBar,
        SettingSideBar,
        Login,
        CompanyNavBar,
        CompanySideBar,
    },
    created(){
        if (this.user) {
            // console.log("User Data",this.user);
            this.$store.commit('updateUser',this.user);            
        }
        this.$store.commit('updateAppUrl',this.app_url);
    }
}
</script>