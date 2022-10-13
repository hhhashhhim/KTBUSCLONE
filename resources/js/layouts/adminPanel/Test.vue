<template>
    <div>
        <div class="main-wrapper main-wrapper-1" >
            <div class="navbar-bg"></div>
         
            <!-- Main Content -->
            <div class="main-content">

               
                <router-view>
                    <SideBar />
                </router-view>

            </div>
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
  name :  'TestPage',
  props:['user','app_url'],
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
        this.$store.commit('updateUser',this.user);
    }
    this.$store.commit('updateAppUrl',this.app_url);
    }
}
</script>
