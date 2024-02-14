<template>
    <div>
        <div v-if="!isProjectPage">
            <p style="padding-top:10px;color:#800000;font-weight:bold;font-family:verdana;font-size:13px;">Devices Project tests</p>
            <div v-if="config.length > 0" v-for="item in config" >
                <div v-if="item.valid" class="darkgreen" style="color:green;">
                    <b>TEST {{item.id}}: {{item.rule}}</b>
                    <br><br><img :src="rootPath" />
                    <b>SUCCESSFUL!</b>
                </div>
                <div v-else class="red">
                    <b>TEST {{item.id}}: {{item.rule}}</b>
                    <br><br><img :src="rootPath" />
                    <b>FAILURE!</b><br/>
                    <div v-if="item.diff">
                        <p>Required: <br><pre>{{ item.rule }}</pre></p>
                        <p>Found difference: <br><pre>{{ item.diff }}</pre></p>
                    </div>
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
                rootPath: '',
                isProjectPage
            }
        },

        mounted() {
            this.config = stph_dt_getConfigFromBackend(),
            this.rootPath = stph_dt_getRootFromBackend()+'Resources/images/tick.png',
            this.isProjectPage = stph_dt_getIsProjectPage()
        }
    }
</script>