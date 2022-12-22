<template>
    <div class="mt-3 mb-3">
      <div  v-if="field.state != 'reset'">
        <small>
          current field state: <b-badge>{{field.state}}</b-badge>                      
        </small>
        <br/>
        <small class="text-muted mb-1 mt-3">
          Please choose action for device tracking.
        </small>
        <b-button-group class="device-tracker-interface">
          <b-button @click="openModal('modal-assign')" :disabled="isDisabledAssign" class="btn-primaryrc"><i class="fas fa-plus-circle"></i> Assign</b-button>
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
      <ModalAssign :field="field" :page="page"/>
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
      isDisabledAssign: function() {
        return !(this.field.state == 'no-device-selected')
      },
      isDisabledReturn: function() {
        return !(this.isDisabledAssign && this.field.state == 'assigned')
      },
      isDisabledReset: function() {
        return !(this.isDisabledReturn && this.field.state == 'returned')
      }
    },
    mounted() {
    }
  }
  </script>
  
  <style>
  .device-tracker-interface {
    width: 90%;
    max-width:90%;
  }
  </style>
  