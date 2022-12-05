import Vue from 'vue'
import App from './App.vue'

Vue.config.productionTip = false

var fields = { 
  "field-1": 0, 
  "field-2": 1, 
  "field-3": 0
}; 

Object.keys(fields).forEach(key => {
  new Vue({
    render: h => h(App, {
      props: {
        field: key,
        state: fields[key]
      }
    }),
  }).$mount("#"+key)

});