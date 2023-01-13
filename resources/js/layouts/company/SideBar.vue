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
                    <a :href="$store.state.app_url + 'admin/dashboard'" class="nav-link">
                        <i class="fas fa-desktop"></i><span>Dashboard</span></a>
                </li>
                <li class="dropdown" v-if="checkPermission('admin')">
                    <a href="#" class="menu-toggle nav-link has-dropdown"><i class="fas fa-ticket-alt"></i>
                        <span>
                            Ticketing
                        </span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="dropdown">
                            <router-link class="nav-link text-capitalize" :to="{ name:'cities-page' }">
                                <i class="fas fa-city"></i> Cities
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('terminal')">
                            <router-link class="nav-link text-capitalize" :to="{ name:'terminal' }">
                                <i class="fa fa-landmark"></i> Terminal
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('fare-table')">
                            <router-link class="nav-link text-capitalize" :to="{ name:'fare-class' }">
                                <i class="fas fa-table"></i> Fare Class
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('fare-table')">
                            <router-link class="nav-link text-capitalize" :to="{ name:'fare-table' }">
                                <i class="fas fa-table"></i> Fare Table
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('terminal')">
                            <router-link class="nav-link text-capitalize" :to="{ name:'bus-class-page' }">
                                <i class="fa fa-bus"></i> Bus Class
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('terminal')">
                            <router-link class="nav-link text-capitalize" :to="{ name:'buses-page' }">
                                <i class="fa fa-bus"></i> Buses
                            </router-link>
                        </li>

                        <li class="dropdown" v-if="checkForSubmenu('route')">
                            <router-link class="nav-link text-capitalize" :to="{ name:'routes-page' }">
                                <i class="fas fa-route"></i> Routes
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('route')">
                            <router-link class="nav-link text-capitalize" :to="{ name:'discount-page' }">
                                <i class="fas fa-table"></i> Discount
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('route')">
                            <router-link class="nav-link text-capitalize" :to="{ name:'surcharge-page' }">
                                <i class="fas fa-table"></i> Surcharge
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('route')">
                            <router-link class="nav-link text-capitalize" :to="{ name:'schedule-page' }">
                                <i class="fas fa-table"></i> Schedule
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('route')">
                            <router-link class="nav-link text-capitalize" :to="{ name:'booking-page' }">
                                <i class="fas fa-bookmark"></i> Booking
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('route')">
                            <router-link class="nav-link text-capitalize" :to="{ name:'booking-schedule-closing' }">
                                <i class="fas fa-bookmark"></i> Close Booking
                            </router-link>
                        </li>

<!--                        <li class="dropdown" v-if="checkForSubmenu('route')">-->
<!--                            <router-link class="nav-link text-capitalize" :to="{ name:'all-booking-page' }">-->
<!--                                <i class="fas fa-bookmark"></i> All Booking-->
<!--                            </router-link>-->
<!--                        </li>-->
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
                            <router-link class="nav-link text-capitalize" :to="{ name:'roles' }"
                                         v-if="checkForSubmenu('roles')">
                                <i class="fas fa-project-diagram"></i> roles
                            </router-link>
                        </li>
                        <li>
                            <router-link class="nav-link text-capitalize" :to="{ name:'users' }"
                                         v-if="checkForSubmenu('user')">
                                <i class="fa fa-user"></i> Users
                            </router-link>
                        </li>
                    </ul>
                </li>
                <li class="dropdown" v-if="checkPermission('hrm')">

                    <a href="#" class="menu-toggle nav-link has-dropdown">
                        <i class="fas fa-sitemap"></i>
                        <span>
                            HRM
                        </span>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <router-link class="nav-link text-capitalize" :to="{ name:'employees' }"
                                         v-if="checkForSubmenu('employee')">
                                <i class="fas fa-users"></i> Employees
                            </router-link>
                        </li>
                        <li>
                            <router-link class="nav-link text-capitalize" :to="{ name:'leaves' }"
                                         v-if="checkForSubmenu('employee')">
                                <i class="fas fa-street-view"></i> Leave Management
                            </router-link>
                        </li>
                        <li>
                            <router-link class="nav-link text-capitalize" :to="{ name:'departments' }"
                                         v-if="checkForSubmenu('employee')">
                                <i class="fas fa-street-view"></i> Departments
                            </router-link>
                        </li>
                        <li>
                            <router-link class="nav-link text-capitalize" :to="{ name:'designations' }"
                                         v-if="checkForSubmenu('employee')">
                                <i class="fas fa-street-view"></i> Designations
                            </router-link>
                        </li>
                    </ul>
                </li>
                <li class="dropdown" v-if="checkPermission('hrm')">

                    <a href="#" class="menu-toggle nav-link has-dropdown">
                        <i class="fas fa-sitemap"></i>
                        <span>
                            Fleet Maintenance
                        </span>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <router-link class="nav-link text-capitalize" :to="{ name:'parts' }"
                                         v-if="checkForSubmenu('employee')">
                                <i class="fas fa-users"></i> Maintenance Parts
                            </router-link>
                        </li>
                        <li>
                            <router-link class="nav-link text-capitalize" :to="{ name:'maintenance-link' }"
                                         v-if="checkForSubmenu('employee')">
                                <i class="fas fa-users"></i> Maintenance Linking
                            </router-link>
                        </li>
                        <li>
                            <router-link class="nav-link text-capitalize" :to="{ name:'maintenance-due' }"
                                         v-if="checkForSubmenu('employee')">
                                <i class="fas fa-users"></i> Maintenance Due
                            </router-link>
                        </li>
                        <li>
                            <router-link class="nav-link text-capitalize" :to="{ name:'maintenance-record' }"
                                         v-if="checkForSubmenu('employee')">
                                <i class="fas fa-users"></i> Maintenance Record
                            </router-link>
                        </li>
                    </ul>
                </li>
                <li class="dropdown" v-if="checkPermission('hrm')">

                    <a href="#" class="menu-toggle nav-link has-dropdown">
                        <i class="fas fa-sitemap"></i>
                        <span>
                            Refreshment
                        </span>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <router-link class="nav-link text-capitalize" :to="{ name:'hotels' }"
                                         v-if="checkForSubmenu('employee')">
                                <i class="fas fa-users"></i> Hotels
                            </router-link>
                        </li>
                    </ul>
                    <ul class="dropdown-menu">
                        <li>
                            <router-link class="nav-link text-capitalize" :to="{ name:'foodOrder' }"
                                         v-if="checkForSubmenu('employee')">
                                <i class="fas fa-users"></i> Food Order
                            </router-link>
                        </li>
                    </ul>
                </li>
                <li class="dropdown" v-if="checkPermission('hrm')">

                    <a href="#" class="menu-toggle nav-link has-dropdown">
                        <i class="material-icons">settings</i>
                        <span>Setting</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <router-link class="nav-link text-capitalize" :to="{ name:'ticketSettings' }"
                                         v-if="checkForSubmenu('employee')">
                                <i class="fas fa-users"></i> Ticket Format
                            </router-link>
                        </li>
<!--                        <li>-->
<!--                            <router-link class="nav-link text-capitalize" :to="{ name:'profileSettings' }"-->
<!--                                         v-if="checkForSubmenu('employee')">-->
<!--                                <i class="fas fa-users"></i>Profile-->
<!--                            </router-link>-->
<!--                        </li>-->
                    </ul>
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
        }
    },
    created() {
        this.permissions = this.$store.state.permissions
    },
    methods: {
        checkPermission(name) {
            let permissions = this.permissions;
            let module = permissions.find(obj => obj.name === name);
            if (module) {
                return module.allow;
            } else {
                return false;
            }

        },
        checkForSubmenu(moduleName) {

            let permissions = this.permissions;
            let valid = false;
            for (var i = 0; i < permissions.length; i++) {

                permissions[i].childs.forEach(subMenuItem => {
                    if (subMenuItem.name == moduleName) {
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
