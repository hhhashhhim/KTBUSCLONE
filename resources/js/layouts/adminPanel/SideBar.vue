<template>
    <div class="main-sidebar sidebar-style-2">
        <aside id="sidebar-wrapper">
            <div class="sidebar-brand text-center">
                <a href="/">
                    <img :src="$store.state.main_url+'assets/img/kt-logo.png'" style="width:250px !important;" alt="">
                </a>
            </div>
            <SidebarMenuSearch />
            <ul class="sidebar-menu">
                <li class="menu-header">Main</li>
                <li class="dropdown">
                <!-- :class="activeLink=='dashboard'?'active':''" @click.native="activeLink('dashboard')" -->
                    <router-link class="nav-link text-capitalize" :to="{ name:'admin-dashboard' }"
                                 >
                        <i class="fa fa-desktop"></i> Dashboard
                    </router-link>
                </li>
                <li class="dropdown toggled">
                    <router-link class="nav-link text-capitalize" :to="{ name:'company' }"
                                >
                        <i class="fa fa-building"></i> Company
                    </router-link>
                </li>
                <li class="dropdown toggled">
                    <router-link class="nav-link text-capitalize" :to="{ name:'update-password' }"
                                 >
                        <i class="fa fa-building"></i> Update Password
                    </router-link>
                </li>
            </ul>
        </aside>
    </div>
</template>
<script>
import SidebarMenuSearch from "../../components/SidebarMenuSearch.vue";

export default {
    components: { SidebarMenuSearch },
    data() {
        return {
            iconsClass: {
                users: "fa-users",
                profile: "fa-user-circle",
                roles: "fa-map-signs",
                company: "fa-building",
            },
            permissions: [],
        }
    },
    mounted() {
        this.syncActiveMenu();
        this.$el.querySelector('.sidebar-menu')?.addEventListener('click', this.activateClickedMenu);
    },
    beforeUnmount() {
        this.$el.querySelector('.sidebar-menu')?.removeEventListener('click', this.activateClickedMenu);
    },
    watch: {
        '$route.fullPath'() {
            this.syncActiveMenu();
        },
    },
    methods: {
        activateClickedMenu(event) {
            const link = event.target.closest('a.nav-link[href]');
            const menu = this.$el.querySelector('.sidebar-menu');
            if (!link || !menu || !menu.contains(link)) return;

            menu.querySelectorAll('li.active').forEach((item) => item.classList.remove('active'));
            link.closest('li')?.classList.add('active');
        },
        syncActiveMenu() {
            this.$nextTick(() => {
                const menu = this.$el.querySelector('.sidebar-menu');
                if (!menu) return;

                menu.querySelectorAll('li.active').forEach((item) => item.classList.remove('active'));
                menu.querySelector('a.router-link-exact-active')?.closest('li')?.classList.add('active');
            });
        },
    }
}
</script>
