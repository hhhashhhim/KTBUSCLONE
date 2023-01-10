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
import DiscountPage from "./pages/discount/DiscountPage.vue";
import SurchargePage from "./pages/surcharge/SurchargePage.vue";
import SchedulePage from "./pages/schedule/SchedulePage.vue";
import FareClass from "./pages/fareClass/FareClassPage.vue";
import BusesPage from "./pages/buses/BusesPage.vue";
import BusClassPage from "./pages/buses/BusClassPage.vue";
import BookingPage from "./pages/booking/BookingPage.vue";
import ScheduleClosingPage from "./pages/schedule/ScheduleClosingPage.vue";
import AllBookingPage from "./pages/booking/AllBookingPage.vue";
import EmployeesPage from "./pages/hrm/employees/EmployeesPage.vue";
import LeavePage from "./pages/hrm/leave/LeavePage.vue";
import DepartmentPage from "./pages/hrm/department/DepartmentPage.vue";
import DesignationPage from "./pages/hrm/designation/DesignationPage.vue";
import TicketSettingPage from "./pages/settings/tickets/TicketSettingsPage.vue";
import MaintenancePartPage from "./pages/maintenance/PartPage.vue";
import MaintenanceLinkPage from "./pages/maintenance/LinkPage.vue";
import MaintenanceDuePage from "./pages/maintenance/DuePage.vue";
import MaintenanceRecordPage from "./pages/maintenance/RecordPage.vue";
import HotelPage from "./pages/refreshment/HotelPage.vue";
import FoodPage from "./pages/refreshment/FoodPage.vue";
import FoodDealPage from "./pages/refreshment/FoodDealPage.vue";
import FoodOrderPage from "./pages/refreshment/host/FoodOrderPage.vue";
import ProfilePage from "./pages/profile/ProfilePage.vue";

// const url = '/kt/'
const url = '/'

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
        path: url + "bus-class",
        component : BusClassPage,
        name:"bus-class-page"
    },
    {
        path: url + "booking",
        component : BookingPage,
        name:"booking-page"
    },
    {
        path: url + "booking/schedule/closing",
        component : ScheduleClosingPage,
        name:"booking-schedule-closing"
    },
    {
        path: url + "all-booking",
        component : AllBookingPage,
        name:"all-booking-page"
    },
    {
        path: url + "hrm/employee",
        component : EmployeesPage,
        name:"employees"
    },
    {
        path: url + "hrm/leave",
        component : LeavePage,
        name:"leaves"
    },
    {
        path: url + "hrm/department",
        component : DepartmentPage,
        name:"departments"
    },
    {
        path: url + "hrm/designation",
        component : DesignationPage,
        name:"designations"
    },
    {
        path: url + "fleet/maintenance/part",
        component : MaintenancePartPage,
        name:"parts"
    },
    {
        path: url + "fleet/maintenance/link",
        component : MaintenanceLinkPage,
        name:"maintenance-link"
    },
    {
        path: url + "fleet/maintenance/due",
        component : MaintenanceDuePage,
        name:"maintenance-due"
    },
    {
        path: url + "fleet/maintenance/record",
        component : MaintenanceRecordPage,
        name:"maintenance-record"
    },
    {
        path: url + "refreshments/hotels",
        component : HotelPage,
        name:"hotels"
    },
    {
        path: url + "refreshments/hotels/specific/foods",
        component : FoodPage,
        name:"foods"
    },
    {
        path: url + "refreshments/hotels/specific/foods/deals",
        component : FoodDealPage,
        name:"foodDeals"
    },
    {
        path: url + "refreshments/hotels/food/order",
        component : FoodOrderPage,
        name:"foodOrder"
    },
    {
        path: url + "settings/tickets",
        component : TicketSettingPage,
        name:"ticketSettings"
    },
    {
        path: url + "settings/profile",
        component : ProfilePage,
        name:"profileSettings"
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
