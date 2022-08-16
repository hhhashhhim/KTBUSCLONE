require('./bootstrap');
// Window.Vue = require('vue');
import { createApp } from 'vue';
import App from "./layouts/adminPanel/App.vue";
import router from './routes.js';
import common from './common.js';
import store from './store.js';

// Admin Panel Customization 
// Vue.mixin(common); // Adding Common Functions


const app = createApp({})
app.component("mainapp",App)
app.mixin(common);
app.use(common).use(router).use(store)
app.mount("#app");
