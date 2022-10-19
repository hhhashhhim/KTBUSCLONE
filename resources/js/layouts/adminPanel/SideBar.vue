<template>
    <div class="main-sidebar sidebar-style-2">
        <aside id="sidebar-wrapper">
            <div class="sidebar-brand text-center">
                <a href="/">
                    <img :src="$store.state.app_url+'assets/img/kt-logo.png'" style="width:250px !important;" alt="">
                </a>
            </div>
            <ul class="sidebar-menu">
                <li class="menu-header">Main</li>
                <li class="dropdown">
                    <router-link class="nav-link text-capitalize" :to="{ name:'admin-dashboard' }" :class="activeLink=='dashboard'?'active':''"  @click.native="activeLink('dashboard')">
                        <i class="fa fa-desktop"></i> Dashboard
                    </router-link>
<!--                    <a href="/" class="nav-link">-->
<!--                    <i class="fas fa-desktop"></i><span>Dashboard</span></a>-->
                </li>
                <li class="dropdown toggled">
                    <router-link class="nav-link text-capitalize" :to="{ name:'company' }" :class="activeLink=='company'?'active':''"  @click.native="activeLink('company')">
                        <i class="fa fa-building"></i> Company
                    </router-link>
                </li>
<!--                <li class="dropdown">-->

<!--                    <a href="#" class="menu-toggle nav-link has-dropdown"><i class="fa fa-user-shield"></i>-->
<!--                        <span>-->
<!--                            Admin-->
<!--                        </span>-->
<!--                    </a>-->
<!--                    <ul class="dropdown-menu">-->
<!--                        <li>-->

<!--                            <a href="/admin/dashboard" class="nav-link"><i class="fa fa-desktop"></i>-->
<!--                                <span>-->
<!--                                    Dashboard-->
<!--                                </span>-->
<!--                            </a>-->
<!--                            &lt;!&ndash; <router-link class="nav-link text-capitalize" :to="{ name:'admin-dashboard' }">-->
<!--                                <i class="fa fa-desktop"></i> Dashboard-->
<!--                            </router-link> &ndash;&gt;-->
<!--                        </li>-->
<!--                        <li class="dropdown">-->
<!--                            <router-link class="nav-link text-capitalize" :to="{ name:'company' }">-->
<!--                                <i class="fa fa-building"></i> Company-->
<!--                            </router-link>-->
<!--                        </li>-->
<!--                    </ul>-->
<!--                </li>-->
                <!-- <li class="dropdown" v-for="(permission,i) in $store.state.permissions" :key="i">
                    <router-link class="nav-link text-capitalize" :to="{ name:permission.name }" v-if="permission.read==true">
                        <i :class="'fa '+iconsClass[permission.name]"></i>{{ permission.name }}
                    </router-link>
                </li> -->
            </ul>
        </aside>
    </div>
</template>
<script>
export default {
    data(){
        return{
          iconsClass:{
            users:"fa-users",
            profile:"fa-user-circle",
            roles:"fa-map-signs",
            company:"fa-building",
          },
          permissions:[],
            activeLink:"",
        }
    },
    methods:{
        activeLink(link){
            console.log("Reaching");
            this.activeLink = link;
        }
    }
}
</script>
