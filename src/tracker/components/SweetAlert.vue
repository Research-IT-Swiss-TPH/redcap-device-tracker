<template>
    <div>
        <div v-if="!hasError">
            <div class="swal2-icon swal2-success swal2-icon-show" style="display: flex;">
                <div class="swal2-success-circular-line-left" style="background-color: rgb(255, 255, 255);"></div>
                <span class="swal2-success-line-tip"></span>
                <span class="swal2-success-line-long"></span>
                <div class="swal2-success-ring"></div>
                <div class="swal2-success-fix" style="background-color: rgb(255, 255, 255);"></div>
                <div class="swal2-success-circular-line-right" style="background-color: rgb(255, 255, 255);"></div>
            </div>
            <h2 class="swal2-title" id="swal2-title" style="display: block;">Success</h2>
            <div  v-if="action=='assign'" class="swal2-html-container text-center" id="swal2-html-container" style="display: block;">
                The device with id {{device}} has been assigned to field {{field}}.
            </div>
            <div  v-if="action=='return'" class="swal2-html-container text-center" id="swal2-html-container" style="display: block;">
                The device with id {{device}} has been returned.
            </div>

            <div  v-if="action=='reset'" class="swal2-html-container text-center" id="swal2-html-container" style="display: block;">
                The device with id {{device}} has been reset.
            </div>
        </div>
        <div v-else>
            <div class="swal2-icon swal2-error swal2-icon-show" style="display: flex;">
                <span class="swal2-x-mark">
                    <span class="swal2-x-mark-line-left"></span>
                    <span class="swal2-x-mark-line-right"></span>
                </span>
            </div>
            <h2 class="swal2-title" id="swal2-title" style="display: block;">Error!</h2>
            <div class="swal2-html-container text-center" id="swal2-html-container" style="display: block;">
                <b-alert show variant="danger">
                    <!-- <p><b>{{ error.message }}</b><br>{{ JSON.parse(error.request.response).error }}</p> -->
                    <p><b>{{ error.msg }}</b></p>
                    <p v-if="error.data.line && error.data.file">Exception thrown at line <i>{{ error.data.line }}</i> in file <i>{{ error.data.file }}</i>:</p>
                    <p class="text-monospace">{{ error.data.message }}</p>
                    <hr>
                    <p class="mb-0">
                        Please notfiy a REDCap administrator about the issue.
                    </p>
                </b-alert>
            </div>            
        </div>

    </div>
</template>
<script>
    export default {
        name: "SweetAlert",
        props: {
            action: String,
            device: String,
            field: String,
            error: Object
        },
        data() {
           return {
           
           }
        },
        computed: {
            hasError: function() {
                return (this.error.data !== undefined)
            } 
        }

    }
</script>
<style scoped></style>