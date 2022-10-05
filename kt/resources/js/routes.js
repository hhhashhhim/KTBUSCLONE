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


const routes = [
    {
        path:"/",
        component : Users,
        name:"home",
    },
    {
        path:"/login",
        component : Login,
        name:"login",
    },
    {
        path:"/users",
        component : Users,
        name:"users",
    },
    {
        path:"/roles",
        component : Roles,
        name:"roles",
    },
    {
        path:"/permissions/:id",
        component :Permissions,
        name:"role.permission"
    },
    {
        path:"/profile",
        component : Profile,
        name:"profile"
    },
    {
        path:"/company",
        component : Company,
        name:"company"
    },
    {
        path:"/terminal",
        component : Terminal,
        name:"terminal"
    },
    {
        path:"/admin/dashboard",
        component : Dashboard,
        name:"admin-dashboard"
    },
    {
        path:"/fare-table",
        component : FareTable,
        name:"fare-table"
    },
    {
        path:"/fare-class",
        component : FareClass,
        name:"fare-class"
    },
    {
        path:"/routes",
        component : RoutePage,
        name:"routes-page"
    },
    {
        path:"/discounts",
        component : DiscountPage,
        name:"discount-page"
    },
    {
        path:"/surcharge",
        component : SurchargePage,
        name:"surcharge-page"
    },
    {
        path:"/cities",
        component : CitiesPage,
        name:"cities-page"
    },
    {
        path:"/schedule",
        component : SchedulePage,
        name:"schedule-page"
    },
    {
        path:"/buses",
        component : BusesPage,
        name:"buses-page"
    },
    {
        path:"/booking",
        component : BookingPage,
        name:"booking-page"
    },
]
const router = createRouter({
    history:createWebHistory(),
    mode:history,
    routes,
})

export default router
