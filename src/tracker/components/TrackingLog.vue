<template>
    <div class="tracking-log-table">
        <div class="d-flex  mb-1 mt-3 justify-content-between">
            <small class="text-muted">Tracking Log</small>        
        </div>
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
        field: String,
        event_id: String
    },
    data() {
        return {
            isProcessing: true,
            items: []
        }
    },
    methods: {
        async getTrackingLogs() {
            const data = {
                record: this.record,
                field: this.field,
                event_id: this.event_id
            }
            this.$module
            .ajax('get-tracking-logs', data)
            .then((response) => {
                this.isProcessing = false                
                this.items = response.slice(-3).reverse();
                //this.items = response
            }).catch((err) => {
                console.log(err)
            });
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