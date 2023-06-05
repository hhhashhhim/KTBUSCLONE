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
                    <router-link class="nav-link text-capitalize" :to="{ name:'admin-dashboard' }"
                                 :class="activeLink=='dashboard'?'active':''" @click.native="activeLink('dashboard')">
                        <i class="fa fa-desktop"></i> Dashboard
                    </router-link>
                </li>
                <li class="dropdown toggled">
                    <router-link class="nav-link text-capitalize" :to="{ name:'company' }"
                                 :class="activeLink=='company'?'active':''" @click.native="activeLink('company')">
                        <i class="fa fa-building"></i> Company
                    </router-link>
                </li>
                <li class="dropdown toggled">
                    <router-link class="nav-link text-capitalize" :to="{ name:'update-password' }"
                                 :class="activeLink=='update-password'?'active':''" @click.native="activeLink('update-password')">
                        <i class="fa fa-building"></i> Update Password
                    </router-link>
                </li>
            </ul>
        </aside>
    </div>
</template>
<script>
export default {
    data() {
        return {
            iconsClass: {
                users: "fa-users",
                profile: "fa-user-circle",
                roles: "fa-map-signs",
                company: "fa-building",
            },
            permissions: [],
            activeLink: "",
        }
    },
    methods: {
        activeLink(link) {
            console.log("Reaching");
            this.activeLink = link;
        }
    }
}
</script>
