<?php namespace STPH\deviceTracker;

use Exception;
use REDCap;
use ExternalModules\ExternalModules;

//  Require composer dependencies during development only
if( file_exists("vendor/autoload.php") ){
    require 'vendor/autoload.php';
}

//  Classes
if (!class_exists("Tracking"))  require_once("classes/tracking.class.php");

class deviceTracker extends \ExternalModules\AbstractExternalModule {    

    //======================================================================
    // Architecture
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

    private bool $isTesting = false;

    //======================================================================
    // Methods
    //======================================================================    
    
    # Base
    # Request Handler Calls (public)
    # Controllers


    //-----------------------------------------------------
    // Base
    //-----------------------------------------------------

    /**
     * Construct the class
     * 
     * @since 1.0.0
     */
    public function __construct()
    {
        parent::__construct();

        //  Check if we are in testing context
        $this->isTesting = ExternalModules::isTesting();

        //  Setup Project Context if pid is available through request and constant is not yet defined
        if(isset($_GET["pid"]) && !defined('PROJECT_ID')) {
            define('PROJECT_ID', $this->escape($_GET["pid"]));
            // global $Proj;
            // $Proj = new Project(PROJECT_ID);
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
        $this->devices_project_id = $this->isTesting ? self::getTestSystemSetting("devices-project") : $this->getSystemSetting("devices-project");
        $this->devices_event_id = (new \Project( $this->devices_project_id ))->firstEventId;
    }

    /**
     * Mock test system setting that 
     * are not accessible through getSystemSetting
     * 
     * @since 1.0.0
     */
    private static function getTestSystemSetting($key) {
        if($key == "devices-project") {
            //  Return first Test Project
            return ExternalModules::getTestPIDs()[0];
        }
    }    
    
    /**
     * Hooks Device Tracker module to redcap_every_page_top
     *
     * @since 1.0.0
     */
    public function redcap_every_page_top($project_id = null) {


        if($this->isValidTrackingPage()) {
            if(!$this->isValidDevicesProject()) {
                $this->renderAlertInvalidDevices();
            }
            if(!$this->isValidTrackingProject()) {
                $this->renderAlertInvalidTracking();
            } else {
                $this->renderTrackingInterface();
            }
        }
    }

    /**
     * Check if is a valid page for tracking and sets parameters as variables
     * 
     * 
     * @since 1.4.0
     */
    private function isValidTrackingPage() {
        //  Check if valid Data Entry page
        if($this->isPage('DataEntry/index.php') && isset( $_GET['id']) && defined('USERID')) {

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
     * Validate Devices project
     * 
     * 
     * @since 1.4.0
     */
    private function isValidDevicesProject() {
        $pid = $this->isTesting ? self::getTestSystemSetting("devices-project") : $this->getSystemSetting("devices-project");
        $project = new \Project($pid);

        return !$project->longitudinal && !$project->multiple_arms;

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

        $tracking_fields = $this->tracking_fields;

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
        <script>
            $(function() {
                $(document).ready(function() {
                    var trackings =  <?=json_encode($tracking_fields) ?>;
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
        <script>
            const stph_dt_getDataFromBackend = function () {
                return <?= $this->getDataFromBackend() ?>
            }
        </script>
        <!-- actual vue scripts -->
        <script src="<?= $this->getUrl('./dist/appTracker.js') ?>"></script>
        <?php
    }

    private function renderAlertInvalid($msg) {
        ?>
        <script>
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
        $msg = "Invalid <b>Devices Project</b><br>The current devices project is not a valid Devices Project. Please check the documentation to correctly setup your devices project with Device Tracker module.";
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
            "event_id"   => $this->tracking_event
            
        ];

        $data["fields"] = $this->tracking_fields;

        return json_encode($data);

    }


    //-----------------------------------------------------
    // Request Handler Calls (public)
    //-----------------------------------------------------

        /**
     * Get Tracking Data from session_tracking_id
     * 
     * 
     * @since 1.0.0
     */
    public function getTrackingDataBU($record_id, $field_id, $event_id){

        $response = [];

        $params = [
            'project_id'    => PROJECT_ID, 
            'records' => $record_id,
            'fields' => $field_id,
            'events' => $event_id,
            'return_format' => 'json'
        ];

        $data_t = json_decode( REDCap::getData($params), true);

        //  Ensure this check is secure for multiple and single events! (Also cover the case when event has data inside other instrument)
        if(empty($data_t[0][$field_id])) {
            $this->sendResponse($response);
        }

        $session_tracking_id =  reset($data_t)[$field_id];
       
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

        $this->sendResponse($response); 
    }   

    /**
     * Get Tracking Data from session_tracking_id
     * 
     * 
     * @since 1.0.0
     */
    public function getTrackingData($project_id, $record_id, $field_id, $event_id = null){

        $response = [];

        $params = [
            'project_id'    => $project_id,
            'records' => $record_id,
            'fields' => $field_id,
            'events' => $event_id,
            'return_format' => 'json'
        ];

        $data_t = json_decode( REDCap::getData($params), true);

        //  Ensure this check is secure for multiple and single events! (Also cover the case when event has data inside other instrument)
        if(empty($data_t[0][$field_id])) {
            return $response;
        }

        $session_tracking_id =  reset($data_t)[$field_id];
       
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

    /**
     * Validate device by device_id and tracking_field
     * 
     * 
     * @since 1.0.0
     */
    public function validateDevice(string $device_id, string $trackingField) {

        $types = $this->getDeviceTypesForField($trackingField);
        $availableDevices = $this->getAvailableDevices($types);

        $device = $availableDevices[$device_id];
        if(isset($device)) {
            $this->sendResponse(
                array("device_id" => $device_id)
            );
        } else {
            $this->sendError(404);
        }

    }
    

    /**
     * Get Additional Fields for different actions
     * 
     * @since 1.0.0
     */
    public function getAdditionalFields($mode, $field) {

        $additionalFields = [];

        $tracking = $this->getTrackingForField($field);
        
        if($tracking["use-additional-" . $mode]) {
            foreach ($tracking["additional-fields-" . $mode] as $key => $additionalField) {
                $additionalFields[] = $this->getFieldMetaData($additionalField["add-field-" . $mode]);
            }
        }

        $this->sendResponse($additionalFields);

    }

    /**
     * Handle Tracking Action
     * 
     * 
     * @since 1.0.0
     */
    public function handleTracking(Tracking $tracking) {

        //  Initialize variables
        $tracking_settings = [];       
        $dataValues_t = [];

        try {
            //  Begin database transaction
            $this->beginDbTx();

            //  Retrieve current device instance info (assuming that current instance is last instance)
            //  list($currentInstanceId, $currentInstanceState) = $this->getCurrentDeviceInfo($tracking->device);
            list($lastSessionId, $lastSessionState, $isLastSessionUntracked) = $this->getSessionData($tracking->device);
            //  Do checks and determine ID of session to bes saved (different for every action)
            if($tracking->mode == 'assign') {
                if( ($lastSessionState != "") && ($lastSessionState != 0) ) {
                    throw new Exception ("Invalid current instance state. Expected 0, found: " . $lastSessionState);
                }

                if( ($lastSessionState != "") && $isLastSessionUntracked) {
                    $saveSessionId = $lastSessionId;
                } else {
                    $saveSessionId = $lastSessionId + 1;
                }
            }

            if($tracking->mode == 'return') {
                if( $lastSessionState != 1) {
                    throw new Exception ("Invalid current instance state. Expected 1, found: " . $lastSessionState);
                }
                $saveSessionId = $lastSessionId;
            }

            if($tracking->mode == 'reset') {
                if( $lastSessionState != 2) {
                    throw new Exception ("Invalid current instance state. Expected 2, found: " . $lastSessionState);
                }
                $saveSessionId = $lastSessionId;
            }

            //  Retrieve tracking ID
            $currentTrackingId = $this->getCurrentTrackingId($tracking->project, $tracking->field, $tracking->owner);

            //  Do checks (different for every action)
            if($tracking->mode == 'assign') {
                if(!empty($currentTrackingId)) {
                    throw new Exception("Invalid current tracking field. Expected NULL found: " . $currentTrackingId);
                }
            } else {
                if($currentTrackingId != $tracking->id) {
                    throw new Exception("Invalid current tracking field. Expected ".$tracking->id." found: " . $currentTrackingId);
                } 
            }

            /**
             * 1. Save data to devices project
             * 
             */

            //  Prepare parameters for actual saving
            $params_d = [
                'project_id' => $this->devices_project_id,
                'data' => $tracking->getDataDevices($saveSessionId, $this->devices_event_id)
            ];
            //  Perform actual saving
            $saved_d = REDCap::saveData($params_d);

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

            foreach ($this->getSubSettings('trackings') as $key => $settings) {
                if($settings["tracking-field"] == $tracking->field) {
                    $tracking_settings =  $settings;
                    break;
                }
            }


            //  Save tracking id into tracking project
            if($tracking->mode == 'assign') {
                $dataValues_t[$tracking->field] = $tracking->id;                
            }            

            //  To Do: Validate
            //  Validate extra fields with tracking field instructions
            //  Validate extra fields with actual fields in form
            //  Check if action has extras
            $hasExtra = !empty($tracking->extra) && $this->checkHasExtra($tracking_settings, $tracking->mode);
            if($hasExtra) {
                //  Add extra fields to data to be saved
                foreach ($tracking->extra as $key => $value) {
                    //  push values to fields and add to $dataValues_t
                    $dataValues_t[$key] = $value;
                }                
            }

            //  Check if sync is enabled
            $hasSync = $this->checkHasSync($tracking_settings);

            //  to do: process this through tracking class method (need module instance inside tracking class)
            //  optional: add warnings when setting is empty
            if($hasSync) {

                $sync_data = [];

                if($tracking->mode == 'assign' && !empty($tracking_settings["sync-date-assign"])) {
                    $sync_data[$tracking_settings["sync-date-assign"]] = $tracking->timestamp;
                }
                
                if($tracking->mode == 'return' && !empty($tracking_settings["sync-date-return"]) ) {
                    $sync_data[$tracking_settings["sync-date-return"]] = $tracking->timestamp;
                }

                if($tracking->mode == 'reset' && !empty($tracking_settings["sync-date-reset"]) ) {
                    $sync_data[$tracking_settings["sync-date-reset"]] = $tracking->timestamp;
                }                

                if( !empty($tracking_settings["sync-state"])) {
                    $sync_data[$tracking_settings["sync-state"]] = $tracking->getDeviceStateByMode();
                }
                
                //  Add sync fields to data to be saved
                foreach ($sync_data as $key => $value) {
                    //  push values to fields and add to $dataValues_t
                    $dataValues_t[$key] = $value;
                }
            }       

            //  Perform actual save only if we have data for the specific action to be saved or sync in enabled
            //  to do
            if($tracking->mode == 'assign' || $hasSync || $hasExtra) {
                $data_t = [ $tracking->owner => [$tracking->event => $dataValues_t ] ];
                
                $params_t = [
                    'project_id' => $tracking->project,
                    'data' => $data_t
                ];
                $saved_t = REDCap::saveData($params_t);

                //  Check if there were any errors during save and throw error
                if(is_array($saved_t["errors"]) && count($saved_t["errors"]) !== 0) {
                    throw new Exception(implode(", ", $saved_t["errors"]));
                } elseif(!empty($saved_t["errors"])) {
                    throw new Exception($saved_t["errors"]);
                }
            }

            //  Write to log
            $logId = $this->log(
                "tracking-action",
                [
                    "action"=> $tracking->mode,
                    "field"=> $tracking->field,
                    "value"=> $tracking->device,
                    "record" => $tracking->owner,
                    "session" => $lastSessionId,
                    "user" => $tracking->user,
                    "date"  => $tracking->timestamp,
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
                "action"=> $tracking->mode,
                "field"=> $tracking->field,
                "value"=> $tracking->device,
                "record" => $tracking->owner,
                "user" => $tracking->user,
            ]);

            //  Send to Frontend
            $this->sendError(500, $th, $tracking_settings);
        }

        $response = array(
            "tracking" => $tracking,
            "devices_project" => $this->devices_project_id,
            "saved_devices" => $saved_d,
            "saved_tracking" => $saved_t ?? [],
            "log_id" => $logId,
            "extra" => array("hasExtra" => $hasExtra, "data" => $tracking->extra, "use"=>(bool) $tracking_settings["use-additional-assign"]),
            "sync" => $sync_data ?? [],
            "settings" => $tracking_settings
        );

        $this->sendResponse($response);         
    }

    /**
     * Provide logs for monitoring
     * Limits to project context
     * 
     * @since 1.0.0
     */
    public function provideLogs() {

        //  Initiate logs variable
        $logs = [];

        //  Default Query
        $sql = "select log_id, message, project_id, message, date,  user, action, field, value, record, instance, error";
        $parameters = [];

        //  Project Page specific query (limit output to current pid only)
        if(defined('PROJECT_ID')) {
            $sql .= " WHERE project_id = ?";
            $parameters = [PROJECT_ID];
        }

        //  Run query
        $result = $this->queryLogs($sql, $parameters);
        while($row = $result->fetch_assoc()){
            $logs[] = $this->escape($row);
        }
        $result->close();

        //  Return response
        $this->sendResponse($logs);

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
            'fields'=>array('record_id', 'device_type', 'session_device_state')
        );

        return REDCap::getData($params);
    }   

    /**
     * Check if current action has extra fields
     * enabled by mode/action
     * 
     */
    private function checkHasExtra($settings, $mode):bool {

        if($mode == 'assign') {
            return (bool) $settings["use-additional-assign"];
        }

        if($mode == 'return') {
            return (bool) $settings["use-additional-return"];
        }

        if($mode == 'reset') {
            return (bool) $settings["use-additional-reset"];
        }

        return false;

    }

    /**
     * Check if current action has device data sync 
     * enabled by mode/action
     * 
     */
    private function checkHasSync($settings):bool {
        return (bool) $settings["use-sync-data"];
    }

    /**
     * Get Tracking for Field
     * 
     * @since 1.0.0
     */
    private function getTrackingForField($field) {
        $trackings = $this->getSubSettings('trackings');
        $trackings_filtered = array_filter($trackings, function($tracking) use ($field){
            return $tracking["tracking-field"] == $field;
        });

        if(count($trackings_filtered) > 1) {
            throw new Exception("Invalid trackings count for field " . $field);
        }

        return reset($trackings_filtered);
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
    private function getCurrentTrackingId($pid, $field, $id) {
        $result = $this->query(
                    "SELECT value FROM redcap_data WHERE project_id = ? AND field_name = ? AND record = ?", 
                    [ $pid, $field, $id ]
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


    /**
     * Get Logs for a tracking/field pair
     * 
     * @since 1.0.0
     */
    public function getTrackingLogs($record, $field) {

        $sql = "select log_id, message, user, action, field, date where message = ? AND record = ? AND field = ?";
        $parameters = ['tracking-action', $record, $field];

        $result = $this->queryLogs($sql, $parameters);
        $logs = [];
        while($row = $result->fetch_object()){
            $entry = [
                "action" => $row->action,
                "date" => $row->date,
                "user"=> $row->user
            ];
            $logs[] = $entry;
        }

        $this->sendResponse($logs);

    }


   /**  
    * 
    * Echoes sucessful JSON response
    *   
    * @return void
    * @since 1.0.0
    *
    */      
    public function sendResponse($response) {
        header('Content-Type: application/json; charset=UTF-8');        
        echo json_encode($response);
        exit();
    }

   /**  
    * 
    * Echoes error JSON response
    *   
    * @return void
    * @since 1.0.0
    *
    */      
    public function sendError($status = 400, $th = null, $settings = [] ) {
       
        header('Content-Type: application/json; charset=UTF-8');
        switch ($status) {
            case 500:
                header("HTTP/1.1 500 Internal Server Error'");
                break;
            case 400:
                header("HTTP/1.1 400 Bad Request");
                break;
            case 404:
                header("HTTP/1.1 404 Not Found");
                break;                    
            case 403:
                header("HTTP/1.1 403 Forbidden");
                break;
            default:
                # code...
                break;
        }
        if($th != null) {
            echo json_encode([
                'message' => $th->getMessage(),
                'code' => $status,
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTrace(),
                'settings' => $settings
            ]);
        }
        die();

    }


    /**
     * Duplicate of escape method from ExternalModules class, since it has been added in v13.1.2 and might not be supported
     * on most insitutions
     * 
     * @since 1.0.0
     */
	public static function escape($value){
		$type = gettype($value);

		/**
		 * The unnecessary casting on these first few types exists solely to inform psalm and avoid warnings.
		 */
		if($type === 'boolean'){
			return (bool) $value;
		}
		else if($type === 'integer'){
			return (int) $value;
		}
		else if($type === 'double'){
			return (float) $value;
		}
		else if($type === 'array'){
			$newValue = [];
			foreach($value as $key=>$subValue){
				$key = static::escape($key);
				$subValue = static::escape($subValue);
				$newValue[$key] = $subValue;
			}

			return $newValue;
		}
		else if($type === 'NULL'){
			return null;
		}
		else{
			/**
			* Handle strings, resources, and custom objects (via the __toString() method. 
			* Apart from escaping, this produces that same behavior as if the $value was echoed or appended via the "." operator.
			*/
			return htmlspecialchars(''.$value, ENT_QUOTES);
		}
	}    
    
}