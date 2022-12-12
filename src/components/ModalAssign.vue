<template>
      <b-modal 
        id="modal-assign" 
        v-on:close="resetDevice"
        no-close-on-backdrop
        :title="title" >
        <b-form >
            <b-form-group
                description="Only a valid device can be assigned.">
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
        </b-form>
        <template #modal-footer="{ ok }">
            <b-button :disabled="!isValidDevice" size="lg" block  class="btn-primaryrc" @click="ok()">
                Assign {{userInput}} 
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
            isLoading: false
        }
    },
    props: {
        name: String
    },
    methods: {
        async validateDevice() {            
            if(this.userInput == "") {
                this.isValidDevice = null
            } else {
                this.isLoading = true
                //  Make async request to backend
                await new Promise(resolve => setTimeout(resolve, 500));
                this.isLoading = false
                if(this.userInput == "foo") {
                        document.activeElement.blur();
                        this.isValidDevice = true
                    } else {
                        this.isValidDevice = false
                    }
            }
        },
        resetDevice: function() {
            this.userInput = ""
            this.isValidDevice = null
        }
    },
    computed: {
        title: function() {
            return 'Assign device for "' +this.name + '"'
        }
    },
    watch: {
        userInput(current) {
            if(current == "") {
                this.isValidDevice = null
            }
        }
    }
  }
  </script>
  
  <!-- Add "scoped" attribute to limit CSS to this component only -->
  <style scoped>
  </style>
  