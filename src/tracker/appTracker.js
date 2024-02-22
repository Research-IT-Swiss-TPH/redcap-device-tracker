import Vue from 'vue'
import App from './App.vue'

Vue.config.productionTip = false

//  Constants set via Backend
var backend = stph_dt_getDataFromBackend();
console.log(backend);

var stph_dt_module = stph_dt_getModuleFromBackend();
//console.log(stph_dt_module);

//  Axios  
import axios from 'axios'
import VueAxios from 'vue-axios'
Vue.use(VueAxios, axios.create({
  baseURL: backend.base_url,
}))

//  Bootstrap Vue
import { BootstrapVue } from 'bootstrap-vue'
import 'bootstrap-vue/dist/bootstrap-vue.css'
Vue.use(BootstrapVue)

//  Vue Clipboard
import VueClipboard from 'vue-clipboard2'
Vue.use(VueClipboard)

//  Add REDCap JavaScript Module Object all Vue instances globally
Vue.prototype.$module = stph_dt_module

backend.fields.forEach(function(field, idx){
  new Vue({
    render: h => h(App, {
      props: {
        page: backend.page,
        field: field
      }
    }),
  }).$mount("#STPH_DT_FIELD_"+field)
  
})
