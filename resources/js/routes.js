import Vue from "vue";
import Profile from "./components/Profile.vue";
import { createWebHistory,createRouter } from "vue-router";

import Users from "./pages/users/Users.vue";
import Roles from "./pages/roles/Roles.vue";
import Company from "./pages/company/Company.vue";
import Permissions from "./pages/roles/Permissions.vue";
import Login from "./pages/auth/Login.vue";
import Terminal from "./pages/terminal/Terminal.vue";
import Dashboard from "./pages/auth/Dashboard.vue";
import FareTable from "./pages/fareTable/FareTable.vue";
import RoutePage from "./pages/route/RoutePage.vue";
import CitiesPage from "./pages/city/CitiesPage.vue";
import DiscountPage from "./pages/discount/DiscountPage";
import SurchargePage from "./pages/surcharge/SurchargePage";
import SchedulePage from "./pages/schedule/SchedulePage";
import FareClass from "./pages/fareClass/FareClassPage";
import BusesPage from "./pages/buses/BusesPage";
import BookingPage from "./pages/booking/BookingPage";
import store from './store.js';

const url = '/kt/'

const routes = [
    {
        path: url + "",
        component : Users,
        name:"home",
    },
    {
        path: url + "login",
        component : Login,
        name:"login",
    },
    {
        path: url + "users",
        component : Users,
        name:"users",
    },
    {
        path: url + "roles",
        component : Roles,
        name:"roles",
    },
    {
        path: url + "permissions/:id",
        component :Permissions,
        name:"role.permission"
    },
    {
        path: url + "profiles",
        component : Profile,
        name:"profile"
    },
    {
        path: url + "companies",
        component : Company,
        name:"company"
    },
    {
        path: url + "terminals",
        component : Terminal,
        name:"terminal"
    },
    {
        path: url + "admin/dashboard",
        component : Dashboard,
        name:"admin-dashboard"
    },
    {
        path: url + "fare-table",
        component : FareTable,
        name:"fare-table"
    },
    {
        path: url + "fare-class",
        component : FareClass,
        name:"fare-class"
    },
    {
        path: url + "routes",
        component : RoutePage,
        name:"routes-page"
    },
    {
        path: url + "discounts",
        component : DiscountPage,
        name:"discount-page"
    },
    {
        path: url + "surcharge",
        component : SurchargePage,
        name:"surcharge-page"
    },
    {
        path: url + "cities",
        component : CitiesPage,
        name:"cities-page"
    },
    {
        path: url + "schedule",
        component : SchedulePage,
        name:"schedule-page"
    },
    {
        path: url + "buses",
        component : BusesPage,
        name:"buses-page"
    },
    {
        path: url + "booking",
        component : BookingPage,
        name:"booking-page"
    },
]
const router = createRouter({
    history:createWebHistory(),
    mode:history,
    routes,
})

// router.beforeEach(()=>{
//     console.log(this.$store);
// })

export default router
