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
                        <br/>Please insert a valid device ID to process assignment. You can approve the assignment with a valid device only.</b-alert>
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
                                    Device ID not found or invalid device state.
                                </b-form-invalid-feedback>                
                            </b-input-group>   
                        </b-form-group>
                        <b-form-group>
                            <b-button :disabled="!isValidDevice" size="lg" block  class="btn-primaryrc" @click="processAction()">
                                <span v-if="!isProcessing">Approve</span>
                                <b-spinner v-else></b-spinner>
                            </b-button>             
                        </b-form-group>              
                    </b-form>
                </div>
                <div v-else-if="modalMode == 'return' ">
                    <b-alert show variant="info"><b>Return device:</b>
                        <br/>By approving you will set the device state of device <b>{{deviceId}}</b> to "returned".</b-alert>
                    <b-form >
                        <b-form-group>
                            <b-button size="lg" block  class="btn-primaryrc" @click="processAction()">
                                <span v-if="!isProcessing">Approve</span>
                                <b-spinner v-else></b-spinner>
                            </b-button>
                        </b-form-group>
                    </b-form>
                </div>
                <div v-else-if="modalMode == 'reset' ">
                    <b-alert show variant="info"><b>Reset device:</b> 
                        <br/>By approving you will set the device state of device <b>{{deviceId}}</b> to "reset".</b-alert>
                    <b-form >
                        <b-form-group>
                            <b-button size="lg" block  class="btn-primaryrc" @click="processAction()">
                                <span v-if="!isProcessing">Approve</span>
                                <b-spinner v-else></b-spinner>
                            </b-button>
                        </b-form-group>
                    </b-form>
                </div>                
            </div>
            <div v-else>
               <sweet-alert 
                :action="modalMode"
                :device="deviceId" 
                :field="field.name" 
                :error="actionErrorMessage"
                />
            </div>
        <!-- Default Footer for all modes -->
        <template #modal-footer="{ ok, cancel, hide }">
            <b-button v-if="isActionInit" :disabled="isProcessing" @click="cancel()">
                Cancel
            </b-button>
            <b-button v-if="!isActionInit" class="btn-primaryrc" @click="complete()">
                Complete 
            </b-button>
        </template>
      </b-modal>
  </template>
  
  <script>
  import SweetAlert from './SweetAlert.vue'
  export default {
    name: 'TrackingModal',
    components: {
        SweetAlert
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
            actionErrorMessage: "",
            //  Validation helpers during action 'assign'
            userInput: "",
            isValidDevice: null,
            isValidating: false
        }
    },
    props: {
        field: Object,
        page: Object
    },
    methods: {
        async validateDevice() {
            if(this.userInput == "") {
                this.isValidDevice = null
            } else {
                this.isValidDevice = null
                this.isValidating = true
                this.axios({
                    params: {
                        action: 'validate-device',
                        device_id: this.userInput,
                        tracking_field: this.field.name
                    }
                })
                .then( response => {
                    //this.device_id = response.data.device_id;
                    document.activeElement.blur();
                    this.isValidDevice = true
                })
                .catch(e => {
                    this.isValidDevice = false
                    console.log(e.message)
                })
                .finally( () => {
                    this.isValidating = false
                })
            }
            
        },

        async processAction() {
            this.isProcessing = true
            this.axios({
                params: {                        
                        action: this.modalMode + '-device',
                        //  pid
                        //  REDCap automatically appends pid to every async GET request, 
                        //  so we do not need to specifically send it
                        event_id: this.page.event_id,
                        owner_id: this.page.record_id,
                        field_id: this.field.name,
                        device_id: this.deviceId,
                        extra: "TO DO"
                    }
                })
                .then(() => {
                    this.setProcessSuccess()
                })
                .catch(e => {
                    this.hasActionError = true
                    //this.error = e
                    this.actionErrorMessage = e.message
                })
                .finally(()=>{
                    this.isProcessing = false
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
            location.reload()
        },

        resetModal() {
            this.userInput = ""
            this.isValidDevice = null
            this.hasActionError = false
        }
    },
    computed: {
        modalMode: function() {
            if(this.field.state == "no-device-selected") {
                return "assign"
            }
            if(this.field.state == "assigned") {
                return "return"
            }
            if(this.field.state = "returned") {
                return "reset"
            }
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
                return this.field.device
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
  