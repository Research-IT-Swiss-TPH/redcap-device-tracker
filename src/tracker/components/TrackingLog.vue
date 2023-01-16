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
            this.axios({
                params: {                        
                        action: 'get-tracking-logs',
                        owner_id: this.record,
                        tracking_field: this.field,
                    }
                })
                .then((response) => {
                    this.isProcessing = false
                    this.items = response.data
                    //console.log(response.data)
                })
                .catch(e => {
                    console.log(e)
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