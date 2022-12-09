<template>
    <div class="mt-3 mb-3">
        <div>
          <b-badge variant="info">{{this.state}}</b-badge>
        </div>
        <b-button-group class="device-tracker-interface" size="sm" v-if="this.state != 'reset'">
          <b-button :disabled="this.isDisabledAssign" class="btn-primaryrc"><i class="fas fa-plus-circle "></i> Assign</b-button>
          <b-button :disabled="this.isDisabledReturn" class="btn-primaryrc"><i class="fas fa-history"></i> Return</b-button>
          <b-button :disabled="this.isDisabledReset" class="btn-primaryrc"><i class="fas fa-power-off"></i> Reset</b-button>
        </b-button-group>

    </div>
  </template>
  
  <script>
  import HelloWorld from './components/HelloWorld.vue'
  
  export default {
    name: 'App',
    components: {
      HelloWorld
    },
    data() {
      return {
        bar: this.foo
      }
    },
    props: {
        name: String,
        state: String,
        device: String
    },
    methods: {
      getMessage() {
        return this.name + " has state: " + this.state 
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
  