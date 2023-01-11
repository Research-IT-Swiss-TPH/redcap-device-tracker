<template>
    <div>
        <span v-if="!isLoaded">
            <div class="text-center">
                <b-spinner label="Loading..."></b-spinner>
            </div>
        </span>
        <span v-else-if="isLoaded&&fields.length == 0">
        <b-alert show variant="warning">No additional fields found.</b-alert>
        </span>

        <div v-if="isLoaded&&fields.length > 0">
             <b-form-group v-for="(field, index) in fields" :key="field.name">
                    <div v-if="field.type == 'text'">
                        <b-form-input 
                            v-model="additionals[field.name]"
                            :disabled="disabled" 
                            :placeholder="field.label"
                        />
                    </div>
                    <div v-else-if="field.type == 'textarea'">
                        <b-form-textarea
                            rows="3"
                            max-rows="6"
                            v-model="additionals[field.name]"
                            :disabled="disabled"
                            :placeholder="field.label"
                        />
                    </div>                    
                    <div v-else-if="field.type == 'yesno' || field.type=='truefalse'">
                        <b-form-checkbox 
                            :disabled="disabled"
                            v-model="additionals[field.name]"
                            switch size="lg">
                            {{ field.label }}
                        </b-form-checkbox>
                    </div>
                    <div v-else-if="field.type == 'select'">
                        <b-form-select
                            v-model="additionals[field.name]"
                            :disabled="disabled"
                            :options="field.enum"
                         >
                        <!-- This slot appears above the options from 'options' prop -->
                        <template #first>
                            <b-form-select-option :value="null" disabled>-- {{ field.label }} --</b-form-select-option>
                        </template>                         
                         </b-form-select>
                    </div>
                    <div v-else-if="field.type == 'radio'">
                        <b-form-group :label="field.label">
                            <b-radio-group
                                :disabled="disabled"
                                v-model="additionals[field.name]"
                                :options="field.enum"                                     
                            >
                            </b-radio-group>
                        </b-form-group>
                    </div>
                    <div v-else-if="field.type=='descriptive'">
                        <b-alert variant="info" show>
                            <i class="fa fa-info-circle" aria-hidden="true"></i> {{ field.label }}
                        </b-alert>
                    </div>
                    <div v-else>
                        <b-alert variant="warning" show>Field type <b>{{ field.type }}</b> not supported. Please contact administrator/developer or define another field type.</b-alert>
                    </div>
                    <b-form-text id="input-live-help">{{ field.note }}</b-form-text>
             </b-form-group>
        </div>

    </div>
</template>
<script>
    export default {
        name: 'AdditionalFields',
        props: {
            fields:  Array,
            isLoaded: Boolean,
            disabled: Boolean
        },
        data() {
            return {
                additionals: {}
            }
        },
        methods: {

        },
        watch: {
            additionals: {
                handler(newValue, oldValue) {
                    this.$emit('changeAdditionals', newValue)
                },
                deep: true
            }
        }

    }
</script>
<style>
</style>