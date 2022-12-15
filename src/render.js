import Vue from 'vue'
import App from './App.vue'

Vue.config.productionTip = false

//  Constants set via Backend
var baseURL = stph_dt_getBaseUrlFromBackend();
console.log(baseURL);
var fields = stph_dt_getFieldMetaFromBackend();


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

fields.forEach(function(field, idx){
  new Vue({
    render: h => h(App, {
      props: {
        name: field.name,
        state: field.state,
        device: field.device
      }
    }),
  }).$mount("#STPH_DT_FIELD_"+field.name)
})
