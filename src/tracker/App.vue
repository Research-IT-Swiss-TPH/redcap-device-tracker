<template>
    <div class="tracking-wrapper">

      <tracking-branding />

      <b-form-group class="tracking-id">
        <small class="text-muted mb-1 mt-3">Tracking ID</small>
        <b-input-group size="sm">
            <b-form-input readonly :value="tracking.session_tracking_id || '--'" ></b-form-input>
        </b-input-group>
      </b-form-group>

      <tracking-state
        :device="tracking.record_id" 
        :state="tracking.session_device_state"
      />

      <b-form-group class="tracking-actions">
        <small class="text-muted mb-1 mt-3">
            Tracking Actions
        </small>
        <b-button-group class="device-tracker-interface">
            <b-button v-b-modal.tracking-modal :disabled="action!='assign'" class="btn-primaryrc"><i class="fas fa-plus-circle"></i> Assign</b-button>
            <b-button v-b-modal.tracking-modal :disabled="action!='return'" class="btn-primaryrc"><i class="fas fa-history"></i> Return</b-button>
            <b-button v-b-modal.tracking-modal :disabled="action!='reset'" class="btn-primaryrc"><i class="fas fa-power-off"></i> Reset</b-button>
        </b-button-group>
        <trackingModal :action="action" :field="field" :tracking="tracking" :page="page" />
      </b-form-group>      

      <!-- <tracking-log 
        v-if="logRows>0" 
        :rows="logRows" 
        :tracking="tracking"
        :field="field"
        :record="page.record_id" 
      /> -->

    </div>
  </template>
  
  <script>
  import TrackingBranding from './components/TrackingBranding.vue'
  import TrackingState from './components/TrackingState.vue'
  import TrackingLog from './components/TrackingLog.vue'
  import TrackingModal from './components/TrackingModal.vue'

  export default {
    name: 'App',
    components: {
      TrackingBranding,
      TrackingState,
      TrackingModal,
      TrackingLog
    },
    data() {
      return {
        tracking: {}
      }
    },
    props: {
        page: Object,
        field: String
    },
    methods: {

      async getTrackingData() {

        this.axios({
                    params: {
                        action: 'get-tracking-data',
                        record_id: this.page.record_id,
                        field_id: this.field
                    }
                })
                .then( response => {
                  console.log(response.data)
                  if(response.data.session_tracking_id !== undefined) {
                    this.tracking = response.data
                  } else {
                    console.log("No tracking.")
                  }
                })
                .catch(e => {
                    console.log(e.message)
                })
                .finally( () => {
                })        
      },

      getMessage: function() {
        return this.field + " has state: " + this.tracking.state 
      },
      openModal: function(id) {
        this.$bvModal.show(id)
      }
    },
    computed: {

        action: function() {
            if(this.tracking.session_device_state == undefined) {
                return "assign"
            } 
            if(this.tracking.session_device_state === 'available') {
                return 'assign'
            }
            if(this.tracking.session_device_state === 'unavailable') {
                return 'return'
            }
            if(this.tracking.session_device_state === 'maintained') {
                return 'reset'
            }            
        },

        isDisabledAssign: function() {
            return !(this.tracking.session_device_state == undefined )
        },
        isDisabledReturn: function() {
            return !(this.isDisabledAssign && this.tracking.session_device_state == 'unavailable')
        },
        isDisabledReset: function() {
            return !(this.isDisabledReturn && this.tracking.session_device_state == 'maintained')
      },
      
      logRows: function() {
        if(this.tracking.state == 'assigned') {
          return 1
        }
        if(this.tracking.state == 'returned') {
          return 2
        }
        if(this.tracking.state == 'reset') {
          return 3
        } else {
          return 0
        }
      }
    },
    mounted() {
      this.getTrackingData()
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
  