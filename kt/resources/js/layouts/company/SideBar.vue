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
                <li class="dropdown" v-if="checkPermission('users')">
                    
                    <a href="#" class="menu-toggle nav-link has-dropdown"><i class="fa fa-user-shield"></i>
                        <span>
                            Admin
                        </span>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            
                            <a href="/admin/dashboard" class="nav-link"><i class="fa fa-desktop"></i>
                                <span>
                                    Dashboard
                                </span>
                            </a>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('terminal')">
                            <router-link class="nav-link text-capitalize" :to="{ name:'terminal' }">
                                <i class="fa fa-landmark"></i> terminal
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('fare-table')">
                            <router-link class="nav-link text-capitalize" :to="{ name:'fare-table' }">
                                <i class="fas fa-table"></i> Fare Table
                            </router-link>
                        </li>
                           <li class="dropdown">
                            <router-link class="nav-link text-capitalize" :to="{ name:'routes-page' }">
                                <i class="fas fa-table"></i> Routes
                            </router-link>
                        </li>
                    </ul>
                </li>
                <li class="dropdown" v-if="checkPermission('users')">
                    
                    <a href="#" class="menu-toggle nav-link has-dropdown"><i class="fa fa-users"></i>
                        <span>
                            Users
                        </span>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <router-link class="nav-link text-capitalize" :to="{ name:'roles' }" v-if="checkForSubmenu('roles')">
                                <i class="fas fa-project-diagram"></i> roles
                            </router-link>
                        </li>
                        <li>
                            <router-link class="nav-link text-capitalize" :to="{ name:'users' }" v-if="checkForSubmenu('user')">
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
        this.permissions = this.$store.state.permissions
    },
    methods:{
        checkPermission(name){

            let permissions = this.permissions;
            let module = permissions.find(obj => obj.name === name);
            if (module) {
                return module.allow;
            }
            else{
                return false;
            }

        },
        checkForSubmenu(moduleName){

            let permissions = this.permissions;
            let valid = false;
            for(var i=0; i<permissions.length; i++) {

                permissions[i].childs.forEach(subMenuItem => {
                    if (subMenuItem.name==moduleName) {
                        valid = subMenuItem.allow; 
                        return;
                    }
                });
                
            }
            return valid;

        }
    }
}
</script>