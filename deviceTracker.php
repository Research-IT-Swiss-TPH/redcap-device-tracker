<?php

namespace STPH\deviceTracker;

use Exception;
use REDCap;

//  used for development
if( file_exists("vendor/autoload.php") ){
    require 'vendor/autoload.php';
}

//  require tracking class
if (!class_exists("Tracking")) require_once("classes/tracking.class.php");

// Declare your module class, which must extend AbstractExternalModule 
class deviceTracker extends \ExternalModules\AbstractExternalModule {    

    private $devices_project_id;
    private $devices_event_id;

    private $tracking_record;
    private $tracking_page;
    private $tracking_fields;
    private $tracking_event;

    /**
    * Constructs the class
    *
    */
    public function __construct()
    {
        parent::__construct();

        //  Setup Project Context if pid is available through request and constant is not yet defined
        if(isset($_GET["pid"]) && !defined('PROJECT_ID')) {
            define('PROJECT_ID', $this->escape($_GET["pid"]));
        }

        //  Check if in project context, otherwise this will break during module enable/disable
        if(defined('PROJECT_ID')) {
            $this->devices_project_id = $this->getSystemSetting("devices-project");
            $this->devices_event_id = (new \Project( $this->devices_project_id ))->firstEventId;            
        }
    }


   /**
    * Hooks Device Tracker module to redcap_every_page_top
    *
    * @since 1.0.0
    */
    public function redcap_every_page_top($project_id = null) {
       
        if($this->isValidTrackingPage()) {
            $this->renderTrackingInterface();
        }

    }

    /**
     * Check if is a valid page for tracking and sets parameters as variables
     * 
     */
    private function isValidTrackingPage() {
        $isValidDataEntryPage = false;
        $isValidFormWithTracking = false;

        //  Check if valid Data Entry page
        if($this->isPage('DataEntry/index.php') && isset( $_GET['id']) && defined('USERID')) {

            $isValidDataEntryPage = true;
            $all_tracking_fields = $this->getAllTrackingFields();

            //  Check if is a valid Form Page with Tracking field
            if($_GET["page"] && in_array($_GET["page"], array_keys($all_tracking_fields))) {

                $isValidFormWithTracking = true;

                //  Set tracking variables
                $this->tracking_record = $this->escape($_GET["id"]);
                $this->tracking_page = $this->escape($_GET["page"]);
                $this->tracking_event = $this->escape($_GET["event_id"]);
                $this->tracking_fields = $all_tracking_fields[$this->tracking_page];

            }
        }

        return $isValidDataEntryPage && $isValidFormWithTracking;       
    }

    /**
     * Render HTML and Javascript to insert Vue Instance
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


    /**
     * Get data from backend to pass into vue instance(s)
     * 
     * 
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
            "name"  => $this->tracking_page,
            "event_id"   => $this->tracking_event
            
        ];

        $data["fields"] = $this->tracking_fields;

        return json_encode($data);

    }


    /**
     * PUBLIC METHODS used via AJAX/AXIOS
     * 
     * 
     */


    /**
     * Get Tracking Data from session_tracking_id
     * 
     * 
     * @since 1.0.0
     */
    public function getTrackingData($record_id, $field_id){

        $params = [
            'project_id'    => PROJECT_ID, 
            'records' => $record_id,
            'fields' => $field_id,
            'return_format' => 'json'
        ];

        $data_t = json_decode( REDCap::getData($params), true);

        if(count($data_t) === 0) {
            $this->sendResponse([]);
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

        $data = reset(json_decode($json));

        $response = $data;

        $this->sendResponse($response); 
    }     


    /**
     * Get available devices
     * Get a list of all available devices by filtering through session_device_state
     * 
     * @since 1.0.0
     */
    public function getAvailableDevices(array $types=[]) :array {

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
     * Request Handler Methods
     * Can be accessed via AJAX/AXIOS
     * 
     * 
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
     * 
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
        
        try {
            //  Begin database transaction
            $this->beginDbTx();

            //  Retrieve current device instance info (assuming that current instance is last instance)
            list($currentInstanceId, $currentInstanceState) = $this->getCurrentDeviceInfo($tracking->device);
            //  Do checks (different for every action)
            if($tracking->mode == 'assign-device') {
                if( ($currentInstanceId == 0 && $currentInstanceState != NULL) || $currentInstanceId != 0 && $currentInstanceState != 0) {
                    throw new Exception ("Invalid current instance state. Expected 0, found: " . $currentInstanceId);
                }
            }

            if($tracking->mode == 'return-device') {
                if( $currentInstanceState != 1) {
                    throw new Exception ("Invalid current instance state. Expected 1, found: " . $currentInstanceId);
                }
            }

            if($tracking->mode == 'reset-device') {
                if( $currentInstanceState != 2) {
                    throw new Exception ("Invalid current instance state. Expected 2, found: " . $currentInstanceId);
                }                
            }

            //  Retrieve tracking ID
            $currentTrackingId = $this->getCurrentTrackingId($tracking->project, $tracking->field, $tracking->owner);

            //  Do checks (different for every action)
            if($tracking->mode == 'assign-device') {
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
                'data' => $tracking->getDataDevices($currentInstanceId, $this->devices_event_id)
            ];
            //  Perform actual saving
            $saved_d = REDCap::saveData($params_d);

            //  Throw error if there were errors during save
            if(count($saved_d["errors"]) !== 0) {
                throw new Exception(implode(", ", $saved_d["errors"]));
            }
            
            /**
             * 2. Save data to tracking project
             * 
             */

            //  to do: additional fields that are being piped (different for every action)
            //  scenario: user can add additional fields over module settings (text, date) which will be rendered
            //  into action-modal. Any entry will be retrieved through ajax (no strict validation) and piped into
            //  relevant tracking project fields. Mechanism to save data will first take ajax params, fetch settings
            //  match them and finally run a getAdditionalFieldData() method 
            //  also use flag: $hasExtra = false;

            //  Check if has extra fields
            $hasExtra = !empty($tracking->extra);
            $dataValues_t = [];
            
            if($hasExtra) {

                //  Validate
                //$trackings = $this->getTrackingForField($tracking->field);

                // Validate extra fields with tracking field instructions
                // Validate extra fields with actual fields in form

                foreach ($tracking->extra as $key => $value) {
                    //  push values to fields and add to $dataValues_t
                    $dataValues_t[$key] = $value;
                }
                
            }

            if($tracking->mode == 'assign-device') {
                //  add additional values here...
                $dataValues_t[$tracking->field] = $tracking->id;
            }

            //  Perform actual save only if we have data for the specific action to be saved
            if($tracking->mode == 'assign-device' || $tracking->mode != 'assign-device' && $hasExtra) {
                $data_t = [ $tracking->owner => [$tracking->event => $dataValues_t ] ];
                
                $params_t = [
                    'project_id' => $tracking->project,
                    'data' => $data_t
                ];
                $saved_t = REDCap::saveData($params_t);

                //  Check if there were any errors during save and throw error
                if(count($saved_t["errors"]) !== 0) {
                    throw new Exception(implode(", ", $saved_t["errors"]));
                }                
            }

            $logId = $this->log(
                "tracking-action",
                [
                    "action"=> $tracking->mode,
                    "field"=> $tracking->field,
                    "value"=> $tracking->device,
                    "record" => $tracking->owner,
                    "instance" => $currentInstanceId,
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
            $this->sendError(500, $th->getMessage());
        }

        $response = array(
            "tracking" => $tracking,
            "devices_project" => $this->devices_project_id,
            "saved_devices" => $saved_d,
            "saved_tracking" => $saved_t ?? [],
            "log_id" => $logId,
            "extra" => $tracking->extra
        );

        $this->sendResponse($response);         
    }

    /**
     * Provide logs for monitoring
     * Limits to project context
     * 
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



    /**
     * HELPERS for public functions
     * 
     * 
     */

    /**
     * Get Tracking for Field
     * (Helper)
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
     * 
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

    /**
     * Get current device info
     * Returns current device instance number and state
     * 
     * @since 1.0.0
     */
    private function getCurrentDeviceInfo($device_id) {
        $data = REDCap::getData(array(
            'return_format' => 'array', 
            'project_id' => $this->devices_project_id,
            'records' => $device_id,
            'fields' => ["session_device_state"],
            'exportDataAccessGroups' => true
        ));

        //  Get last instance and assume it is current instance
        $currentInstanceId = count((array)$data[$device_id]['repeat_instances'][$this->devices_event_id]["sessions"]);
        $currentInstanceState  = $data[$device_id]["repeat_instances"][$this->devices_event_id]["sessions"][$currentInstanceId]["session_device_state"];

        return array($currentInstanceId, $currentInstanceState);
    }

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
     */
    private function getDeviceTypesForField(string $field) :array {
        $trackings = $this->getSubSettings('trackings');
        foreach ($trackings as $key => $settings) {
            if($settings['tracking-field'] == $field) {
                if(!empty($settings["device-types"])) {
                    return explode(",", trim($settings["device-types"]) );
                } 
            }
        }
        return [];
    }


    /**
     * Get Logs for a tracking/field pair
     * 
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
    private function sendResponse($response) {
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
    private function sendError($status = 400, $msg = "") {
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
        if($msg !="") {
            echo json_encode(array("error" => $msg));
        } 
        die();
    }

    /**
     * Duplicate of escape method from ExternalModules class, since it has been added in v13.1.2 and might not be supported
     * on most insitutions
     * 
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