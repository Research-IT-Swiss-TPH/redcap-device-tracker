<template>
    <div>
        <span v-if="!isLoaded">
            <div class="text-center">
                <b-spinner variant="warning" type="grow" label="Loading..."></b-spinner>
            </div>
        </span>
        <span v-else-if="isLoaded&&fields.length == 0">
        <b-alert show variant="warning">No additional fields found.</b-alert>
        </span>

        <div v-if="isLoaded&&fields.length > 0">
             <b-form-group v-for="field in fields" :key="field.name">
                    <div v-if="field.type == 'text'">
                        <b-form-input 
                            :disabled="disabled"
                            :placeholder="field.label"
                        />
                    </div>
                    <div v-else-if="field.type == 'textarea'">
                        <b-form-textarea
                            rows="3"
                            max-rows="6"
                            :disabled="disabled"
                            :placeholder="field.label"
                        />
                    </div>                    
                    <div v-else-if="field.type == 'yesno'">
                        <b-form-checkbox 
                            :disabled="disabled"
                            switch size="lg">
                            {{ field.label }}
                        </b-form-checkbox>
                    </div>
                    <div v-else-if="field.type == 'select'">
                        <b-form-select
                            :value="null"
                            :disabled="disabled"
                            :options="field.enum"
                         >
                        <!-- This slot appears above the options from 'options' prop -->
                        <template #first>
                            <b-form-select-option :value="null" disabled>-- {{ field.label }} --</b-form-select-option>
                        </template>                         
                         </b-form-select>
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
        methods: {

        }

    }
</script>
<style>
</style>