require('./bootstrap');
// Window.Vue = require('vue');
import { createApp } from 'vue';
import Select2 from 'vue3-select2-component';
import App from "./layouts/adminPanel/App.vue";
import Test from "./layouts/adminPanel/Test.vue";
import common from './common.js';
import store from './store.js';
import  router  from './routes.js';
import globalFunctions from './helpers/GlobalFunctions.js'

window.Swal = require('sweetalert2');

// Admin Panel Customization
// Vue.mixin(common); // Adding Common Functions

const syncPersistedState = async () => {
    await store.dispatch("initializeAppState", { force: true });
};

const bootstrapApplication = async () => {
    const app = createApp();

    Object.keys(globalFunctions).forEach((key) => {
        app.config.globalProperties[`$${key}`] = globalFunctions[key]
    });

    app.component('select2', Select2);
    app.component("main-app",App);
    app.component("test-app",Test);
    app.mixin(common);
    app.use(common).use(store).use(router);

    await store.dispatch("initializeAppState");

    if (typeof window !== "undefined") {
        window.addEventListener("storage", (event) => {
            if (!event.key || event.key === "user" || event.key === "token") {
                syncPersistedState();
            }
        });

    }

    app.mount("#app");
};

bootstrapApplication();
