<template>
    <div class="tracking-actions">
        <small class="text-muted mb-1 mt-3">
            Tracking Actions
        </small>
        <b-button-group class="device-tracker-interface">
            <b-button v-b-modal.tracking-modal :disabled="isDisabledAssign" class="btn-primaryrc"><i class="fas fa-plus-circle"></i> Assign</b-button>
            <b-button v-b-modal.tracking-modal :disabled="isDisabledReturn" class="btn-primaryrc"><i class="fas fa-history"></i> Return</b-button>
            <b-button v-b-modal.tracking-modal :disabled="isDisabledReset" class="btn-primaryrc"><i class="fas fa-power-off"></i> Reset</b-button>
        </b-button-group>
        <trackingModal  :tracking="tracking" :page="page" />        
    </div>
</template>
<script>
  import TrackingModal from './TrackingModal.vue'
  export default {
    name: 'TrackingActions',
    props: {
        tracking: Object,
        page: Object
    },
    components: {
        TrackingModal
    },
    computed: {
        isDisabledAssign: function() {
            return !(this.tracking.state == 'no-device-selected')
        },
        isDisabledReturn: function() {
            return !(this.isDisabledAssign && this.tracking.state == 'assigned')
        },
        isDisabledReset: function() {
            return !(this.isDisabledReturn && this.tracking.state == 'returned')
      },        
    }
  }
</script>
<style scoped>
  .tracking-actions {
    margin-top:15px;
  }
  .device-tracker-interface {
    width: 100%;
  }
</style>