<template>
    <div>
        <p style="padding-top:10px;color:#800000;font-weight:bold;font-family:verdana;font-size:13px;">Tracking Logs</p>

        <b-table 
                    show-empty
                    striped 
                    hover 
                    empty-text="There have been no tracking actions yet."
                    :sort-by.sync="sortBy"
                    :sort-desc.sync="sortDesc"
                    :busy.sync="isBusy"
                    :per-page="perPage"
                    :current-page="currentPage"
                    :fields="fields"
                    :items="items">             
                </b-table>

                <b-pagination
                    v-model="currentPage"
                    :total-rows="rows"
                    :per-page="perPage"
                ></b-pagination>        
    </div>
</template>
<script>

    export default{
        data() {
            return {
                isBusy: false,
                sortBy: 'log_id',
                sortDesc: true,
                perPage: 20,
                currentPage: 1,
                fields: [
                { key: 'log_id', sortable: true, label:"ID" },
                { key: 'message', label: "Log Event"},
                { key: 'project_id', sortable: true, label: "Project"},
                { key: 'record', sortable: true },
                { key: 'user', sortable: true },
                { key: 'date', sortable: true },
                { key: 'action', sortable: false },
                { key: 'field', sortable: false },
                { key: 'value', sortable: false },
                { key: 'error'}
                ],
                items: []
            }
        },
        
        methods: {
            async getTrackingLogs() {
                    this.isBusy = true
                    this.$module
                    .ajax('get-tracking-logs')
                    .then( response => {
                        this.items  = response
                    })
                    .catch(e => {
                        console.log(e.message)
                    })
                    .finally( () => {
                        this.isBusy = false
                    })
            }
        },
        computed: {
            rows() {
                return this.items.length
            }
        },
        mounted() {
            this.getTrackingLogs()
        }
    }

</script>