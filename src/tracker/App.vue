<template>
    <div class="tracking-wrapper">

      <tracking-header :state="tracking.session_device_state" />

      <b-form-group class="tracking-actions">
        <small class="text-muted mb-1 mt-3">
            Tracking Actions
        </small>
        <b-skeleton v-if="isLoading" type="input"></b-skeleton>
        <div class="device-tracker-interface" v-else>
          <b-button-group v-if="hasActions"  >
            <b-button v-b-modal.tracking-modal :disabled="action!='assign'" class="btn-primaryrc"><i class="fas fa-plus-circle"></i> Assign</b-button>
            <b-button v-b-modal.tracking-modal :disabled="action!='return'" class="btn-primaryrc"><i class="fas fa-history"></i> Return</b-button>
            <b-button v-b-modal.tracking-modal :disabled="action!='reset'" class="btn-primaryrc"><i class="fas fa-power-off"></i> Reset</b-button>
          </b-button-group>
          <b-alert v-else show variant="success" class="actions-final-alert">
            Completed.
          </b-alert>
        </div>
        <trackingModal :action="action" :field="field" :tracking="tracking" :page="page" />
      </b-form-group>

      <b-form-group class="tracking-id">
        <small class="text-muted mb-1 mt-3">Tracking ID</small>
        <b-skeleton v-if="isLoading" type="input"></b-skeleton>
        <b-input-group v-else size="sm">
            <b-form-input 
              readonly 
              :value="tracking.session_tracking_id || '--'" >
            </b-form-input>
            <b-input-group-append >
                <b-button
                  copySource="Tracking ID"
                  :disabled="!tracking.session_tracking_id" 
                  v-clipboard:copy="tracking.session_tracking_id"
                  v-clipboard:success="onCopy">
                    <i class="fa-regular fa-copy"></i>
                </b-button>
            </b-input-group-append>
        </b-input-group>
      </b-form-group>

      <b-form-group class="tracking-device">
        <small class="text-muted mb-1 mt-3">Tracking Device</small>
        <b-skeleton v-if="isLoading" type="input"></b-skeleton>
        <b-input-group v-else size="sm">
            <b-form-input 
              readonly :value="tracking.record_id || '--'" >
            </b-form-input>
            <b-input-group-append >
                <b-button 
                  copySource="Tracking Device"
                  :disabled="!tracking.record_id"
                  v-clipboard:copy="tracking.record_id"
                  v-clipboard:success="onCopy">
                    <i class="fa-regular fa-copy"></i>
                </b-button>
            </b-input-group-append>            
        </b-input-group>
      </b-form-group>

      <tracking-log 
        v-if="logRows>0" 
        :rows="logRows" 
        :tracking="tracking"
        :field="field"
        :record="page.record_id"
        :event_id="page.event_id"
      />

    </div>
  </template>
  
  <script>
  import TrackingHeader from './components/TrackingHeader'
  import TrackingState from './components/TrackingState'
  import TrackingLog from './components/TrackingLog'
  import TrackingModal from './components/TrackingModal'

  export default {
    name: 'App',
    components: {
      TrackingHeader,
      TrackingState,
      TrackingModal,
      TrackingLog
    },
    data() {
      return {
        tracking: {},
        isLoading: true,
      }
    },
    props: {
        page: Object,
        field: String,
        module: Object
    },
    methods: {

      async getTrackingData() {
        
        const data = {
          record: this.page.record_id, 
          field: this.field, 
          event_id: this.page.event_id
        }

        this.$module
          .ajax('get-tracking-data', data)
          .then( response => {                 
            if(response.session_tracking_id !== undefined) {
              this.tracking = response
            } else {
              console.log("No tracking.")
            }
          })
          .catch(e => {
              console.log(e.message)
          })
          .finally( () => {
            this.isLoading = false
          })        
      },

      onCopy: function (e) {
        this.$bvToast.toast(`Copied "${e.trigger.attributes.copySource.nodeValue}" into clipboard.`, {
          title: 'Success',
          autoHideDelay: 1000,
          variant: 'success'
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

        hasActions: function() {
          return (this.tracking.session_reset_date == "" || this.tracking.session_reset_date == undefined)
        },
      
        logRows: function() {
          if(this.tracking.session_device_state == 'available' && this.hasActions) {
            return 0
          }
          if(this.tracking.session_device_state == 'unavailable') {
            return 1
          }
          if(this.tracking.session_device_state == 'maintained') {
            return 2
          } 
          if(this.tracking.session_device_state == 'available' && this.tracking.session_reset_date){
            return 3
          }
          else {
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
    form#form input[type="text"].form-control {
      max-width: none;
      width:auto;
    }
    .tracking-wrapper {
     margin-top:5px;
      margin-bottom: 5px;
      position: relative;
      max-width: 90%;
      width:90;
    }

    .device-tracker-branding:hover {
      color: #F00000;
    }

    .device-tracker-interface .btn-group{
      width: 100%;
    }

    .actions-final-alert {
      margin-bottom: 0;
    }
  </style>
  