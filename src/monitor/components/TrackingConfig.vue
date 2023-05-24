<template>
    <div>
        <div v-if="!isProjectPage">
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
        </div>
        <div v-else>
            <p style="padding-top:10px;color:#800000;font-weight:bold;font-family:verdana;font-size:13px;">Tracking Projects tests</p>
            tbd
        </div>        
    </div>
</template>
<script>
    export default {

        data() {
            return {
                config: [],
                isProjectPage
            }
        },

        mounted() {
            this.config = stph_dt_getConfigFromBackend()
            this.isProjectPage = stph_dt_getIsProjectPage()
        }
    }
</script>