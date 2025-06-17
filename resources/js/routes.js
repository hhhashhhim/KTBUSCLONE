import Profile from "./components/Profile.vue";
import {createWebHistory, createRouter} from "vue-router";
import Users from "./pages/users/Users.vue";
import Roles from "./pages/roles/Roles.vue";
import Company from "./pages/company/Company.vue";
import Updatepassword from "./pages/auth/UpdatePassword.vue";
import Permissions from "./pages/roles/Permissions.vue";
import Login from "./pages/auth/Login.vue";
import Terminal from "./pages/terminal/Terminal.vue";
import Dashboard from "./pages/auth/Dashboard.vue";
import FareTable from "./pages/fareTable/FareTable.vue";
import SubRoute from "./pages/route/SubRoutePage.vue";
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
import ScheduleUnclosingPage from "./pages/schedule/ScheduleUnclosingPage.vue";
import ScheduleCommissionClosingPage from "./pages/schedule/ScheduleCommissionClosingPage.vue";
import ScheduleUnclosingSparePage from "./pages/schedule/ScheduleUnclosingSparePage.vue";
import ScheduleMergePage from "./pages/schedule/ScheduleMergePage.vue";
import AllBookingPage from "./pages/booking/AllBookingPage.vue";
import EmployeesPage from "./pages/hrm/employees/EmployeesPage.vue";
import LeavePage from "./pages/hrm/leave/LeavePage.vue";
import DepartmentPage from "./pages/hrm/department/DepartmentPage.vue";
import DesignationPage from "./pages/hrm/designation/DesignationPage.vue";
import TicketSettingPage from "./pages/settings/tickets/TicketSettingsPage.vue";
import ActivityLogPage from "./pages/settings/ActivityLogPage.vue";
import MaintenancePartPage from "./pages/maintenance/PartPage.vue";
import MaintenanceLinkPage from "./pages/maintenance/LinkPage.vue";
import MaintenanceDuePage from "./pages/maintenance/DuePage.vue";
import MaintenanceRecordPage from "./pages/maintenance/RecordPage.vue";
import HotelPage from "./pages/refreshment/HotelPage.vue";
import FoodPage from "./pages/refreshment/FoodPage.vue";
import FoodDealPage from "./pages/refreshment/FoodDealPage.vue";
import FoodOrderPage from "./pages/refreshment/host/FoodOrderPage.vue";
import ProfilePage from "./pages/profile/ProfilePage.vue";
import ExpenseCategoryPage from "./pages/expense/ExpenseCategoryPage.vue";
import ExpensePage from "./pages/expense/ExpensePage.vue";
import TerminalCommissionPage from "./pages/terminal/TerminalCommissionPage.vue";
import TerminalDiscountPage from "./pages/terminal/TerminalDiscountPage.vue";
import TerminalTimePage from "./pages/terminal/TerminalTimePage.vue";
import TerminalTimeDifferencePage from "./pages/terminal/TerminalTimeDifferencePage.vue";
import AccountGroupPage from "./pages/account/ledger/AccountGroupPage.vue";
import AccountHeadPage from './pages/account/ledger/AccountHeadPage.vue';
import AccountHeadBankPage from './pages/account/ledger/AccountHeadBankPage.vue';
import AccountHeadCashPage from './pages/account/ledger/AccountHeadCashPage.vue';
import BankTransactionPage from './pages/account/transaction/BankTransactionPage.vue';
import CashTransactionPage from './pages/account/transaction/CashTransactionPage.vue';
import JournalTransactionPage from './pages/account/transaction/JournalTransactionPage.vue';
import AccountReportFinancePage from './pages/account/report/AccountReportFinancePage.vue';
import loyaltyCardPage from "./pages/loyalityCard/CardCategoriesPage.vue";
import loyaltyCardAssignPage from "./pages/loyalityCard/CardAssignPage.vue";
import ReportsHeadersPage from "./pages/ReportsHeader/ReportsHeaderPage.vue";
import HeaderLinkPage from "./pages/ReportsHeader/HeaderLinkPage.vue";
import CloseSummeryReportPage from "./pages/SummeryReports/CloseSummeryReportPage.vue";
import AdvanceSaleReportsPage from "./pages/Sales/AdvanceSaleReportsPage.vue";
import TerminalCommissionReportsPage from "./pages/Sales/TerminalCommissionReportsPage.vue";
import TerminalSaleReportsPage from "./pages/Sales/TerminalSaleReportsPage.vue";
import TerminalDiscountReportsPage from "./pages/Sales/TerminalDiscountReportsPage.vue";
import ScheduleDropReportPage from "./pages/ScheduleDrop/ScheduleDropReportPage.vue";
import ConfirmCancellationPage from "./pages/Cancel/ConfirmCancelationPage.vue";
import OverIssuePage from "./pages/Cancel/OverIssuePage.vue";
import ReschedulePage from "./pages/Cancel/ReschedulePage.vue";
import CounterExpensesPage from "./pages/expense/CounterExpensesPage.vue";
import OfficeExpensesPage from "./pages/expense/OfficeExpensesPage.vue";
import InventoryProduct from "./pages/inventory/products/AddProduct.vue";
import Stock from "./pages/inventory/products/Stock.vue";
import StockInward from "./pages/inventory/products/StockInward.vue";
import StockOutward from "./pages/inventory/products/StockOutward.vue";
import Suppliers from "./pages/inventory/products/Supplier.vue";
import GPO from "./pages/inventory/products/Gpo.vue";
import PO from "./pages/inventory/products/PurchaseOrder.vue";
import MR from "./pages/inventory/products/MaterialRequest.vue";
import Reports from "./pages/inventory/products/reports.vue";
import BidSummary from "./pages/inventory/products/BidSummary.vue";
import PR from "./pages/inventory/products/PurchaseRequisitionNote.vue";

// const url = '/kt-dev/'
const url = '/'


const routes = [
    {
        path: url + "",
        component: Users,
        name: "home",
    },
    {
        path: url + "users",
        component: Users,
        name: "users",
    },
    {
        path: url + "roles",
        component: Roles,
        name: "roles",
    },
    {
        path: url + "permissions/:id",
        component: Permissions,
        name: "role.permission"
    },
    {
        path: url + "profiles",
        component: Profile,
        name: "profile"
    },
    {
        path: url + "companies",
        component: Company,
        name: "company"
    },
    {
        path: url + "update-password",
        component: Updatepassword,
        name: "update-password"
    },
    {
        path: url + "terminals",
        component: Terminal,
        name: "terminal"
    },
    {
        path: url + "admin/dashboard",
        component: Dashboard,
        name: "admin-dashboard"
    },
    {
        path: url + "fare-table",
        component: FareTable,
        name: "fare-table"
    },
    {
        path: url + "sub-routes",
        component: SubRoute,
        name: "sub-routes"
    },
    {
        path: url + "fare-class",
        component: FareClass,
        name: "fare-class"
    },
    {
        path: url + "routes",
        component: RoutePage,
        name: "routes-page"
    },
    {
        path: url + "discounts",
        component: DiscountPage,
        name: "discount-page"
    },
    {
        path: url + "surcharge",
        component: SurchargePage,
        name: "surcharge-page"
    },
    {
        path: url + "cities",
        component: CitiesPage,
        name: "cities-page"
    },
    {
        path: url + "schedule",
        component: SchedulePage,
        name: "schedule-page"
    },
    {
        path: url + "buses",
        component: BusesPage,
        name: "buses-page"
    },
    {
        path: url + "bus-class",
        component: BusClassPage,
        name: "bus-class-page"
    },
    {
        path: url + "bookings",
        component: BookingPage,
        name: "booking-page"
    },
    {
        path: url + "booking/schedule/closing",
        component: ScheduleClosingPage,
        name: "booking-schedule-closing"
    },
    {
        path: url + "booking/schedule/unclosing",
        component: ScheduleUnclosingPage,
        name: "booking-schedule-unclosing"
    },
    {
        path: url + "booking/schedule/commission/unclosing",
        component: ScheduleCommissionClosingPage,
        name: "booking-commission-closing"
    },
    {
        path: url + "booking/schedule/unclosing/spare",
        component: ScheduleUnclosingSparePage,
        name: "booking-schedule-unclosing-spare"
    },
    {
        path: url + "booking/schedule/merges",
        component: ScheduleMergePage,
        name: "booking-schedule-merges"
    },
    {
        path: url + "booking/all",
        component: AllBookingPage,
        name: "all-booking-page"
    },
    {
        path: url + "hrm/employees",
        component: EmployeesPage,
        name: "employees"
    },
    {
        path: url + "hrm/leaves",
        component: LeavePage,
        name: "leaves"
    },
    {
        path: url + "hrm/departments",
        component: DepartmentPage,
        name: "departments"
    },
    {
        path: url + "hrm/designations",
        component: DesignationPage,
        name: "designations"
    },
    {
        path: url + "fleet/maintenance/part",
        component: MaintenancePartPage,
        name: "maintenance-parts"
    },
    {
        path: url + "fleet/maintenance/link",
        component: MaintenanceLinkPage,
        name: "maintenance-link"
    },
    {
        path: url + "fleet/maintenance/due",
        component: MaintenanceDuePage,
        name: "maintenance-due"
    },
    {
        path: url + "fleet/maintenance/record",
        component: MaintenanceRecordPage,
        name: "maintenance-record"
    },
    {
        path: url + "refreshments/hotels",
        component: HotelPage,
        name: "hotels"
    },
    {
        path: url + "refreshments/hotels/specific/foods",
        component: FoodPage,
        name: "foods"
    },
    {
        path: url + "refreshments/hotels/specific/foods/deals",
        component: FoodDealPage,
        name: "foodDeals"
    },
    {
        path: url + "refreshments/hotels/food/order",
        component: FoodOrderPage,
        name: "foodOrder"
    },
    {
        path: url + "settings/tickets",
        component: TicketSettingPage,
        name: "ticketSettings"
    },
    {
        path: url + "settings/activity/log",
        component: ActivityLogPage,
        name: "activityLog"
    },
    {
        path: url + "settings/profile",
        component: ProfilePage,
        name: "profileSettings"
    },
    {
        path: url + "expense/categories",
        component: ExpenseCategoryPage,
        name: "expense-category-page"
    },
    {
        path: url + "expenses/:id",
        component: ExpensePage,
        name: "expense-page"
    },
    {
        path: url + "report/header/link/:id",
        component: HeaderLinkPage,
        name: "header-link-page"
    },
    {
        path: url + "terminals/:id/commissions",
        component: TerminalCommissionPage,
        name: "terminal-commission"
    },
    {
        path: url + "terminals/:id/discounts",
        component: TerminalDiscountPage,
        name: "terminal-discount"
    },
    {
        path: url + "terminals/:id/times",
        component: TerminalTimePage,
        name: "terminal-time"
    },
    {
        path: url + "terminal/time/difference",
        component: TerminalTimeDifferencePage,
        name: "terminal-difference"
    },
    {
        path: url + "accounts/groups",
        component: AccountGroupPage,
        name: "account-groups"
    },
    {
        path: url + "accounts/heads",
        component: AccountHeadPage,
        name: "account-head"
    },
    {
        path: url + "accounts/heads/banks",
        component: AccountHeadBankPage,
        name: "account-head-bank"
    },
    {
        path: url + "accounts/heads/cash",
        component: AccountHeadCashPage,
        name: "account-head-cash"
    },
    { 
        path: url + 'accounts/transactions/bank-transactions',
        component: BankTransactionPage,
        name: "account-transaction-bank-transactions",
    },
    { 
        path: url + 'accounts/transactions/cash-transactions',
        component: CashTransactionPage,
        name: "account-transaction-cash-transactions",
    },
    { 
        path: url + 'accounts/transactions/journal-transactions',
        component: JournalTransactionPage,
        name: "account-transaction-journal-transactions",
    },
    { 
        path: url + 'accounts/reports/finance',
        component: AccountReportFinancePage,
        name: "account-report-finance",
    },
    {
        path: url + "loyalty/card/categories",
        component: loyaltyCardPage,
        name: "loyalty-card-categories"
    },
    {
        path: url + "loyalty/card/assign",
        component: loyaltyCardAssignPage,
        name: "loyalty-card-assign"
    },
    {
        path: url + "reports/header",
        component: ReportsHeadersPage,
        name: "report-header"
    },
    {
        path: url + "reports/summary/close/trip",
        component: CloseSummeryReportPage,
        name: "summery-report"
    },
    {
        path: url + "office/expenses",
        component: OfficeExpensesPage,
        name: "office-expenses-page"
    },
    {
        path: url + "reports/advance/sale",
        component: AdvanceSaleReportsPage,
        name: "advance-sale-report"
    },
    {
        path: url + "reports/terminal/commission",
        component: TerminalCommissionReportsPage,
        name: "terminal-commission-report"
    },
    {
        path: url + "reports/terminal/sale",
        component: TerminalSaleReportsPage,
        name: "terminal-sale-report"
    },
    {
        path: url + "reports/terminal/discount",
        component: TerminalDiscountReportsPage,
        name: "terminal-discount-report"
    },
    {
        path: url + "reports/schedules/drop",
        component: ScheduleDropReportPage,
        name: "schedule-drop-report"
    },
    {
        path: url + "reports/confirm/cancel",
        component: ConfirmCancellationPage,
        name: "confirm-cancel-report"
    },
    {
        path: url + "reports/over/issue",
        component: OverIssuePage,
        name: "over-issue-report"
    },
    {
        path: url + "reports/reschedule",
        component: ReschedulePage,
        name: "reschedule-report"
    },
    {
        path: url + "counter/expenses",
        component: CounterExpensesPage,
        name: "counter-expenses-page"
    },

    {
        path: url + "inventory/products",
        component: InventoryProduct,
        name: "products"
    },
    {
        path: url + "inventory/stock",
        component: Stock,
        name: "stock"
    },
    {
        path: url + "inventory/stock-inwards",
        component: StockInward,
        name: "stockInward"
    },
    {
        path: url + "inventory/stock-outwards",
        component: StockOutward,
        name: "stockOutward"
    },
    {
        path: url + "inventory/suppliers",
        component: Suppliers,
        name: "suppliers"
    },
    {
        path: url + "inventory/gpo",
        component: GPO,
        name: "gpo"
    },
    {
        path: url + "inventory/MR",
        component: MR,
        name: "MR"
    },
    {
        path: url + "inventory/PO",
        component: PO,
        name: "PO"
    },
    {
        path: url + "inventory/reports",
        component: Reports,
        name: "reports"
    },
    {
        path: url + "inventory/BidSummary",
        component: BidSummary,
        name: "BidSummary"
    },
    {
        path: url + "inventory/PR",
        component: PR,
        name: "PR"
    },
]
const router = createRouter({
    history: createWebHistory(),
    mode: history,
    routes,
})

export default router
