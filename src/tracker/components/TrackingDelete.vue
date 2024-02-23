<template>
    <div>
        <div class="d-flex justify-content-end">
            <small class="text-muted">
                <span @click="showDeleteModal" class="dt-click" ><i class="fa-solid fa-trash"></i> Delete Tracking</span>
            </small>
        </div>
    <b-modal
        id="delete-modal"
        hide-header-close
        header-class="justify-content-center"
        no-close-on-backdrop
        title="Device Tracker"
        centered>

        <div v-if="deleteSuccess">
            <div class="swal2-icon swal2-success swal2-icon-show" style="display: flex;">
                <div class="swal2-success-circular-line-left" style="background-color: rgb(255, 255, 255);"></div>
                <span class="swal2-success-line-tip"></span>
                <span class="swal2-success-line-long"></span>
                <div class="swal2-success-ring"></div>
                <div class="swal2-success-fix" style="background-color: rgb(255, 255, 255);"></div>
                <div class="swal2-success-circular-line-right" style="background-color: rgb(255, 255, 255);"></div>
            </div>
            <h2 class="swal2-title" id="swal2-title" style="display: block;">Success</h2>
            <div class="swal2-html-container text-center" id="swal2-html-container" style="display: block;">
                <p class="text-center">Tracking <br><small>{{ deleteResponse.tracking_id }}</small> <br>has been deleted!</p>
                <small>Affected deleted data fields: {{ deleteResponse.deleted_data_count }}</small>
            </div>
        </div>
        <div v-if="deleteError">
            <p><b>There was an error during deletion:</b><br><br>{{ deleteErrorMessage }}<br><br>Check logs and notify an administrator.</p>
        </div>
        <div v-if="!deleteError && !deleteSuccess">
            <b-alert show variant="warning"><b>Delete Tracking</b> 
                <br/>By deleting a tracking you will remove any data associated with the tracking. This includes, synced fields, additional fields and the tracking field. Also within the device project, the relevant tracking session instance will be deleted.
            </b-alert>
            <p>Are you sure you wish to PERMANENTLY delete this tracking and ALL associated data to it?</p>
            <p><b class="text-danger">This process is permanent and CANNOT BE REVERSED.</b></p>
        </div>

        <!-- Default Footer for all modes -->
        <template #modal-footer="{ ok, cancel, hide }">                
            <b-button v-if="!deleteSuccess" :disabled="isProcessing" @click="cancel()">
                Cancel
            </b-button>
            <b-button v-if="deleteSuccess" class="btn-primaryrc" @click="complete()">
                Complete 
            </b-button>
            <b-button v-if="!deleteSuccess && !deleteError" style="text-transform: capitalize;"  class="btn-danger" @click="deleteTracking">
                <span v-if="!isProcessing">Delete Tracking</span>
                <b-spinner small v-else></b-spinner>
            </b-button>                                
        </template>

    </b-modal>
    </div>
</template>
<script>
  export default {
    name: "TrackingDelete",
    props: {
        tracking: Object,
        field: String
    },
    data() {
        return {
            isProcessing: false,
            deleteSuccess: false,
            deleteError: false,
            deleteResponse: {},
            deleteErrorMessage: ""
        }
    },
    methods: {        

        showDeleteModal() {
            this.$bvModal.show('delete-modal')
        },

        async deleteTracking() {
            this.isProcessing = true
            this.$module
            .ajax('delete-tracking', {
                tracking: this.tracking, 
                field: this.field
            })
            .then(response => {

                if(response && this.tracking.session_tracking_id == response.tracking_id) {
                    setTimeout(()=>{
                        this.isProcessing = false
                        this.deleteSuccess = true
                        this.deleteResponse = response
                        console.log(response)
                    }, 500)
                } else {
                    this.isProcessing = false
                    this.deleteError = true
                    this.deleteErrorMessage = response.error
                }

            })
            .catch(err => {
                this.isProcessing = false
                this.deleteError = true
                this.deleteErrorMessage = err
                console.log(err)
            })
        },
        complete() {
            location.reload(); 
        },
        resetModal() {
            this.isProcessing = false
            this.deleteError = false
            this.deleteSuccess = false
            this.deleteResponse = {}
            this.deleteErrorMessage = ""
        }
    },
    mounted() {
        this.$root.$on('bv::modal::hide', (bvEvent, modalId) => {
            this.resetModal()
        })
    }
  }
</script>
<style scoped>
.dt-click {
    cursor: pointer;
}

.dt-click:hover {
    color:#007bffcc!important;
}
</style>