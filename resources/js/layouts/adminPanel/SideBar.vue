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
                <li class="dropdown active">
                    <a href="/" class="nav-link">
                    <i class="fas fa-desktop"></i><span>Dashboard</span></a>
                </li>
                <li class="dropdown">
                    <router-link class="nav-link text-capitalize" :to="{ name:'company' }" v-if="$store.state.user.is_super_admin==1">
                        <i class="fa fa-building"></i> Company
                    </router-link>
                </li>
                <li class="dropdown" v-if="$store.state.user.is_super_admin==1 || checkPermission('users')">
                    
                    <a href="#" class="menu-toggle nav-link has-dropdown"><i class="fa fa-users"></i>
                        <span>
                            Users
                        </span>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <router-link class="nav-link text-capitalize" :to="{ name:'roles' }" v-if="$store.state.user.is_super_admin==1 || checkForSubmenu('roles')">
                                <i class="fas fa-project-diagram"></i> roles
                            </router-link>
                        </li>
                        <li>
                            <router-link class="nav-link text-capitalize" :to="{ name:'users' }" v-if="$store.state.user.is_super_admin==1 || checkForSubmenu('users')">
                                <i class="fa fa-user"></i> Users
                            </router-link>
                        </li>
                    </ul>
                </li>
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
        }
    },
    created(){
        this.permissions = this.$store.state.permissions;
        console.log(this.$store.state.permissions);
    },
    methods:{
        checkPermission(name){
            let permissions =this.$store.state.companyModules;
            for(var i=0; i<permissions.length; i++) {
                if (permissions[i]==name) {
                    return 1;
                }
            }
        },
        checkForSubmenu(moduleName){
            // console.log(moduleName);
            let permissions = this.permissions;
            for(const i in permissions) {
                if (permissions[i].name==moduleName && permissions[i].read==true) {
                    return 1;
                }
            }
        }
    }
}
</script>