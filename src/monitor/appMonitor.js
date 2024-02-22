import Vue from 'vue'
import App from './App.vue'

//  Constants set via Backend
var stph_dt_module = stph_dt_getModuleFromBackend();

//  Bootstrap Vue
import { BootstrapVue } from 'bootstrap-vue'
import 'bootstrap-vue/dist/bootstrap-vue.css'
Vue.use(BootstrapVue)

//  Add REDCap JavaScript Module Object all Vue instances globally
Vue.prototype.$module = stph_dt_module

//  Create Vue Instance and mount our module page container
new Vue({
    render: h => h(App)
  })
.$mount('#STPH_DT_MONITOR');