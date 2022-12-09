import Vue from 'vue'
import App from './App.vue'

Vue.config.productionTip = false

var fields = stph_dt_getFieldMetaFromBackend();
console.log(fields);

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


// Object.keys(fields).forEach(key => {
//   new Vue({
//     render: h => h(App, {
//       props: {
//         field: key,
//         state: fields[key]
//       }
//     }),
//   }).$mount("#STPH_DT_FIELD_"+key)

// });