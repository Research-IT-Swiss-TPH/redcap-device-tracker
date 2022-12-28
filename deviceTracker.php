<?php

namespace STPH\deviceTracker;

use Exception;
use REDCap;
use ExternalModules\ExternalModules;
require __DIR__ . '/vendor/autoload.php';


// Declare your module class, which must extend AbstractExternalModule 
class deviceTracker extends \ExternalModules\AbstractExternalModule {    

    private $devices_project_id;
    private $devices_event_id;

    /**
    * Constructs the class
    *
    */
    public function __construct()
    {
        parent::__construct();
        //  validate module settings..
        //$this->trackings = $this->getTrackings();
        $this->devices_project_id = $this->getSystemSetting("devices-project");
        $this->devices_event_id = (new \Project( $this->devices_project_id ))->firstEventId;

    }


   /**
    * Hooks Device Tracker module to redcap_every_page_top
    *
    * @since 1.0.0
    */
    public function redcap_every_page_top($project_id = null) {     

        //  Init
        $this->trackings = $this->getTrackings();

        //  Check if Data Entry Page and record id is defined
        if($this->isPage('DataEntry/index.php') && isset( $_GET['id']) && defined('USERID')) {

            // $args_test = array(
            //     'return_format' => 'array', 
            //     'project_id' => $_GET["pid"],
            //     'records' => $_GET["id"],
            //     'fields' => [],
            //     'exportDataAccessGroups' => true   
            // );
            // $data_test = REDCap::getData($args_test);
            // dump($data_test);

            //  Check if is form page and has tracking fields
            if($_GET["page"] && in_array($_GET["page"], array_keys($this->trackings))) {
                
                //  Include Javascript             
                $this->includeJavascript($this->trackings[$_GET["page"]], $_GET["id"]);
            }

        }

        //  for dev only
        // if($this->isPage('DataEntry/index.php') && $_GET["pid"] == $this->getDevicesProject()) {
        //     dump($this->getDevicesProjectFields($_GET["id"]));
        // }
    }

    /**
     * Request Handler Methods
     * Can be accessed via AJAX/AXIOS
     * 
     * 
     */
    public function validateDevice(string $device_id, string $trackingField) {

        $types = $this->getDeviceTypes($trackingField);
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

    private function getCurrentDeviceInfo($device_id) {
        $data = REDCap::getData(array(
            'return_format' => 'array', 
            'project_id' => $this->devices_project_id,
            'records' => $device_id,
            'fields' => ["session_device_state"],
            'exportDataAccessGroups' => true
        ));

        $currentInstanceId = count((array)$data[$device_id]['repeat_instances'][$this->devices_event_id]["sessions"]);
        $currentInstanceState  = $data[$device_id]["repeat_instances"][$this->devices_event_id]["sessions"][$currentInstanceId]["session_device_state"];

        return array($currentInstanceId, $currentInstanceState);
    }

    private function getCurrentTrackingInfo($pid, $field, $id) {
        $result = $this->query(
                    "SELECT value FROM redcap_data WHERE project_id = ? AND field_name = ? AND record = ?", 
                    [ $pid, $field, $id ]
                );
        return $result->fetch_object()->value;
    }

    public function assignDevice(string $device_id, string $tracking_field, string $owner_id, string $tracking_project) {

        /**
         * Sequential data saving to REDCap 
         * 
         * 1. Save data to devices project - suffix: _p
         * 2. Save data to tracking project - suffix: _t
         * 
         */

        try {

            /**
             * Start transaction in case there is an error
             * no data will be saved in any of the save procedures
             * https://www.mysqltutorial.org/mysql-transaction.aspx
             * 
             */
            $this->query("SET autocommit = 0;", []);
            $this->query("START TRANSACTION;", []);

            /**
             * 1. Save data to devices project
             * 
             */  
            
            //  Retrieve device instance info
            list($currentInstanceId, $currentInstanceState) = $this->getCurrentDeviceInfo($device_id);
            if( ($currentInstanceId == 0 && $currentInstanceState != NULL) || $currentInstanceId != 0 && $currentInstanceState != 0) {
                $this->sendError(400, "Invalid current instance state. Expected 0, found: " . $currentInstanceId);
            }

            //  Define destination field values
            $destFieldValues = [
                "session_owner_id" => $owner_id,
                "session_project_id" => $tracking_project,
                "session_device_state" => 1,
                "session_assign_date" => date("Y-m-d")
            ];

            //  to do: additional fields that are being piped
            $nextInstanceId = $currentInstanceId + 1;
            $data_p = [$device_id => ["repeat_instances" => [$this->devices_event_id => ["sessions" => [$nextInstanceId => $destFieldValues]]]]];       
            
            $params_p = [
                'project_id' => $this->devices_project_id,
                'data' => $data_p
            ];

            $saved_p = REDCap::saveData($params_p);

            //  Check if there were any errors during save and throw error
            if(count($saved_p["errors"]) !== 0) {
                throw new Exception(implode(", ", $saved_p["errors"]));
            }
        
            /**
             * 2. Save data to tracking project
             * 
             */

            //  Get tracking project data fields
            $currentTrackingValue = $this->getCurrentTrackingInfo($tracking_project, $tracking_field, $owner_id);
            if(!empty($currentTrackingValue)) {
                throw new Exception("Invalid current tracking field. Expected NULL found: " . $currentTrackingValue);
            }

            // save tracking
            $trackingProject = new \Project( $tracking_project );
            $trackingEventId = $trackingProject->firstEventId;

            $data_t = [$owner_id => [$trackingEventId => [ $tracking_field => $device_id]]];
            $params_t = [
                'project_id' => $tracking_project,
                'data' => $data_t
            ];

            $saved_t = REDCap::saveData($params_t);
            //  Check if there were any errors during save and throw error
            if(count($saved_t["errors"]) !== 0) {
                throw new Exception(implode(", ", $saved_t["errors"]));
            }

            /**
             * Commit Transaction
             * 
             */

            $this->query("COMMIT;", []);
            $this->query("SET autocommit = 1;", []);

        } catch (\Throwable $th) {
            //  Rollback and report Error
            $this->query("ROLLBACK;", []);
            $this->sendError(500, $th->getMessage());
        }

        $response = array(
            "tracking_project" => $tracking_project,
            "devices_project" => $this->devices_project_id,
            "saved_devices" => $saved_p,
            "saved_tracking" => $saved_t
        );

        $this->sendResponse($response); 
    }


    /**
     * Hooks into redcap_module_configuration_settings
     *
     * @param string $project_id
     * @param array $settings
     *
     * @since 1.0.0
     *
     */
    // function redcap_module_configuration_settings($project_id, $settings): array
    // {

    //     if ($project_id != null) {
    //         foreach ($settings as &$setting) {
    //             if ($setting["key"] == "device-types") {
    //                 $setting["name"] = '<div data-pid="' . $project_id . '" data-url="' . $this->getUrl("requestHandler.php") . '" style="padding:15px;display:inline-block;" id="api-description-wrapper">' . $this->generate_config_description($project_id) . '</div><script src=' . $this->getUrl("js/config.js") . '></script>';
    //             }
    //         }
    //     }
    //     return $settings;
    // }

    /**
     * Get Page Meta
     * 
     */
    private function getPageMeta() {
        return array(
            "project_id" => $_GET["pid"],
            "record_id"  => $_GET["id"],
            "page_name"  => $_GET["page"]
        );
    }

    /**
     * Get trackings in usefull structure
     * 
     * @since 1.0.0
     */
    private function getTrackings() {
        $trackings = [];
        foreach ($this->getSubSettings('trackings') as $key => $tracking) {
            $form = $this->getFormForField($tracking['tracking-field']);
            $field = $tracking['tracking-field'];
            $trackings[$form][] = $field;
        }
        return $trackings;
    }

    private function getDeviceTypes(string $field) :array {
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

    private function getFieldMeta($fields) {

         $response = array();

         // 0. Loop through tracking_fields
         foreach ($fields as $key => $field) {
            $fieldMeta = array();
            
            $params = [
                'project_id'=> $_GET["project_id"], 
                'records'=>$_GET["id"],
                'fields' => $field,
                'return_format' => 'json'
            ];
            $device_id = reset(json_decode(REDCap::getData($params), true))[$field];
            
            //  1. Check if we have selected a device
            if(empty($device_id)) {                
                $device_state = "no-device-selected";
            } else {

                $pk = $this->getRecordIdField($this->getSystemSetting("devices-project"));
                $params = [
                    'project_id' => $this->getSystemSetting("devices-project"),
                    'records' => [$device_id],
                    'fields'=>array($pk),
                    'return_format' => 'json'
                ];
                $device_id_valid = reset(json_decode(REDCap::getData($params), true))[$pk];

                //  2. Check if we have a valid device
                if(empty($device_id_valid)) {
                    $device_state = "device-not-found";
                } else {                    
                    $params = array(
                        'project_id' => $this->getSystemSetting("devices-project"),
                        'records' => [$device_id],
                        'fields'=>array('session_device_state'),
                        'return_format' => 'json'
                     );
                     $device_session_state = reset(json_decode(REDCap::getData($params), true))['session_device_state'];
                     if($device_session_state == null) {
                        $device_state = "no-session-created";
                     } else {
                        $device_state = $device_session_state;
                     }
                }
            }

            //  Switch case through calculated device_state and return field state
            switch ($device_state) {
                case 0:
                    $fieldMeta["state"] =  "reset";  //  reset
                break;                    
                case 1:
                    $fieldMeta["state"] =  "assigned";  //  assigned
                    break;
                case 2:
                    $fieldMeta["state"] =  "returned";  //  returned
                    break;
                default:
                    $fieldMeta["state"] =  $device_state;  //  undefined
                    break;
            }

            $fieldMeta["name"] = $field;
            $fieldMeta["device"] = $device_id_valid;
            $response[] = $fieldMeta;
            
         }
        return $response;
    }

    /**
     * Include Javascript
     * 
     * @since 1.0.0
     */
    private function includeJavascript($tracking_fields, $record_id): void {
        //  Loop through all tracking fields for each form and insert for each a wrapper into DOM,
        //  so that vue can actually mount an  there.
        foreach ($tracking_fields as $key => $field_name) {
            ?>
            <div id="STPH_DT_WRAPPER_<?= $field_name ?>" style="display: none;">
                <div id="STPH_DT_FIELD_<?= $field_name ?>"></div>            
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
                    trackings.forEach(function(field_name){

                        //  Insert module object
                        //var module = <?=$this->getJavascriptModuleObjectName()?>;

                        //  Insert vue target
                        var target = $('tr#'+field_name+'-tr').find('input');
                        var wrapper = $('#STPH_DT_WRAPPER_' + field_name);
                        target.parent().prepend(wrapper);
                        wrapper.show();
                        console.log(field_name + " prepended wrapper. Hiding.");

                    });
                })
            });
        </script>
        <!-- backend data helpers -->
        <script>
            const stph_dt_getBaseUrlFromBackend = function () {
                return '<?= $this->getUrl("requestHandler.php") ?>'
            }
            const stph_dt_getFieldMetaFromBackend = function() {
                return <?= json_encode($this->getFieldMeta($tracking_fields, $record_id)) ?>
            }

            const stph_dt_getPageMetaFromBackend = function() {
                return <?= json_encode($this->getPageMeta()) ?>
            }
        </script>
        <!-- actual vue scripts -->
        <script src="<?= $this->getUrl('./dist/render.js') ?>"></script>
        <?php
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
    
}