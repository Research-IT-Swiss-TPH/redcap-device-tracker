import Vue from 'vue'
import App from './App.vue'

Vue.config.productionTip = false

var fields = stph_dt_getTrackingFieldsWithStateFromBackend();

console.log(fields);

Object.keys(fields).forEach(key => {
  new Vue({
    render: h => h(App, {
      props: {
        field: key,
        state: fields[key]
      }
    }),
  }).$mount("#STPH_DT_FIELD_"+key)

});