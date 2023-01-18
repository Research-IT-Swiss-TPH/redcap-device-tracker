<template>
    <div>        
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

        <div v-if="page.project_id != null && page.super_user == true">
            <b-button v-b-modal.validate-logs-modal variant="warning">Validate Logs</b-button>
        </div>
        <b-alert v-else variant="warning" show><b>Validating logs</b> is currenlty only available on project level.</b-alert>

        <!-- Validate Logs Modal -->
        <b-modal 

            @ok="validateLogs"
            centered
            title="Validate Logs" 
            ok-title="Remove"
            ok-variant="danger"
            id="validate-logs-modal">
            <div v-if="!isRemoving && totalRemoved == null">
                This action will permanently delete all invalid logs. Are you sure?
            </div>
            <div v-else-if="totalRemoved > 0">
                <b-alert variant="success" show>Removed <b>{{ totalRemoved  }} invalid log entries.</b></b-alert>
            </div>
            <div v-else-if="totalRemoved == 0">
                <b-alert variant="warning" show>All logs are valid. Nothing removed.</b-alert>
            </div>
            <div v-else>
                    Removing...                
            </div>
            <template #modal-footer="{ ok, cancel, hide }">
                <div>
                    <b-button
                    v-if="!isRemoving && totalRemoved == null"
                    variant="danger"
                    class="float-right"
                    @click="ok">
                    Remove
                    </b-button>                    
                    <b-button
                    v-if="!isRemoving && totalRemoved == null"
                    class="float-right"
                    @click="cancel">
                    Cancel
                    </b-button>
                    <b-button 
                        v-if="totalRemoved != null"
                        @click="cancel">
                        Close
                    </b-button>
                </div>
            </template>            
        </b-modal>

    </div>
</template>
<script>

export default {
    name: 'AppMonitor',
    props: {
        page: Object
    },
    data() {
      return {
        isRemoving: false,
        totalRemoved: null,
        isBusy: false,
        sortBy: 'log_id',
        sortDesc: true,
        perPage: 20,
        currentPage: 1,
        fields: [
          { key: 'log_id', sortable: true },
          { key: 'message'},
          { key: 'project_id', sortable: true},
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
        async logProvider() {
                this.isBusy = true
                this.axios({
                    params: {
                        action: 'provide-logs'
                    }
                })
                .then( response => {
                    this.items  = response.data
                    //console.log(response.data)
                })
                .catch(e => {
                    console.log(e.message)
                })
                .finally( () => {
                    this.isBusy = false
                })
        },
        async validateLogs(bvModalEvent) {
            bvModalEvent.preventDefault()
            this.isRemoving = true
            this.axios({
                    params: {
                        action: 'validate-logs'
                    }
                })
                .then( response => {               
                    this.totalRemoved = response.data.total
                    //console.log(response.data)
                })
                .catch(e => {
                    console.log(e.message + ": " + e.response.data.error)
                })
                .finally( () => {
                    this.isRemoving = false
                })
        }
    },
    computed: {
      rows() {
        return this.items.length
      }
    },
    mounted() {
        this.logProvider()

        this.$root.$on('bv::modal::hide', (bvEvent, modalId) => {
            this.isRemoving = false
            this.totalRemoved = null
        })
    }

}
</script>>