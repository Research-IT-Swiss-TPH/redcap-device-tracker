<template>
      <b-modal 
        id="tracking-modal" 
        header-class="justify-content-center"
        hide-header-close
        no-close-on-backdrop
        title="Device Tracker" >
            <div v-if="isActionInit">
                <div v-if="modalMode == 'assign'">
                    <b-alert show variant="info"><b>Assign device:</b> <br/>Please insert a valid device ID to process assignment. You can approve the assignment with a valid device only.</b-alert>
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
                            <b-button v-if="!isDeviceAssigned" :disabled="!isValidDevice" size="lg" block  class="btn-primaryrc" @click="assignDevice()">
                                <span v-if="!isAssigning">Approve</span>
                                <b-spinner v-else></b-spinner>
                            </b-button>             
                        </b-form-group>              
                    </b-form>
                </div>
                <div v-else-if="modalMode == 'return' ">
                    <b-alert show variant="info"><b>Return device:</b> <br/>By approving you will set the device state of device <b></b>to returned.</b-alert>
                    <b-form >
                        <b-form-group>
                            <b-button size="lg" block  class="btn-primaryrc" @click="returnDevice()">
                                <span v-if="!isReturning">Approve</span>
                                <b-spinner v-else></b-spinner>
                            </b-button>
                        </b-form-group>
                    </b-form>
                </div>
            </div>
            <div v-else>
               <sweet-alert 
                :device="device_id" 
                :field="field.name" 
                :error="actionErrorMessage"
                />
            </div>
        <!-- Default Footer for all modes -->
        <template #modal-footer="{ ok, cancel, hide }">
            <b-button v-if="!isDeviceAssigned" :disabled="isAssigning" @click="cancel()">
                Cancel
            </b-button>
            <b-button v-if="isDeviceAssigned" class="btn-primaryrc" @click="complete()">
                Complete 
            </b-button>
        </template>
      </b-modal>
  </template>
  
  <script>
  import SweetAlert from './SweetAlert.vue'
  export default {
    name: 'ModalAssign',
    components: {
        SweetAlert
    },
    data() {
        return {
            //  Action Results
            isDeviceAssigned: false,
            isDeviceReturned: false,
            isDeviceReset: false,
            //  Error Handlers
            hasActionError: false,
            actionErrorMessage: "",

            //  Action 'assign'
            userInput: "",
            isValidDevice: null,
            isValidating: false,
            isAssigning: false,
            device_id: "",

            //  Action 'return'
            isReturning: false,

            //  Action 'reset'
            isReseting: false

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
                    this.device_id = response.data.device_id;
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
        async assignDevice() {
            this.isAssigning = true
            this.axios({
                    params: {
                        action: 'assign-device',
                        //  REDCap automatically appends pid to every async GET request, so we do not need to send it
                        event_id: this.page.event_id,
                        owner_id: this.page.record_id,
                        field_id: this.field.name,
                        device_id: this.userInput,
                        extra: "TO DO"
                    }
                })
                .then( response => {
                    console.log(response.data)
                    //this.device_id = response.data.device_id;
                    this.isAssigning = false
                    this.isDeviceAssigned = true
                })
                .catch(e => {
                    //this.isValidDevice = false
                    this.hasActionError = true
                    this.isAssigning = false
                    this.actionErrorMessage = e.message
                    //console.log(e.message)
                })
        },
        async returnDevice() {
            this.isReturning = true
            this.axios({
                    params: {
                        action: 'return-device',
                        //  REDCap automatically appends pid to every async GET request, so we do not need to send it
                        event_id: this.page.event_id,
                        owner_id: this.page.record_id,
                        field_id: this.field.name,
                        device_id: this.field.device,
                        extra: "TO DO"
                    }
                })
                .then( response => {
                    console.log(response.data)
                    //this.device_id = response.data.device_id;
                    this.isReturning = false
                    this.isDeviceReturned = true
                })
                .catch(e => {
                    //this.isValidDevice = false
                    this.hasActionError = true
                    this.isReturning = false
                    this.actionErrorMessage = e.message + ": " + e.response.data
                })
        },
        complete() {
            location.reload()
        },
        resetModal: function() {
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
  