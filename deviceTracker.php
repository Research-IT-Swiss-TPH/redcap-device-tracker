<?php namespace STPH\deviceTracker;

use Exception;
use REDCap;
use Records;
use ExternalModules\ExternalModules;

//  Require composer dependencies during development only
if( file_exists("vendor/autoload.php") ){
    require 'vendor/autoload.php';
}

//  Require custom classes
if (!class_exists("Tracking")) require_once("classes/tracking.class.php");


class deviceTracker extends \ExternalModules\AbstractExternalModule {    

    //======================================================================
    // Overview
    //======================================================================
    
    /**
     * module support for different project setups
     * 
     * from     single       instrument     single-event    single arm      ~   supported   @1.0.0
     * from     single       instrument     multiple-event  single arm      ~   supported   @1.4.0
     * from     repeating    instrument     multiple-event  single arm      ~   invalidated @1.4.0
     * *        *           *               *               multiple arms   ~   unsupported (tbd: invalidated)
     * 
     */

    //======================================================================
    // Variables
    //======================================================================

    public  $devices_project_id;
    private $devices_event_id;

    private $tracking_record;
    private $tracking_page;
    private $tracking_fields;
    private $tracking_event;


    //======================================================================
    // Methods
    //======================================================================    
    
    # Base
    # Ajax Calls
    # Controllers


    //-----------------------------------------------------
    // Base
    //-----------------------------------------------------

    private function initModule() {

        //  Setup Project Context if pid is available through request and constant is not yet defined
        if(isset($_GET["pid"]) && !defined('PROJECT_ID')) {
            define('PROJECT_ID', $this->escape($_GET["pid"]));
        }
        
        //  Set Device Project variables
        $this->setDeviceProject();
    }

    /**
     * Set Device Project variables
     * 
     * Covers test context
     * @since 1.0.0
     */
    private function setDeviceProject() {
        $this->devices_project_id = $this->getSystemSetting("devices-project");
        if(!empty($this->devices_project_id)) {
            $this->devices_event_id = (new \Project( $this->devices_project_id ))->firstEventId;
        }
    }
    
    /**
     * Hooks Device Tracker module to redcap_data_entry_form
     *
     * @since 1.0.0
     */
    public function redcap_data_entry_form_top($project_id = null) {
        if($this->isValidTrackingPage()) {
            if(!$this->isValidDevicesProject()) {
                $this->renderAlertInvalidDevices();
                $this->renderDisableTrackingField();
            }
            elseif(!$this->isValidTrackingProject()) {
                $this->renderAlertInvalidTracking();
                $this->renderDisableTrackingField();
            } 
            else {
                $this->renderTrackingInterface();
            }
        }
    }

    /**
     * Allows custom actions to be performed on any of the pages in REDCap's Control Center 
     * 
     */
    public function redcap_control_center() {
        if($this->isPage("ExternalModules/manager/control_center.php")) {
            $this->initModule();
            $config = $this->getConfig();
            $invalids = array_filter($config, function($el){
                return $el["valid"] == false;
            });
            $count = count($invalids);

            if($count > 0) {
                $this->includeControlCenterJS($count);
            }
         
        }
    }

    /**
     * Ajax 
     * 
     */
    public function redcap_module_ajax($action, $payload, $project_id, $record, $instrument, $event_id, $repeat_instance, $survey_hash, $response_id, $survey_queue_hash, $page, $page_full, $user_id, $group_id) {
        $this->initModule();

        switch ($action) {
            case 'get-tracking-data':
                $result = $this->ajax_getTrackingData($payload, $project_id);
                break;
            case 'get-additional-fields':
                $result = $this->ajax_getAdditionalFields($payload, $project_id);
                break;
            case 'get-tracking-logs':
                $result = $this->ajax_getTrackingLogs($payload, $project_id);
                break;
            case 'validate-device':
                $result = $this->ajax_validateDevice($payload);
                break;
            case 'handle-tracking':
                $result = $this->ajax_handleTracking($payload);
                break;
            case 'delete-tracking':
                $result = $this->ajax_deleteTracking($payload, $project_id, $event_id, $repeat_instance, $record, $user_id);
                break;
            default:
                // Action not defined
                throw new Exception ("Action $action is not defined");
        }
        return $result;
    }

    private function ajax_deleteTracking($payload, $project_id, $event_id, $repeat_instance, $record, $user_id){
        
        $affected_rows_tracking = 0;
        $tracking_data = $payload["tracking"];
        $field = $payload["field"];

        if(empty($tracking_data)) {
            throw new Exception("'tracking' must be set!");
        }

        if(empty($field)) {
            throw new Exception("'field' must be set!");
        }

        //  check user rights      
        $user = $this->getUser($user_id);
        $record_delete_right =  (bool) $user->getRights([$project_id])["record_delete"];
        $isSuperUser = $user->isSuperUser();
        if(!$record_delete_right && !$isSuperUser) {
            throw new Exception("user '".$user_id."' has not required user right 'record_delete' to delete a tracking.");
        }

        $tracking_settings = $this->getTrackingSettingsForField($field);

        //  start deletion
        try {

        //  Begin database transaction
        $this->beginDbTx();

        /**
         * Delete data in tracking project:
         * tracking field
         * sync fields
         * additional fields
         * 
         */

        $sync_fields = array_values(array_filter($tracking_settings, function($key){
            return strpos($key, 'sync-') === 0;
        },ARRAY_FILTER_USE_KEY));

        $additional_fields = [];
        foreach ($tracking_settings as $i => $v) {
            if(strpos($i, 'additional-fields-') === 0) {
                foreach ($tracking_settings[$i] as $j => $w) {
                    foreach ($w as $k => $x) {
                        $additional_fields[] = $x;
                    }                   
                }
            }
        }
        $fields_to_delete = array_merge(array($field), $sync_fields, $additional_fields);
        $field_names = implode("','", $fields_to_delete);

        $data_table = method_exists('\REDCap', 'getDataTable') ? \REDCap::getDataTable($project_id) : "redcap_data";
        $sqlTrackingProject = "DELETE FROM $data_table WHERE project_id = ? AND event_id = ? AND record = ? AND field_name IN ('$field_names')";       
        $query = $this->createQuery();
        $query->add($sqlTrackingProject, [$project_id, $event_id, $record]);
        $query->execute();
        $affected_rows_tracking = $query->affected_rows;


        //  2. delete in devices project
        /**
         * Delete record instance from devices project
         * 
         */
        $project_id_d = $this->devices_project_id;
        $record_d = $tracking_data["record_id"];
        $instrument_d = strtolower($tracking_data["redcap_repeat_instrument"]);
        $event_id_d = $this->devices_event_id;
        $instance_d = $tracking_data["redcap_repeat_instance"];
        $log_event_id = Records::deleteForm($project_id_d, $record_d, $instrument_d, $event_id_d, $instance_d);


        //  Write to log
        $logId = $this->log(
            "tracking-delete",
            [
                "action"=> 'tracking-delete',
                "field" => $field,
                "value" => $record_d,
                "record" => $record,
                "event" => $event_id,
                "user" => $user_id,
                "date" => date("Y-m-d H:i:s")
            ]
        );

        //  End database transaction
        $this->endDbTx();
        
        return array(
            "tracking_id" => $tracking_data["session_tracking_id"],
            "deleted_data_count" => $affected_rows_tracking
        );

        } catch(\Throwable $th) {

            //  Rollback database
            $this->rollbackDbTx();

            return array("error" => $th->getMessage());
        }

    }

    private function ajax_handleTracking($payload){
       
        if(empty($payload["device_id"])) {
            throw new Exception("'device_id' must be set!");
        }
        if(empty($payload["field_id"])) {
            throw new Exception("'field_id' must be set!");
        }
        if(empty($payload["owner_id"])) {
            throw new Exception("'owner_id' must be set!");
        }
        if(empty($payload["action"])) {
            throw new Exception("'action' must be set!");
        }
        if( !in_array( $payload["action"], array('assign', 'return', 'reset'))){
            throw new Exception("Invalid tracking action!");
        }

        $tracking = new Tracking($payload);

        try {
            //  Begin database transaction
            $this->beginDbTx();

            //  Retrieve current device instance info (assuming that current instance is last instance)            
            list($lastSessionId, $lastSessionState, $isLastSessionUntracked) = $this->getSessionData($tracking->device);
            
            //  Validate session data
            //  Do checks and determine ID of session to be saved
            $saveSessionId = $tracking->validateSessionData($lastSessionId, $lastSessionState, $isLastSessionUntracked);

            //  Retrieve tracking ID
            $currentTrackingId = $this->getCurrentTrackingId($tracking->project, $tracking->field, $tracking->owner, $tracking->event);

            //  Validate tracking ID
            $tracking->validateTrackingId($currentTrackingId);


            /**
             * 1. Save data to devices project
             * 
             */
            $saved_d = $tracking->saveDataToDevices($this->devices_project_id, $this->devices_event_id, $saveSessionId);

            //  Throw error if there were errors during save
            if(is_array($saved_d["errors"]) && count($saved_d["errors"]) !== 0) {
                throw new Exception(implode(", ", $saved_d["errors"]));
            } elseif(!empty($saved_d["errors"])) {
                throw new Exception($saved_d["errors"]);
            }

            /**
             * 2. Save data to tracking project
             * 
             */

            //  Get tracking settings
            $tracking_settings = $this->getTrackingSettingsForField($tracking->field);

            list($saved_t, $hasExtra, $sync_data) = $tracking->saveDataToTracking($tracking_settings);

            //  Check if there were any errors during save and throw error
            if(is_array($saved_t["errors"]) && count($saved_t["errors"]) !== 0) {
                throw new Exception(implode(", ", $saved_t["errors"]));
            } elseif(!empty($saved_t["errors"])) {
                throw new Exception($saved_t["errors"]);
            }            

            //  Write to log
            $logId = $this->log(
                "tracking-action",
                [
                    "action"=> $tracking->action,
                    "field" => $tracking->field,
                    "value" => $tracking->device,
                    "record" => $tracking->owner,
                    "event" => $tracking->event,
                    "session" => $lastSessionId,
                    "user" => $tracking->user,
                    "date" => $tracking->timestamp,
                    "valid" =>  true,
                    "extra" => json_encode($tracking->extra),
                    "timestamp" => $tracking->timestamp
                ]
            );

            //  End database transaction
            $this->endDbTx();

        } catch (\Throwable $th) {

            //  Rollback database
            $this->rollbackDbTx();
            
            //  Handle Error
            //  Save to logs
            $this->log("tracking-error", [
                "error" => $th->getMessage(),
                "action"=> $tracking->action,
                "field"=> $tracking->field,
                "event" => $tracking->event,
                "value"=> $tracking->device,
                "record" => $tracking->owner,
                "user" => $tracking->user,
                "date" => $tracking->timestamp
            ]);

            throw new Exception("<br><br><b>".$th->getMessage() . "</b><br>Exception thrown at line <i>" . $th->getLine(). "</i> in file <i>" . $th->getFile() . "</i>");
        }

        $response = array(
            "tracking" => $tracking,
            "devices_project" => $this->devices_project_id,
            "saved_devices" => $saved_d,
            "saved_tracking" => $saved_t ?? [],
            "log_id" => $logId,
            "extra" => array(
                "hasExtra" => $hasExtra, 
                "data" => $tracking->extra, 
                "use"=>(bool) $tracking_settings["use-additional-assign"]   // unclear
            ),
            "sync" => $sync_data ?? [],
            "settings" => $tracking_settings
        );

        return $response;
    }

    private function ajax_validateDevice($payload){
        $trackingField = $payload["tracking_field"];
        $device_id = $payload["device_id"];

        if(empty($trackingField)) {
            throw new Exception("tracking field must be set!");
        }

        if(empty($device_id)) {
            throw new Exception("device id must be set!");
        }

        $types = $this->getDeviceTypesForField($trackingField);
        $availableDevices = $this->getAvailableDevices($types);

        $device = $availableDevices[$device_id];

        //  Check if device is within available devices
        if(!isset($device)){
            return array("validation_message" => "Device is not available.");
        }

        //  Check if suspension date was set and invalidated when suspension time has passed
        $suspension_date = reset($device)["device_suspension_date"];

        if(!empty($suspension_date)){
            $suspension_date_time = strtotime(reset($device)["device_suspension_date"]);
            if( $suspension_date_time < time()) {
                return array("validation_message" => "Device (ID:".$device_id.") has been suspended:  " . $suspension_date);
            }
        }

        //  return device_id in case the device is valid
        if(isset($device)) {
            return array("device_id" => $device_id);
        }

        return false;
       
    }

    private function ajax_getTrackingData($payload, $project_id){
        
        $response = [];

        $record = $payload["record"];
        $field = $payload["field"];
        $event_id = $payload["event_id"];

        if(empty($record)) {
            throw new Exception("Record must be set!");
        }

        if(empty($field)) {
            throw new Exception("Field must be set!");
        }

        if(empty($event_id)) {
            throw new Exception("Event must be set!");
        }

        $params = [
            'project_id'    => $project_id,
            'records' => $record,
            'fields' => $field,
            'events' => $event_id,
            'return_format' => 'json'
        ];

        $data_t = json_decode( REDCap::getData($params), true);

        //  Ensure this check is secure for multiple and single events! (Also cover the case when event has data inside other instrument)
        if(empty($data_t[0][$field])) {
            return $response;
        }

        $session_tracking_id =  reset($data_t)[$field];
       
        $filterLogic = "[session_tracking_id] = '" . $session_tracking_id . "'";

        $params = array(
            'return_format' => 'json', 
            'project_id' => $this->devices_project_id,
            'exportAsLabels' => true,
            'filterLogic' => $filterLogic,
            'fields' => []
        );

        $json = REDCap::getData($params);
        $response = reset(json_decode($json));

        return $response;

    }

    private function ajax_getAdditionalFields($payload, $project_id){

        $field = $payload["field"];
        $mode = $payload["mode"];

        if(empty($field)) {
            throw new Exception("Field must be set!");
        }
        
        if(empty($mode)) {
            throw new Exception("Mode must be set!");
        }

        $additionalFields = [];

        $tracking = $this->getTrackingSettingsForField($field);
        
        if($tracking["use-additional-" . $mode]) {
            foreach ($tracking["additional-fields-" . $mode] as $key => $additionalField) {
                $additionalFields[] = $this->getFieldMetaData($additionalField["add-field-" . $mode]);
            }
        }
        return $additionalFields;
    }

    /**
     * Get Tracking Logs for Tracker and Monitor App
     * 
     */
    private function ajax_getTrackingLogs($payload, $project_id) {        
       
        $logs = [];

        $record = $payload["record"];
        $field = $payload["field"];
        $event_id = $payload["event_id"];

        //  system/project context
        if($record === null) {

            $sql = "select log_id, message, project_id, event, date, user, action, field, value, record, instance, error";
            $parameters = [];

            //  project context
            if($project_id !== null) {
                $sql = "select log_id, message, event,date, user, action, field, value, record, instance, error WHERE project_id = ?";
                $parameters = [$project_id];                
            }
            $result = $this->queryLogs($sql, $parameters);
            while($row = $result->fetch_assoc()){
                $logs[] = $this->escape($row);
            }
            $result->close();
            
        } else {

            if($field === null) {
                throw new Exception("Field must be set.");
            }
            //  record context
            $sql = "select log_id, message, user, action, field, date, record where message = ? AND record = ? AND field = ?";
            $parameters = ['tracking-action', $record, $field];
           
            $project = new \Project($project_id);
            //  longitudinal project
            if($project->longitudinal === true && $event_id !== null) {
                $sql .= " AND event = ?";
                $parameters = ['tracking-action', $record, $field, $event_id];
            }

            $result = $this->queryLogs($sql, $parameters);
            while($row = $result->fetch_object()){
                $entry = [
                    "action" => $this->escape($row->action),
                    "date" =>  $this->escape($row->date),
                    "user"=>  $this->escape($row->user)
                ];
                $logs[] = $entry;
            }            
        }
    
        return $logs;

    }


    /**
     * Include Javascript to embed module configuration error messages and link to module config check page
     * 
     */
    private function includeControlCenterJS($count) {
        $box = '<div class="red" style="margin-bottom:15px;padding:10px 15px;"><div style="color:#A00000;"><i class="fas fa-bell"></i> <span style="margin-left:3px;font-weight:bold;"><span id="module-config-error-count">'.$count.'</span> module configuration errors for Device Tracker are blocking its proper functionality. <a style="color:white;text-decoration:none;font-weight:normal;" href="'.$this->getUrl('link-control-center.php').'" class="btn btn-danger btn-xs ml-2">View config</a></span></div></div></div></div>';
        ?>
        <script type='text/javascript'>
            $(function() {
                $('#external-modules-enable-modules-button').before('<?= $box ?>');
                //$('#external-modules-enabled').find('tr[data-module="device_tracker"] td').first().find(".external-modules-byline").append("Foo");
            })
        </script>
        <?php
    }


    /**
     * Check if is a valid page for tracking and sets parameters as variables
     * 
     * 
     * @since 1.4.0
     */
    private function isValidTrackingPage() {
        //  Check if valid Data Entry page
        if(isset( $_GET['id']) && defined('USERID')) {
            
            $this->initModule();
            $all_tracking_fields = $this->getAllTrackingFields();

            //  Check if is a valid Form Page with Tracking field
            if($_GET["page"] && in_array($_GET["page"], array_keys($all_tracking_fields))) {

                //  Set tracking variables
                $this->tracking_record = $this->escape($_GET["id"]);
                $this->tracking_page = $this->escape($_GET["page"]);
                $this->tracking_event = $this->escape($_GET["event_id"]);
                $this->tracking_fields = $all_tracking_fields[$this->tracking_page];

                return true;
            }

        }
        return false;
    
    }

    /**
     * Check if module configuration is correct
     * 
     * 1. Check Device Tracking Project Configuration
     * 1.0 pid not null
     * 1.1 project->longitudinal is false
     * 1.2 project->multiple_arms is false
     * 
     * 2. Check forms
     * 2.0 Check if form exists
     * 2.1 Check if form fullfills repeating
     * 
     * 3. Check fields
     * 3.0 Check if field exist
     * 
     * 4. Check field settings
     * 4.0 Check if settings are valid
     * 
     */
    public function getConfig() {

        $config = array();

        //  Get project id in correct context
        $pid = $this->getSystemSetting("devices-project");

        /**
         * 1.0 Check if Device Project has been set
         * should not be null
         * 
         * */ 
        $config[] = array( "id" => "1-0", "valid" => $pid !== null, "rule" => "Device Project is defined.");

        // abort further checking if project unset or invalid
        if($pid !== null ) {

            //  Get project object (does not populate repeating forms..)
            $project = new \Project($pid);

            /**
             * 1.0.1 Check if Device Project id is valid
             * 
             * 
             */
            $config[] = array( "id" => "1-0-1", "valid" => $this->getProjectStatus($pid) !== null, "rule" => "Device Project is valid.");

            if($this->getProjectStatus($pid) === null){
                return $config;
            }

            /**
             * 1.1 Check if Device Project is longitudinal
             * should be false
             * 
             */
            $config[] = array( "id" => "1-1", "valid" => $project->longitudinal === false, "rule" => "Device Project is not longitudinal");

            /**
             * 1.2 Check if Device Project has multiple arms
             * should be false
             * 
             */
            $config[] = array( "id" => "1-2", "valid" => $project->multiple_arms === false, "rule" => "Device Project has no multiple arms");


            //  Get repeating forms
            $repeatingForms = array_keys(reset($project->getRepeatingFormsEvents()));

            //  Load rule schema from json file
            $schema_json = file_get_contents( dirname(__FILE__) . "/schema.json");
            $schema = json_decode($schema_json, true);

            //  Loop through all forms
            foreach ($schema["forms"] as $key_form => $form) {

                /**
                 * 2.1.x Check if form exists
                 * should be true
                 * 
                 */
                $config[] = array( "id" => "2-1-".$key_form, "valid" =>  in_array($form["name"], array_keys( $project->forms)), "rule" => "Form '" . $form["name"] . "' exists");

                /**
                 * 2.2.x Check if form is repeating
                 * should be bool of repeating
                 * 
                 */
                $config[] = array( "id" => "2-2-".$key_form, "valid" => in_array($form["name"], $repeatingForms) == $form["repeating"], "rule" => "Form '" . $form["name"] . "' is" . ($form["repeating"] ? "": " not") . " repeating");


                //  Loop through all fields for each form
                foreach ($form["fields"] as $key_field => $field) {

                    /**
                     * 3.0.x Check if field exists
                     * should be true
                     * 
                     */
                    $config[] = array( 
                        "id" => "3-0-".$key_form."-".$key_field, 
                        "valid" => $project->metadata[$field["name"]] && $project->metadata[$field["name"]]["form_name"] ==  $form["name"],
                        "rule" => "Field '" . $field["name"] . "' exists in form '" . $form["name"] . "'");
                 
                    //  Loop through all settings for each field
                    foreach ($field["settings"] as $key_setting => $setting) {

                        /**
                         * 4.0.x Check if field settings are valid
                         * should be true
                         * 
                         */

                        $setting_name = key($setting);
                        $setting_value = current($setting);

                        $config[] = array( 
                            "id" => "4-0-".$key_form."-".$key_field."-".$key_setting, 
                            "valid" => $project->metadata[$field["name"]][$setting_name] == $setting_value,
                            "rule" => "Field '" . $field["name"] . "' has setting '". $setting_name . "' with value '" . $setting_value . "'",
                            //  only shows diff of setting values, does not cover differences in setting names!
                            "diff" => join(' ', array_diff(explode(" ",$project->metadata[$field["name"]][$setting_name]), explode(" ", $setting_value)))
                        );

                    }
                }
            }
        }
        return $config;
    }

    /**
     * Validate Devices project
     * Fetch config, count invalid rules
     * 
     * 
     * @since 1.4.0
     */
    private function isValidDevicesProject() {
        $invalids = array_filter($this->getConfig(), function($el){
            return $el["valid"] == false;
        });

        return count($invalids) == 0;
    }

    /**
     * Validate Tracking project
     * 
     * 
     * @since 1.4.0
     */
    private function isValidTrackingProject() {

        $project = new \Project(PROJECT_ID);

        $longitudinal   = $project->longitudinal;
        $multiple_arms  = $project->multiple_arms;

        $RULE_SINGLE_EVENT_SINGLE_ARM = $longitudinal == false && $multiple_arms == false;
        $RULE_MULTIP_EVENT_SINGLE_ARM = $longitudinal == true && $multiple_arms == false;

        //  TBD: Check that tracking field is NOT repeating

        //  Is valid if any of those rules is true
        return $RULE_SINGLE_EVENT_SINGLE_ARM || $RULE_MULTIP_EVENT_SINGLE_ARM;
        
    }        

    /**
     * Render HTML and Javascript to insert Vue Instance
     * 
     * 
     * @since 1.0.0
     */
    private function renderTrackingInterface() {

        $this->initializeJavascriptModuleObject();

        //  Loop through all tracking fields for each form and insert for each a wrapper into DOM,
        //  so that vue can actually mount an  there.
        foreach ($this->tracking_fields as $key => $tracking_field) {
            ?>
            <div id="STPH_DT_WRAPPER_<?= $tracking_field ?>" style="display: none;">
                <div id="STPH_DT_FIELD_<?= $tracking_field ?>"></div>            
            </div>            
            <?php
        }
        //  move mounted vue instances to correct position (=vue target) 
        //  within DOM for each field
        ?>
        <script type='text/javascript'>
            $(function() {
                $(document).ready(function() {
                    var trackings =  <?=json_encode($this->tracking_fields) ?>;
                    trackings.forEach(function(tracking_field){
                        //  Insert vue target
                        var target = $('tr#'+tracking_field+'-tr').find('input');
                        var wrapper = $('#STPH_DT_WRAPPER_' + tracking_field);
                        //  Prepend
                        target.parent().prepend(wrapper);
                        wrapper.show();
                        target.hide();
                        console.log('Device Tracker initiated on field "' + tracking_field + '"');
                    });
                })
            });
        </script>
        <!-- backend data passthrough -->
        <script type='text/javascript'>
            const stph_dt_getDataFromBackend = function () {
                return <?= $this->getDataFromBackend() ?>
            }
            const stph_dt_getModuleFromBackend = function() {
                return <?=$this->getJavascriptModuleObjectName()?>;
            }
            
        </script>
        <!-- actual vue scripts -->
        <script src="<?= $this->getUrl('./dist/appTracker.js') ?>"></script>
        <?php
    }

    private function renderDisableTrackingField() {
        ?>
        <script type='text/javascript'>
            $(function() {
                $(document).ready(function() {
                    console.log("Disable tracking field due to invalid devices project.");

                    var tracking_fields = <?= json_encode($this->tracking_fields) ?>;
                    tracking_fields.forEach(field => {
                        var field_tr = $('tr#'+field+'-tr');
                        field_tr.find('td').css('background-color', '#f8d7da');
                        field_tr.find('input').prop('disabled', true);
                    });

                });
            });
        </script>
        <?php
    }

    private function renderAlertInvalid($msg) {
        ?>
        <script type='text/javascript'>
            $(function() {
                $(document).ready(function() {
                    console.log("Invalid tracking project.")
                    var msg = '<?= $msg ?>'
                    var alert_html = '<div class="alert alert-danger alert-dismissible fade show" role="alert">'+msg+'<button type="button" class="close" data-dismiss="alert" aria-label="Close"> <span aria-hidden="true">&times;</span> </button></div>';
                    $('form#form div').first().prepend(alert_html);
                });
            });
        </script>
        <?php    
    }

    private function renderAlertInvalidDevices() {
        $msg = "Invalid <b>Devices Project</b><br>The current devices project is not a valid Devices Project. Please notify a REDCap administrator and check the documentation to correctly setup your devices project with Device Tracker module.";
        $this->renderAlertInvalid($msg);
    }    

    /**
     * Render invalid Tracking Page
     * 
     * @since 1.4.0
     */
    private function renderAlertInvalidTracking() {
        $msg = "Invalid <b>Tracking Project</b><br>The current tracking project is not a valid Tracking Project. Please check the documentation to correctly setup your tracking project with Device Tracker module.";
        $this->renderAlertInvalid($msg);  
    }


    /**
     * Get data from backend to pass into vue instance(s)
     * 
     * @since 1.0.0
     */
    private function getDataFromBackend() {

        $timeout = $this->getSystemSetting("timeout") ?? 1500;
        //  Validate Timout Setting
        if(!is_numeric($timeout) || $timeout < 500) {
            $timeout = 1500;
        }
        
        //  Initialize
        $data = [];

        //  Base URL for Axios Requests
        $data["base_url"] = $this->getUrl("requestHandler.php");

        //  Page Data
        $data["page"] = [
            "path"       => PAGE_FULL,
            "user_id"    => USERID,
            "project_id" => PROJECT_ID,
            "record_id"  => $this->tracking_record,
            "name"       => $this->tracking_page,
            "event_id"   => $this->tracking_event,
            "timeout"    => $timeout
            
        ];

        $data["fields"] = $this->tracking_fields;

        return json_encode($data);

    }


    //-----------------------------------------------------
    // Controllers
    //-----------------------------------------------------

    /**
     * Get a list of all available devices by [device_type]
     * where [session_device_state] is blank or 0
     * 
     * @since 1.0.0
     */
    private function getAvailableDevices(array $types=[]) :array {

        //  General filter logic
        $filterLogic = '([session_device_state][last-instance] = 0 or isblankormissingcode([session_device_state][last-instance]))';

        //  Add filter by type(s) if given
        $filterForTypes = "";
        if(!empty($types)) {
            if(count($types) == 1) {
                $filterForTypes = "[device_type] = " . $types[0];
            } else {             
                $filterForTypes = "(";
                foreach ($types as $key => $type) {
                    if($key == 0) {
                        $filterForTypes .= "[device_type] = " . $type ;
                    } else {
                        $filterForTypes .= " or [device_type] = " . $type;
                    }
                }
                $filterForTypes .= ")";
            }
            $filterLogic .= " and " . $filterForTypes;
        }



        $params = array(
            'project_id' => $this->getSystemSetting("devices-project"),
            'filterLogic'=> $filterLogic, 
            'fields'=>array('record_id', 'device_type', 'session_device_state', 'device_registration_date' ,'device_suspension_date')
        );

        return REDCap::getData($params);
    }   

    /**
     * Get Tracking Settings for a Tracking Field
     * 
     * @since 1.0.0
     */
    private function getTrackingSettingsForField($tracking_field) {
        $trackings = $this->getSubSettings('trackings');
        $trackings_filtered = array_filter($trackings, function($tracking) use ($tracking_field){
            return $tracking["tracking-field"] == $tracking_field;
        });

        if(count($trackings_filtered) > 1) {
            throw new Exception("Invalid trackings count for field " . $tracking_field);
        }

        $trackingSettings = reset($trackings_filtered);

        return $trackingSettings;
    }

    /**
     * Get REDCap field meta data from redcap_meta_data table
     * 
     * @since 1.0.0
     */
    private function getFieldMetaData($field) {
        $sql = 'SELECT * FROM redcap_metadata WHERE project_id = ? AND field_name = ?';
        $result =  $this->query($sql, [PROJECT_ID, $field]);
     
        if($result->num_rows == 1) {

            $fieldMetaData = $result->fetch_object();
            $result->close();

            $enum = [];
            //  ENUM formatting
            $parsedEnum = parseEnum($fieldMetaData->element_enum);
            foreach ($parsedEnum as $value => $text) {
                $enum[] = array(
                    "value" => $value,
                    "text" => $text
                );
            }

            return array(
                "name"  => $fieldMetaData->field_name,
                "type"  => $fieldMetaData->element_type,
                "label" => $fieldMetaData->element_label,
                "note"  => $fieldMetaData->element_note,
                "valid" => $fieldMetaData->element_validation_type,
                "enum"  => $enum
             );                
        }
    }

    /**
     * Use database transactions in case there is an error
     * no data will be saved in any of the save procedures
     * https://www.mysqltutorial.org/mysql-transaction.aspx
     * 
     * 
     * @since 1.0.0
     */
    private function beginDbTx() {    
        $this->query("SET autocommit = 0;", []);
        $this->query("START TRANSACTION;", []);
    }

    private function endDbTx() {
        $this->query("COMMIT;", []);
        $this->query("SET autocommit = 1;", []);
    }

    private function rollbackDbTx() {
        $this->query("ROLLBACK;", []);
        $this->query("SET autocommit = 1;", []);
    }

    private function getSessionData($device_id) {
        //  Fetch all data for device
        $data = REDCap::getData(array(
            'return_format' => 'array', 
            'project_id' => $this->devices_project_id,
            'records' => $device_id,
            //'fields' => ["session_device_state"],
            'exportDataAccessGroups' => true
        ));

        //  Find last instance for relevant device (could be that instances have been deleted inbetween, so counting is bad )        
        $sessions = (array) $data[$device_id]["repeat_instances"][$this->devices_event_id]["sessions"];
        //  First check if we have any session, set 0 if not
        if(count($sessions) == 0) {
            $lastSessionId = 0;
        } else {
            $lastSessionId = max(array_keys($sessions));
        }
        //  Find sessions state of last id
        $lastSessionState  = $sessions[$lastSessionId]["session_device_state"];

        //  Check if last session is empty 
        $isLastSessionUntracked = empty($sessions[$lastSessionId]["session_tracking_id"]);

        return array($lastSessionId, $lastSessionState, $isLastSessionUntracked);
    }


    /**
     * Get current tracking id from database
     * 
     * @since 1.0.0
     */
    private function getCurrentTrackingId($pid, $field, $id, $event) {
        // Add support for multiple redcap_data tables
        $data_table = method_exists('\REDCap', 'getDataTable') ? \REDCap::getDataTable($pid) : "redcap_data";
        $result = $this->query(
                    "SELECT value FROM $data_table WHERE project_id = ? AND field_name = ? AND record = ? AND event_id = ?", 
                    [ $pid, $field, $id, $event]
                );
        return $result->fetch_object()->value;
    }    

    /**
     * Get trackings in useful structure
     * 
     * @since 1.0.0
     */
    private function getAllTrackingFields() {
        $all_tracking_fields = [];
        foreach ($this->getSubSettings('trackings') as $key => $tracking) {
            $form = $this->getFormForField($tracking['tracking-field']);
            $field = $tracking['tracking-field'];
            $all_tracking_fields[$form][] = $field;
        }
        return $all_tracking_fields;
    }

    /**
     * Get device types
     * Return a list of devices types that are defined in field setting
     * 
     * 
     * @since 1.0.0
     */
    private function getDeviceTypesForField(string $field) :array {
        $trackings = $this->getSubSettings('trackings');
        $str = "";
        foreach ($trackings as $key => $settings) {
            if($settings['tracking-field'] == $field) {
                //  Return empty array if types not set
                if($settings["device-types"] == "") {
                    return [];                    
                } 
                $str = $settings["device-types"];
                break;
            }
        }

        //  Check if has comma separation and explode with trim
        if( strpos($str, ",") >= -1) {
            return array_map('trim', explode(',', $str));
        } else {
            return array(trim($str));
        }
        
    }    
}
