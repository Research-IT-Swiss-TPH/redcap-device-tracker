<template>
    <div>        
        <b-table 
            striped 
            hover 
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

export default {
    name: 'AppMonitor',
    data() {
      return {
        isBusy: false,
        sortBy: 'log_id',
        sortDesc: true,
        perPage: 20,
        currentPage: 1,
        fields: [
          { key: 'log_id', sortable: true },
          { key: 'project_id', sortable: true},
          { key: 'record', sortable: true },
          { key: 'user', sortable: true },
          { key: 'date', sortable: true },
          { key: 'action', sortable: false },
          { key: 'field', sortable: false },
          { key: 'value', sortable: false },
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
                    console.log(response.data)
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
        this.logProvider()
    }

}
</script>>