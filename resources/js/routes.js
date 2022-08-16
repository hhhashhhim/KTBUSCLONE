import Vue from "vue";
import Welcome from "./components/Welcome.vue";
import Profile from "./components/Profile.vue";
import { createWebHistory,createRouter } from "vue-router";

import Users from "./pages/users/Users.vue";
import Roles from "./pages/roles/Roles.vue";
import Company from "./pages/company/Company.vue";
import Permissions from "./pages/roles/Permissions.vue";
import Login from "./pages/auth/Login.vue";
import AdminRoles from "./pages/admin/roles/Roles.vue";

const routes = [
    {
        path:"/",
        component : Welcome,
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
    }
]
const router = createRouter({
    history:createWebHistory(),
    mode:history,
    routes,
})

export default router