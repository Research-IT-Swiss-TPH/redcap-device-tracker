<template>
    <div>
        <b-tabs content-class="mt-3">
            <b-tab title="Config" ><p>
                <tracking-config />
            </p></b-tab>
            <b-tab title="Logs" active>
                <tracking-logs />
            </b-tab>
        </b-tabs>
    </div>
</template>
<script>
import TrackingConfig from './components/TrackingConfig.vue'
import TrackingLogs from './components/TrackingLogs.vue'
export default {
    name: 'AppMonitor',
    components: {
        TrackingConfig,
        TrackingLogs
    }

}
</script>
<style>
 .tab-content {
    border: none;
    padding: 0;
 }
</style>