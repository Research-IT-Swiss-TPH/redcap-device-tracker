<template>
    <div class="mt-3 mb-3">
      <div  v-if="state != 'reset'">
        <small>
          current field state: <b-badge>{{state}}</b-badge>                      
        </small>
        <br/>
        <small class="text-muted mb-1 mt-3">
          Please choose action for device tracking.
        </small>
        <b-button-group class="device-tracker-interface">
          <b-button @click="openModal('modal-assign')" :disabled="isDisabledAssign" class="btn-primaryrc"><i class="fas fa-plus-circle "></i> Assign</b-button>
          <b-button :disabled="isDisabledReturn" class="btn-primaryrc"><i class="fas fa-history"></i> Return</b-button>
          <b-button :disabled="isDisabledReset" class="btn-primaryrc"><i class="fas fa-power-off"></i> Reset</b-button>
        </b-button-group>
      </div>
      <div v-else>
        <small class="text-muted mb-1 mt-3">
          Device Tracking has been finalized.
        </small>
        <p>show log here</p>
      </div>
      <ModalAssign :name="name" />
    </div>
  </template>
  
  <script>
  import HelloWorld from './components/HelloWorld.vue'
  import ModalAssign from './components/ModalAssign.vue'

  
  export default {
    name: 'App',
    components: {
      ModalAssign,
      HelloWorld
    },
    data() {
      return {

      }
    },
    props: {
        name: String,
        state: String,
        device: String,
        types: String,
    },
    methods: {
      getMessage: function() {
        return this.name + " has state: " + this.state 
      },
      openModal: function(id) {
        this.$bvModal.show(id)
      }
    },
    computed: {
      isDisabledAssign: function() {
        return !(this.state == 'no-device-selected')
      },
      isDisabledReturn: function() {
        return !(this.isDisabledAssign && this.state == 'assigned')
      },
      isDisabledReset: function() {
        return !(this.isDisabledReturn && this.state == 'returned')
      }
    }
  }
  </script>
  
  <style>
  .device-tracker-interface {
    width: 90%;
    max-width:90%;
  }
  </style>
  