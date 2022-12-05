<template>
    <div>
        <HelloWorld :msg=getMessage() />
    </div>
  </template>
  
  <script>
  import HelloWorld from './components/HelloWorld.vue'
  
  export default {
    name: 'App',
    data() {
      return {
        bar: this.foo
      }
    },
    methods: {
      getMessage() {
        return this.field + " has state: " + this.state 
      }
    },
    props: {
        field: String,
        state: String
    },
    components: {
      HelloWorld
    }
  }
  </script>
  
  <style>
  </style>
  