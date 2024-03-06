import Vue from 'vue'
import App from './App.vue'

Vue.config.productionTip = false

//  Passthroughs
var stph_dt_backend = stph_dt_getDataFromBackend();
var stph_dt_module = stph_dt_getModuleFromBackend();

//  Bootstrap Vue
import { BootstrapVue } from 'bootstrap-vue'
import 'bootstrap-vue/dist/bootstrap-vue.css'
Vue.use(BootstrapVue)

//  Vue Clipboard
import VueClipboard from 'vue-clipboard2'
Vue.use(VueClipboard)


//  Add REDCap JavaScript Module Object all Vue instances globally
Vue.prototype.$module = stph_dt_module

stph_dt_backend.fields.forEach(function(field, idx){
  new Vue({
    render: h => h(App, {
      props: {
        page: stph_dt_backend.page,
        field: field,
        idx:idx
      }
    }),
  }).$mount("#STPH_DT_FIELD_"+field)
  
})
