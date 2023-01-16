import Vue from 'vue'
import App from './App.vue'

Vue.config.productionTip = false

//  Constants set via Backend
var backend = stph_dt_getDataFromBackend();
console.log(backend);

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
