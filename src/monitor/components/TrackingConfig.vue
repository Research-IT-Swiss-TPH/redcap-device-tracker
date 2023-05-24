<template>
    <div>
        <p style="padding-top:10px;color:#800000;font-weight:bold;font-family:verdana;font-size:13px;">Devices Project tests</p>
        <div v-for="item in config" >
            <div v-if="item.valid" class="darkgreen" style="color:green;">
                <b>TEST {{item.id}}: {{item.rule}}</b>
                <br><br><img src="/redcap_v13.1.32/Resources/images/tick.png" />
                <b>SUCCESSFUL!</b>
            </div>
            <div v-else class="red">
                <b>TEST {{item.id}}: {{item.rule}}</b>
                <br><br><img src="/redcap_v13.1.32/Resources/images/exclamation.png" />
                <b>FAILURE!</b><br/>                
            </div>
        </div>
        <p style="padding-top:10px;color:#800000;font-weight:bold;font-family:verdana;font-size:13px;">Tracking Projects tests</p>
        tbd
        
    </div>
</template>
<script>
    export default {

        data() {
            return {
                config: []
            }
        },

        mounted() {
            this.config = stph_dt_getConfigFromBackend()
        }
    }
</script>