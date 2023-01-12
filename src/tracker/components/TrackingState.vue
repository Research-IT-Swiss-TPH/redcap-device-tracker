<template>
    <b-form-group class="tracking-state">
        <small class="text-muted mb-1 mt-3">Tracking State And Device</small>
        <b-input-group size="sm" :prepend="fieldState">
            <b-form-input readonly style="width:0" :value="device || '--'" ></b-form-input>
        </b-input-group>
    </b-form-group>
</template>
<script>
export default {
    name: 'TrackingState',
    props: {
        device: String,
        state: String
    },
    computed: {
        fieldState: function() {
            if(this.state == undefined) {
                return "No state"
            }

            // if(this.state == "available" && this.device !== undefined) {
            //     return "Finalized"
            // }
            
            else {
                return this.state
            }
        }
    }
}
</script>
<style scoped>

.autowidth {
    width:auto!important;
}

.input-group-prepend .input-group-text {
    text-transform: uppercase;
    background: #2c3e50;
    color: #ecf0f1;
}
</style>