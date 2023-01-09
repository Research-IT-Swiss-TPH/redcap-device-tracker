<template>
    <div class="device-tracker-branding">
        <i v-b-popover.hover.right="{ content: 'This field is handled by an external module. For more information see <a target=\'_blank\' href=\'https://to.do\'>documentation</a>.', html: true }" class="fas fa-satellite-dish" title="Device Tracker Module"></i>
    </div>
</template>
<script>
export default {
    name: 'TrackingBranding'
}
</script>