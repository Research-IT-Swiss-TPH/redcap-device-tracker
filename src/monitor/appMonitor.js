import Vue from 'vue'
import App from './App.vue'

//  Constants set via Backend
var baseURL = stph_dt_getBaseUrlFromBackend();
var page    = stph_dt_getPageInfoFromBackend();

//  Axios  
import axios from 'axios'
import VueAxios from 'vue-axios'
Vue.use(VueAxios, axios.create({
  baseURL: baseURL,
}))


//  Bootstrap Vue
import { BootstrapVue } from 'bootstrap-vue'
import 'bootstrap-vue/dist/bootstrap-vue.css'
Vue.use(BootstrapVue)

//  Create Vue Instance and mount our module page container
new Vue({
    render: h => h(App, {
      props: {
        page: page
      }
    })
  })
.$mount('#STPH_DT_MONITOR');