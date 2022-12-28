<template>
      <b-modal 
        id="modal-assign" 
        hide-header-close
        no-close-on-backdrop
        :title="title" >
        <div v-if="!isDeviceAssigned">
            <p>Please insert a valid device ID to continue assignment.</p>
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
                            v-on:keydown.enter.prevent
                            :readonly="isValidDevice"
                            :state="isValidDevice" 
                            v-model="userInput" 
                            lazy
                            placeholder="Insert Device ID">
                        </b-form-input>
                        <b-input-group-append>
                            <b-button v-if="!isValidDevice" @click="validateDevice" class="btn-primaryrc">
                                <span v-if="isLoading">
                                    <b-spinner small></b-spinner>
                                </span>
                                <span v-else><i class="fas fa-search"></i></span>
                            </b-button>
                        </b-input-group-append>
                        <b-form-valid-feedback>
                            Device ID found. Valid device.
                        </b-form-valid-feedback>
                        <b-form-invalid-feedback>
                            Device ID not found. Invalid device.
                        </b-form-invalid-feedback>                
                    </b-input-group>   
                </b-form-group>
                <b-form-group>
                    <b-button v-if="!isDeviceAssigned" :disabled="!isValidDevice" size="lg" block  class="btn-primaryrc" @click="assignDevice()">
                        <span v-if="!isAssigning">Process</span>
                        <b-spinner v-else></b-spinner>
                    </b-button>          
                    <b-alert variant="success" :show="isDeviceAssigned">
                        Device <b>{{userInput}}</b> has been assigned to participant with record id <b>ID</b>. You can complete the assignment to reload the participant page.
                    </b-alert>                
                </b-form-group>              
            </b-form>
        </div>
        <div v-else>
            <div class="swal2-icon swal2-success swal2-icon-show" style="display: flex;"><div class="swal2-success-circular-line-left" style="background-color: rgb(255, 255, 255);"></div>
                <span class="swal2-success-line-tip"></span> <span class="swal2-success-line-long"></span>
                <div class="swal2-success-ring"></div> <div class="swal2-success-fix" style="background-color: rgb(255, 255, 255);"></div>
                <div class="swal2-success-circular-line-right" style="background-color: rgb(255, 255, 255);"></div>
            </div>
            <h2 class="swal2-title" id="swal2-title" style="display: block;">Device assigned</h2>
            <div class="swal2-html-container text-center" id="swal2-html-container" style="display: block;">
                The device with id {{device_id}} has been assigned to field {{field.name}}.
            </div>
        </div>
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
  export default {
    name: 'ModalAssign',
    data() {
        return {
            userInput: "",
            isValidDevice: null,
            isValidating: false,
            isLoading: false,
            isDeviceAssigned: false,
            isAssigning: false,
            reloadTime: 3,
            device_id: ""
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
            }
            
        },
        async assignDevice() {
            this.isAssigning = true
            this.axios({
                    params: {
                        action: 'assign-device',
                        device_id: this.userInput,
                        owner_id: this.page.record_id,
                        tracking_field: this.field.name
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
                    this.isAssigning = false
                    console.log(e.message)
                })            
            //await new Promise(resolve => setTimeout(resolve, 1500));
            // this.axios({
            //     params: 'assign-device',
            //     device_id: this.device_id,
            //     tracking_field: this.field
            // })

        },
        complete() {
            location.reload()
        },
        resetDevice: function() {
            this.userInput = ""
            this.isValidDevice = null
        }
    },
    computed: {
        title: function() {
            return 'Assign new device'
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
            this.resetDevice()
        })
    }
  }
  </script>
  
  <!-- Add "scoped" attribute to limit CSS to this component only -->
  <style scoped>
  </style>
  