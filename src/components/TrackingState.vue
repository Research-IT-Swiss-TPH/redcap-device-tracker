<template>
    <div class="tracking-state">
        <small class="text-muted mb-1 mt-3">Tracking State</small>
        <b-input-group size="sm" :prepend="state">
            <b-form-input readonly style="width:0" :value="device || '--'" ></b-form-input>
        </b-input-group>

    </div>  
</template>
<script>
export default {
    name: 'TrackingState',
    props: {
        device: String,
        state: String
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