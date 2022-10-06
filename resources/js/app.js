require('./bootstrap');
// Window.Vue = require('vue');
import { createApp } from 'vue';
import App from "./layouts/adminPanel/App.vue";
import Test from "./layouts/adminPanel/Test.vue";
import common from './common.js';
import store from './store.js';
import  router  from './routes.js';

// Admin Panel Customization 
// Vue.mixin(common); // Adding Common Functions


const app = createApp();
app.component("main-app",App);
app.component("test-app",Test);
app.mixin(common);
app.use(common).use(store).use(router);
app.mount("#app");
