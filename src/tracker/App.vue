<template>
    <div class="tracking-wrapper">

      <tracking-branding />

      <tracking-state 
        :device="field.device" 
        :state="field.state"
      />

      <tracking-actions  
        v-if="field.state != 'reset'" 
        :field="field" 
        :page="page"
      />

      <tracking-log 
        v-if="logRows>0" 
        :rows="logRows" 
        :field="field.name" 
        :record="page.record_id" 
      />

    </div>
  </template>
  
  <script>
  import TrackingBranding from './components/TrackingBranding.vue'
  import TrackingState from './components/TrackingState.vue'
  import TrackingLog from './components/TrackingLog.vue'
  import TrackingActions from './components/TrackingActions.vue'

  export default {
    name: 'App',
    components: {
      TrackingBranding,
      TrackingState,
      TrackingActions,
      TrackingLog
    },
    data() {
      return {
      }
    },
    props: {
        page: Object,
        field: Object
    },
    methods: {
      getMessage: function() {
        return this.field.name + " has state: " + this.field.state 
      },
      openModal: function(id) {
        this.$bvModal.show(id)
      }
    },
    computed: {
      logRows: function() {
        if(this.field.state == 'assigned') {
          return 1
        }
        if(this.field.state == 'returned') {
          return 2
        }
        if(this.field.state == 'reset') {
          return 3
        } else {
          return 0
        }
      }
    },
    mounted() {

    }
  }
  </script>
  <style scoped>
    .tracking-wrapper {
     margin-top:5px;
      margin-bottom: 5px;
      position: relative;
      max-width: 90%;
      width:90;
    }
    .device-tracker-branding {
      position: absolute;
      right: 0;
      color: grey;
    }
    .device-tracker-branding:hover {
      color: #F00000;
    }
  </style>
  