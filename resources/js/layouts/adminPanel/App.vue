<template>
    <div>
        <div class="main-wrapper main-wrapper-1" v-if="$store.state.user">
            <div class="navbar-bg"></div>
            <NavBar  v-if="$store.state.user.is_super_admin == 1" />
            <SideBar v-if="$store.state.user.is_super_admin == 1" />
            <CompanyNavBar v-if="$store.state.user.is_super_admin == 0" />
            <CompanySideBar v-if="$store.state.user.is_super_admin == 0" />
            <!-- Main Content -->
            <div class="main-content">
                <transition mode="out-in" :enter-active-class="isCallCenterRoute ? 'cc-route-enter' : 'loader'" :leave-active-class="isCallCenterRoute ? 'cc-route-leave' : 'loader'">
                    <router-view></router-view>
                </transition>
            </div>
        </div>
        <div v-else>
            <Login />
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
    props:['app_url','permissions'],
    name:"App",
    components:{
        NavBar,
        SideBar,
        SettingSideBar,
        Login,
        CompanyNavBar,
        CompanySideBar,
    },
    computed: {
        // The legacy asset script hides .loader on window load. Never apply that
        // splash-screen class to a call center page arriving through a route.
        isCallCenterRoute() { return String(this.$route.name || '').startsWith('call-center'); },
    },
    created(){
        this.$store.commit('updateAppUrl',this.app_url);
    }
}
</script>
