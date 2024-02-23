<template>
      <b-modal 
        id="tracking-modal" 
        header-class="justify-content-center"
        hide-header-close
        no-close-on-backdrop
        title="Device Tracker" >
            <div v-if="isActionInit">

                <div v-if="modalMode == 'assign'">
                    <b-alert show variant="info"><b>Assign device:</b> 
                        <br/>Please insert a valid device ID to process assignment. You can approve the assignment with a valid device only.
                    </b-alert>
                    <b-form >
                        <b-form-group>
                            <b-input-group class="mt-3">
                                <b-input-group-prepend>
                                    <b-input-group-text>
                                        <i class="fas fa-barcode"></i>
                                    </b-input-group-text>
                                </b-input-group-prepend>
                                <b-form-input
                                    autofocus
                                    id="device-id-search"
                                    v-on:keydown.enter.prevent
                                    :readonly="isValidDevice"
                                    :state="isValidDevice" 
                                    v-model="userInput" 
                                    lazy
                                    placeholder="Insert Device ID">
                                </b-form-input>
                                <b-input-group-append>
                                    <b-button v-if="!isValidDevice" @click="validateDevice" class="btn-primaryrc">
                                        <span v-if="isValidating">
                                            <b-spinner small></b-spinner>
                                        </span>
                                        <span v-else><i class="fas fa-search"></i></span>
                                    </b-button>
                                </b-input-group-append>
                                <b-form-valid-feedback>
                                    Device ID found. Valid device.
                                </b-form-valid-feedback>
                                <b-form-invalid-feedback>
                                    {{ device_id_invalid_feedback }}                                    
                                </b-form-invalid-feedback>                
                            </b-input-group>   
                        </b-form-group>
                    </b-form>
                </div>

                <div v-else-if="modalMode == 'return' ">
                    <b-alert show variant="info"><b>Return device:</b>
                        <br/>By approving you will set the device state of device <b>{{deviceId}}</b> to "returned".
                    </b-alert>
                </div>

                <div v-else-if="modalMode == 'reset' ">
                    <b-alert show variant="info"><b>Reset device:</b> 
                        <br/>By approving you will set the device state of device <b>{{deviceId}}</b> to "reset".
                    </b-alert>
                </div>

                <tracking-add-fields 
                    :disabled="modalMode == 'assign'&&!isValidDevice"
                    :isLoaded="isAdded"
                    :fields="additionalFields"
                    @changeAdditionals="extra=$event"
                />
            </div>

            <div v-else>
               <sweet-alert 
                :action="modalMode"
                :device="deviceId" 
                :field="field" 
                :error="actionError"
                />
            </div>

            <!-- Default Footer for all modes -->
            <template #modal-footer="{ ok, cancel, hide }">                
                <b-button v-if="isActionInit||hasActionError" :disabled="isProcessing" @click="cancel()">
                    Cancel
                </b-button>
                <b-button v-if="!isActionInit&&!hasActionError" class="btn-primaryrc" @click="complete()">
                    Complete 
                </b-button>
                <b-button v-if="isActionInit" style="text-transform: capitalize;" :disabled="modalMode == 'assign'&&!isValidDevice"  class="btn-primaryrc" @click="handleTracking()">
                    <span v-if="!isProcessing">{{ modalMode }} Device</span>
                    <b-spinner small v-else></b-spinner>
                </b-button>                                
            </template>
      </b-modal>
  </template>
  
  <script>
  import SweetAlert from './SweetAlert'
  import TrackingAddFields from './TrackingAddFields'

  export default {
    name: 'TrackingModal',
    components: {
        SweetAlert,
        TrackingAddFields
    },
    data() {
        return {
            isProcessing: false,
            //  Action Results
            isDeviceAssigned: false,
            isDeviceReturned: false,
            isDeviceReset: false,
            //  Error Handlers
            hasActionError: false,
            actionError: {},

            userInput: "",
            isValidDevice: null,
            isValidating: false,
            device_id_invalid_feedback:"",

            additionalFields: [],
            isAdded: false,
            extra: []
        }
    },
    props: {
        field: String,
        action: String,
        tracking: Object,
        page: Object
    },
    methods: {

        handleAjaxError(error){
            if(typeof error === 'string') {
                this.actionError.data = {}
                this.actionError.msg = error

            }
            console.log(typeof error)
            this.hasActionError = true
        },
        async validateDevice() {
            if(this.userInput == "") {
                this.isValidDevice = null
            } else {
                this.isValidDevice = null
                this.isValidating = true

                this.userInput = this.userInput.trim()
                
                const data = {
                    device_id: this.userInput,
                    tracking_field: this.field
                }

                this.$module
                .ajax('validate-device', data)                            
                    .then( response => {         
                        if(response) {
                            if(response.device_id == data.device_id) {
                                document.activeElement.blur();
                                this.isValidDevice = true
                            } else {
                                this.device_id_invalid_feedback = response.validation_message
                                this.isValidDevice = false
                            }

                        } else {
                            this.device_id_invalid_feedback = "Device is not available for unknown reasons. Please notify Administrator."
                            this.isValidDevice = false
                        }
                    })
                    .catch(e => {
                        this.device_id_invalid_feedback = "An error occured. Please check logs."
                        this.isValidDevice = false
                        console.log(e.message)
                    })
                    .finally( () => {
                        this.isValidating = false
                    })
            }
            
        },

        async handleTracking() {

            const data = {
                tracking_id: this.tracking.session_tracking_id,
                action: this.modalMode,
                event_id: this.page.event_id,
                owner_id: this.page.record_id,
                field_id: this.field,
                device_id: this.deviceId,
                user_id: this.page.user_id,
                extra: JSON.stringify(this.extra)
            }

            this.$module
            .ajax('handle-tracking', data)
                .then(response => {
                    console.log(response)
                    this.setProcessSuccess()
                })
                .catch(error => {
                    //console.log(error)
                    this.handleAjaxError(error)
                })
                .finally(()=>{
                    this.isProcessing = false
                })
        },
        async getAdditionalFields() {

            const data = {
                mode: this.modalMode,
                field: this.field
            }

            this.$module
            .ajax('get-additional-fields', data )
                .then((response) => {
                    this.additionalFields = response                    
                })
                .catch(e => {
                    console.log(e)
                })
                .finally(()=>{
                    setTimeout(()=>{
                        this.isAdded = true
                    }, 750)
                })            

        },

        setProcessSuccess() {
            if(this.modalMode == 'assign') {
                this.isDeviceAssigned = true
            }
            if(this.modalMode == 'return') {
                this.isDeviceReturned = true
            }
            if(this.modalMode == 'reset') {
                this.isDeviceReset = true
            }
        },

        complete() {
            //  We need to construct URL explicit, since in some cases REDCap adds an "auto" url parameters which seems broken..
            const loc = document.location
            let dest = loc.protocol + '//' + loc.host + this.page.path + '?pid='+ this.page.project_id +'&id='+this.page.record_id+'&event_id='+this.page.event_id+'&page='+this.page.name            
            location.href= dest
        },

        resetModal() {
            this.userInput = ""
            this.isValidDevice = null
            this.hasActionError = false
        }
    },
    computed: {
        modalMode: function() {
            return this.action
        },
        
        isActionInit: function() {
            if(this.modalMode == "assign" && !this.isDeviceAssigned && !this.hasActionError) {
                return true
            }
            if(this.modalMode == "return" && !this.isDeviceReturned && !this.hasActionError) {
                return true
            }
            if(this.modalMode == "reset" && !this.isDeviceReset && !this.hasActionError) {
                return true
            }
            else return false
        },

        deviceId: function() {
            if(this.modalMode == 'assign') {
                return this.userInput
            } else {
                return this.tracking.record_id
            }
        }
    },
    watch: {
        userInput(current) {
            if(current == "") {
                this.isValidDevice = null
            }
        }
    },
    mounted(){

        this.$root.$on('bv::modal::show', (bvEvent, modalId) => {            
            this.getAdditionalFields()
        })

        this.$root.$on('bv::modal::hide', (bvEvent, modalId) => {
            this.resetModal()
        })
    }
  }
  </script>
  
  <!-- Add "scoped" attribute to limit CSS to this component only -->
  <style scoped>
    #device-id-search:focus {
        box-shadow: none!important;
    }
  </style>
  