<template>
    <div class="main-sidebar sidebar-style-2">
        <aside id="sidebar-wrapper">
            <div class="sidebar-brand text-center">
                <a href="">
                    <img :src="$store.state.main_url + 'assets/img/kt-logo.png'" style="width:250px !important;" alt="">
                    <!-- <img :src="$store.state.main_url + 'assets/img/sarlogo.png'" style="width:100px !important;" alt=""> -->
                </a>
            </div>
            <ul class="sidebar-menu">
                <li class="menu-header">Main</li>
                <li class="dropdown active">
                    <a :href="$store.state.main_url + 'admin/dashboard'" class="nav-link">
                        <i class="fas fa-desktop"></i><span>Dashboard</span></a>
                </li>
                <!-- Admin Panel -->
                <li class="dropdown" v-if="checkPermission('fleet-maintenance')">
                    <a href="#" class="menu-toggle nav-link has-dropdown"><i class="fas fa-ticket-alt"></i>
                        <span>
                            Inventory
                        </span>  
                    </a>
                    <ul class="dropdown-menu">
                        <li class="dropdown">
                        <router-link
                            class="nav-link text-capitalize d-flex justify-content-between"
                            :to="{ name: 'MR' }"
                        >
                            <span><i class="far fa-file"></i> MR</span>
                          </router-link>
                        </li>
                        <li class="dropdown">
                            <router-link
                                class="nav-link text-capitalize d-flex justify-content-between"
                                :to="{ name: 'PR' }"
                            >
                                <span><i class="far fa-file"></i> PRN</span>
                                <span class="badge badge-secondary" style="width: 25px; padding: 6px;">{{ mrsQty || 0 }}</span>
                            </router-link>
                            </li>
                            <li class="dropdown">
                            <router-link
                                class="nav-link text-capitalize d-flex justify-content-between"
                                :to="{ name: 'BidSummary' }"
                            >
                                <span><i class="far fa-file"></i>Bid Summary</span>
                                <span class="badge badge-secondary" style="width: 25px; padding: 6px;">{{ prnsQty || 0 }}</span>
                            </router-link>
                            </li>
                            <li class="dropdown">
                            <router-link
                                class="nav-link text-capitalize d-flex justify-content-between"
                                :to="{ name: 'PO' }"
                            >
                                <span><i class="far fa-file"></i> PO</span>
                                <span class="badge badge-secondary" style="width: 25px; padding: 6px;">{{ bidsQty || 0 }}</span>
                            </router-link>
                            </li>
                        <li class="dropdown">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'stockInward' }">
                                <span><i class="fa fa-warehouse"></i>  Stock Inward</span>
                                <span class="badge badge-secondary" style="width: 25px; padding: 6px;">{{ posQty || 0 }}</span>
                            </router-link>
                        </li>
                        <li class="dropdown">
                        <router-link class="nav-link text-capitalize" :to="{ name: 'stockOutward' }"> 
                            <span><i class="fa fa-warehouse"></i>  Stock Outward</span>
                            <span class="badge badge-secondary" style="width: 25px; padding: 6px;">{{ mrsQty || 0 }}</span>
                        </router-link>  
                        </li>
                        <!-- <li class="dropdown" v-if="checkForSubmenu('terminals')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'stock' }">
                                <i class="fa fa-landmark"></i> Stock
                            </router-link>
                        </li> -->
                        <li class="dropdown">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'products' }">
                                <i class="fab fa-product-hunt"></i> Products
                            </router-link>
                        </li>
                        <li class="dropdown">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'suppliers' }">
                                <i class="fas fa-user-tag"></i> Suppliers
                            </router-link>
                        </li>
                      
                        <!-- <li class="dropdown" v-if="checkForSubmenu('cities')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'gpo' }">
                                <i class="fas fa-city"></i> GPO
                            </router-link>
                        </li> -->

                        <li class="dropdown">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'reports' }">
                                <i class="far fa-file"></i> Reports
                            </router-link>
                        </li>
                        
                    </ul>
                </li>
                <li class="dropdown" v-if="checkPermission('admin')">
                    <a href="#" class="menu-toggle nav-link has-dropdown"><i class="fas fa-ticket-alt"></i>
                        <span>
                            Admin
                        </span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="dropdown" v-if="checkForSubmenu('cities')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'cities-page' }">
                                <i class="fas fa-city"></i> Cities
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('terminals')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'terminal' }">
                                <i class="fa fa-landmark"></i> Terminal
                            </router-link>
                        </li>
                        <!-- <li class="dropdown" v-if="checkForSubmenu('difference')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'terminal-difference' }">
                                <i class="fas fa-clock"></i> Terminal Time
                            </router-link>
                        </li> -->
                        <li class="dropdown" v-if="checkForSubmenu('fare-class')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'fare-class' }">
                                <i class="fas fa-table"></i> Fare Class
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('fare-table')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'fare-table' }">
                                <i class="fas fa-table"></i> Fare Table
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('sub-routes')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'sub-routes' }">
                                <i class="fas fa-city"></i> Sub Routes
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('routes')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'routes-page' }">
                                <i class="fas fa-route"></i> Routes
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('discounts')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'discount-page' }">
                                <i class="fas fa-table"></i> Discount
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('surcharge')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'surcharge-page' }">
                                <i class="fas fa-table"></i> Surcharge
                            </router-link>
                        </li>
                    </ul>
                </li>
                <!-- Reporting panel -->
                <li class="dropdown" v-if="checkPermission('reports')">
                    <a href="#" class="menu-toggle nav-link has-dropdown"><i class="fas fa-ticket-alt"></i>
                        <span>
                            Reports
                        </span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="dropdown" v-if="checkForSubmenu('report-header')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'report-header'}">
                                <i class="fas fa-city"></i> Report Headers
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('confirm-cancel')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'confirm-cancel-report' }">
                                <i class="fas fa-city"></i> Confirmed Canceled
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('confirm-cancel')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'over-issue-report' }">
                                <i class="fas fa-city"></i> Over Issue Report
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('confirm-cancel')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'reschedule-report' }">
                                <i class="fas fa-city"></i> Reschedule Report
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('sales')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'advance-sale-report' }">
                                <i class="fa fa-landmark"></i> Advance Sales Report
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('terminal-sale')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'terminal-sale-report' }">
                                <i class="fa fa-landmark"></i> Terminal Sales Report
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('terminal-sale')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'terminal-discount-report' }">
                                <i class="fa fa-landmark"></i> Discount Report
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('commission')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'terminal-commission-report' }">
                                <i class="fa fa-landmark"></i> Terminal Commission
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('schedule-drop')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'schedule-drop-report' }">
                                <i class="fa fa-landmark"></i> Schedule Drop Report
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('close-trip')">
                            <router-link class="nav-link text-capitalize" :to="{ name : 'summery-report'}">
                                <i class="fas fa-clock"></i> Closed Trip Report
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('expenses')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'office-expenses-page' }">
                                <i class="fas fa-bookmark"></i> Office Expenses
                            </router-link>
                        </li>
                    </ul>
                </li>
                <!-- Buses panel -->
                <li class="dropdown" v-if="checkPermission('buses')">
                    <a href="#" class="menu-toggle nav-link has-dropdown"><i class="fas fa-ticket-alt"></i>
                        <span>
                            Buses
                        </span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="dropdown" v-if="checkForSubmenu('bus-class')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'bus-class-page' }">
                                <i class="fa fa-bus"></i> Bus Class
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('buses')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'buses-page' }">
                                <i class="fa fa-bus"></i> Buses
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('schedules')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'schedule-page' }">
                                <i class="fas fa-table"></i> Schedule
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('merges')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'booking-schedule-merges' }">
                                <i class="fas fa-bookmark"></i> Merge Buses
                            </router-link>
                        </li>
                    </ul>
                </li>
                <!-- Users Panel -->
                <li class="dropdown" v-if="checkPermission('users')">
                    <a href="#" class="menu-toggle nav-link has-dropdown"><i class="fa fa-users"></i>
                        <span>
                            Users
                        </span>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <router-link class="nav-link text-capitalize" :to="{ name: 'roles' }"
                                         v-if="checkForSubmenu('roles')">
                                <i class="fas fa-project-diagram"></i> roles
                            </router-link>
                        </li>
                        <li>
                            <router-link class="nav-link text-capitalize" :to="{ name: 'users' }"
                                         v-if="checkForSubmenu('users')">
                                <i class="fa fa-user"></i> Users
                            </router-link>
                        </li>
                    </ul>
                </li>
                <!-- Tickting panel -->
                <li class="dropdown" v-if="checkPermission('ticketing')">
                    <a href="#" class="menu-toggle nav-link has-dropdown"><i class="fas fa-ticket-alt"></i>
                        <span>
                            Ticketing
                        </span>
                    </a>
                    <!--                    Permission just about terminals-->
                    <ul class="dropdown-menu">
                        <li class="dropdown" v-if="checkForSubmenu('bookings')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'booking-page' }">
                                <i class="fas fa-bookmark"></i> Booking
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('closing')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'booking-schedule-closing' }">
                                <i class="fas fa-bookmark"></i> Close Booking
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('closing')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'booking-schedule-unclosing' }">
                                <i class="fas fa-bookmark"></i> Unclose Booking
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('closing')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'booking-commission-closing' }">
                                <i class="fas fa-bookmark"></i> Commission Closing
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('closing')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'booking-schedule-unclosing-spare' }">
                                <i class="fas fa-bookmark"></i> Spare Unclosing
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('all-booking')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'all-booking-page' }">
                                <i class="fas fa-bookmark"></i> All Booking
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('counter-expenses')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'counter-expenses-page' }">
                                <i class="fas fa-bookmark"></i> Counter Expenses
                            </router-link>
                        </li>
                    </ul>
                </li>
                <!-- loyalityCard Panel -->
                <li class="dropdown" v-if="checkPermission('loyaltyCard')">
                    <a href="#" class="menu-toggle nav-link has-dropdown"><i class="fas fa-ticket-alt"></i>
                        <span>
                            Loyalty Card
                        </span>
                    </a>
                    <!--                    Permission just about terminals-->
                    <ul class="dropdown-menu">
                        <li class="dropdown" v-if="checkForSubmenu('loyaltyCardCategory')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'loyalty-card-categories' }">
                                <i class="fas fa-bookmark"></i> Card Category
                            </router-link>
                        </li>
                        <li class="dropdown" v-if="checkForSubmenu('loyaltyCardAssign')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'loyalty-card-assign' }">
                                <i class="fas fa-bookmark"></i> Assign Card
                            </router-link>
                        </li>
                    </ul>
                </li>
                <!-- Expenses panel -->
                <li class="dropdown" v-if="checkPermission('expenses')">
                    <a href="#" class="menu-toggle nav-link has-dropdown"><i class="fas fa-ticket-alt"></i>
                        <span>
                            Expenses
                        </span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="dropdown" v-if="checkForSubmenu('categories')">
                            <router-link class="nav-link text-capitalize" :to="{ name: 'expense-category-page' }">
                                <i class="fas fa-city"></i> Expense Categories
                            </router-link>
                        </li>
                    </ul>
                </li>

                <!-- Hrm -->
                <li class="dropdown" v-if="checkPermission('hrm')">

                    <a href="#" class="menu-toggle nav-link has-dropdown">
                        <i class="fas fa-sitemap"></i>
                        <span>
                            HRM
                        </span>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <router-link class="nav-link text-capitalize" :to="{ name: 'employees' }"
                                         v-if="checkForSubmenu('employees')">
                                <i class="fas fa-users"></i> Employees
                            </router-link>
                        </li>
                        <li>
                            <router-link class="nav-link text-capitalize" :to="{ name: 'leaves' }"
                                         v-if="checkForSubmenu('leaves')">
                                <i class="fas fa-street-view"></i> Leave Management
                            </router-link>
                        </li>
                        <li>
                            <router-link class="nav-link text-capitalize" :to="{ name: 'departments' }"
                                         v-if="checkForSubmenu('departments')">
                                <i class="fas fa-street-view"></i> Departments
                            </router-link>
                        </li>
                        <li>
                            <router-link class="nav-link text-capitalize" :to="{ name: 'designations' }"
                                         v-if="checkForSubmenu('designations')">
                                <i class="fas fa-street-view"></i> Designations
                            </router-link>
                        </li>
                    </ul>
                </li>
                <!-- Fleet Maintenance Panel -->
                <li class="dropdown" v-if="checkPermission('fleet-maintenance')">

                    <a href="#" class="menu-toggle nav-link has-dropdown">
                        <i class="fas fa-sitemap"></i>
                        <span>
                            Fleet Maintenance
                        </span>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <router-link class="nav-link text-capitalize" :to="{ name: 'maintenance-parts' }"
                                         v-if="checkForSubmenu('part')">
                                <i class="fas fa-users"></i> Parts Addition
                            </router-link>
                        </li>
                        <!-- <li>
                            <router-link class="nav-link text-capitalize" :to="{ name: 'maintenance-link' }"
                                         v-if="checkForSubmenu('linking')">
                                <i class="fas fa-users"></i> Maintenance Linking
                            </router-link>
                        </li> -->
                        <li>
                            <router-link class="nav-link text-capitalize" :to="{ name: 'maintenance-due' }"
                                         v-if="checkForSubmenu('dues')">
                                <i class="fas fa-users"></i> Maintenance
                            </router-link>
                        </li>
                        <li>
                            <router-link class="nav-link text-capitalize" :to="{ name: 'fault-claim' }" v-if="checkForSubmenu('fault-claim')">
                                <i class="fas fa-tools"></i> Fault Claim
                            </router-link>
                            <router-link class="nav-link text-capitalize" :to="{ name: 'dock-request' }" v-if="checkForSubmenu('dock-request')">
                                <i class="fas fa-warehouse"></i> Dock Requests
                            </router-link>
                        </li>
                        <!-- <li>
                            <router-link class="nav-link text-capitalize" :to="{ name: 'maintenance-record' }"
                                         v-if="checkForSubmenu('records')">
                                <i class="fas fa-users"></i> Maintenance Record
                            </router-link>
                        </li> -->
                    </ul>
                </li>
                <!-- Rereshment Panel -->
                <li class="dropdown" v-if="checkPermission('refreshment')">
                    <a href="#" class="menu-toggle nav-link has-dropdown">
                        <i class="fas fa-sitemap"></i>
                        <span>
                            Refreshment
                        </span>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <router-link class="nav-link text-capitalize" :to="{ name: 'hotels' }"
                                         v-if="checkForSubmenu('hotels')">
                                <i class="fas fa-users"></i> Hotels
                            </router-link>
                        </li>
                    </ul>
                    <ul class="dropdown-menu">
                        <li>
                            <router-link class="nav-link text-capitalize" :to="{ name: 'foodOrder' }"
                                         v-if="checkForSubmenu('order')">
                                <i class="fas fa-users"></i> Food Order
                            </router-link>
                        </li>
                    </ul>
                </li>
                <!-- Acoounts Panel  -->
                <li class="dropdown" v-if="checkPermission('accounts')">

                    <a href="#" class="menu-toggle nav-link has-dropdown">
                        <i class="fas fa-sitemap"></i>
                        <span>
                            Accounts
                        </span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="dropdown" v-if="checkForSubmenu('chart-of-accounts')">
                            <a href="#" class="has-dropdown">Add Ledger</a>
                            <ul class="dropdown-menu">
                                <li>
                                    <router-link class="nav-link text-capitalize" :to="{ name: 'account-groups' }">
                                        <i class="fas fa-street-view"></i> Tier 3/4
                                    </router-link>
                                    <router-link class="nav-link text-capitalize" :to="{ name: 'account-head' }">
                                        <i class="fas fa-street-view"></i> Ledger
                                    </router-link>
                                    <router-link class="nav-link text-capitalize" :to="{ name: 'account-head-bank' }">
                                        <i class="fas fa-street-view"></i> Bank-Ledger
                                    </router-link>
                                    <router-link class="nav-link text-capitalize" :to="{ name: 'account-head-cash' }">
                                        <i class="fas fa-street-view"></i> Cash-Ledger
                                    </router-link>
                                </li>
                            </ul>
                        </li>
                        <li class="dropdown">
                            <a href="#" class="has-dropdown"><i class="far fa-money-bill-alt"></i><span>Transaction</span></a>
                            <ul class="dropdown-menu">
                                <li>
                                    <router-link :to="{name:'account-transaction-bank-transactions'}" class="nav-link"><i class="far fa-dot-circle"></i><span>Bank Transaction</span></router-link
                                        >
                                </li>
                                <li>
                                    <router-link :to="{name:'account-transaction-cash-transactions'}" class="nav-link"><i class="far fa-dot-circle"></i><span>Cash Transaction</span></router-link
                                        >
                                </li>
                                <li>
                                    <router-link :to="{name:'account-transaction-journal-transactions'}" class="nav-link"><i class="far fa-dot-circle"></i><span>Journal Transaction</span></router-link
                                        >
                                </li>
                            </ul>
                        </li>
                        <li class="dropdown">
                            <a href="#" class="has-dropdown"><i class="far fa-file-alt"></i><span>Reports</span></a>
                            <ul class="dropdown-menu">
                                <li><router-link :to="{name:'account-report-finance'}" class="nav-link"><i class="far fa-dot-circle"></i><span>Finance</span></router-link>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </li>
                <!-- settings panel -->
                <li class="dropdown" v-if="checkPermission('settings')">

                    <a href="#" class="menu-toggle nav-link has-dropdown">
                        <i class="material-icons">settings</i>
                        <span>Setting</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <router-link class="nav-link text-capitalize" :to="{ name: 'ticketSettings' }"
                                         v-if="checkForSubmenu('tickets')">
                                <i class="fas fa-users"></i> Ticket Format
                            </router-link>
                        </li>
                        <li>
                            <router-link class="nav-link text-capitalize" :to="{ name: 'activityLog' }"
                                         v-if="checkForSubmenu('ActivityLog')">
                                <i class="fas fa-users"></i> Activity Log
                            </router-link>
                        </li>
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
        // main menu
        checkPermission(name) {
            let permissions = this.permissions;
            let module = permissions.find(obj => obj.name === name);
            if (module) {
                return module.allow;
            } else {
                return false;
            }

        },
        // Sub menu
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

        },
    }
};
</script>