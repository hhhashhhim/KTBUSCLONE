require('./bootstrap');
// Window.Vue = require('vue');
import { createApp } from 'vue';
import App from "./layouts/adminPanel/App.vue";
import Test from "./layouts/adminPanel/Test.vue";
import common from './common.js';
import store from './store.js';
import  router  from './routes.js';
import globalFunctions from './helpers/GlobalFunctions.js'

// Admin Panel Customization
// Vue.mixin(common); // Adding Common Functions


const app = createApp();

Object.keys(globalFunctions).forEach((key) => {
    app.config.globalProperties[`$${key}`] = globalFunctions[key]
})

app.component("main-app",App);
app.component("test-app",Test);
app.mixin(common);
app.use(common).use(store).use(router);
app.mount("#app");
