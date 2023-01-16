<template>
    <b-form-group class="device-tracker-header">
        <b-row>
            <b-col class="text-left"><i v-b-popover.hover.right="{ content: 'This field is handled by an external module. For more information see <a target=\'_blank\' href=\'#todo\'>documentation</a>.', html: true }" class="fas fa-satellite-dish" title="Device Tracker Module"></i></b-col>
            <b-col class="text-right">
                <b-badge><i :class="icon"></i> {{ state || "available" }}</b-badge>
            </b-col>
        </b-row>
    </b-form-group>
</template>
<script>
export default {
    name: 'TrackingHeader',
    props: {
        state: String
    },
    computed: {
        icon: function() {

            switch (this.state) {
                case 'available':
                    return "fa-regular fa-circle-check"
                    break;
                case 'unavailable':
                    return "fa-regular fa-circle-xmark"
                    break
                case 'maintained':
                    return "fa-solid fa-circle-half-stroke"
                    break                    
                default:
                    return "fa-regular fa-circle-check"
                    break;
            }
            
        }
    }
}
</script>
<style scoped>

</style>