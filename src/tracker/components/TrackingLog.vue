<template>
    <div class="tracking-log-table">
        <small class="text-muted mb-1 mt-3">Tracking Log</small>
        <b-skeleton-table
        v-if="isProcessing"
        :rows="rows"
        :columns="3"
        :table-props="{ bordered: true, striped: true, small: true }"
        ></b-skeleton-table>
        <b-table 
            v-else-if="!isProcessing && items.length > 0"
            :items="items"
            bordered
            striped
            small
        ></b-table>
        <b-alert v-else variant="warning" show><b>Warning:</b> Tracking log not found.</b-alert>
    </div>
</template>
<script>
  export default {
    name: 'TrackingLog',
    props: {
        rows: Number,
        record: String,
        field: String
    },
    data() {
        return {
            isProcessing: true,
            items: []
        }
    },
    methods: {
        async getTrackingLogs() {
            stph_dt_jsmo
                .ajax('get-tracking-logs', {
                    owner: this.record,
                    field: this.field
                })
                .then( (response) => {
                    //console.log(response)
                    this.items = response
                })
                .catch( (e) => {
                    console.log(e)
                })
                .finally( () => {
                    this.isProcessing = false
                })
        }
    },
    mounted() {
        setTimeout(()=>{
            this.getTrackingLogs()
        }, 750)
        
    }
}
</script>
<style scoped>
</style>