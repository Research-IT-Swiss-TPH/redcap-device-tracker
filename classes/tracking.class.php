<?php namespace STPH\deviceTracker;

use Exception;
use REDCap;

class Tracking {

    public String $action;
    public String $id;
    public Int $project;
    public Int $event;
    public String $owner;
    public String $field;
    public String $device;
    public Array $extra;
    public String $user;
    public String $timestamp;

    public function __construct($payload = []) {

        if(!empty($payload) && is_array($payload) ) {
           
            $this->project  = PROJECT_ID;
            
            $this->action = $payload["action"];
            $this->event = $payload["event_id"];
            $this->owner = $payload["owner_id"];
            $this->field = $payload["field_id"];
            $this->device = $payload["device_id"];
            $this->user = $payload["user_id"];

            $this->id       = hash('sha256', $this->owner . "." . $this->device . "." . $this->project);
            $this->timestamp = date("Y-m-d H:i:s");

            $this->extra = [];
            if(!empty($payload["extra"]) && is_array(json_decode($payload["extra"], true))) {
                $this->extra = json_decode($payload["extra"], true);
            }            
            

        } else {
            throw new Exception("Invalid Payload");
        }
    }
    
    public function validateSessionData($lastSessionId, $lastSessionState, $isLastSessionUntracked) {
        //  Do checks and determine ID of session to bes saved (different for every action)
        if($this->action == 'assign') {
            if( ($lastSessionState != "") && ($lastSessionState != 0) ) {
                throw new Exception ("Invalid current instance state. Expected 0, found: " . $lastSessionState);
            }

            if( ($lastSessionState != "") && $isLastSessionUntracked) {
                $saveSessionId = $lastSessionId;
            } else {
                $saveSessionId = $lastSessionId + 1;
            }
        }

        if($this->action == 'return') {
            if( $lastSessionState != 1) {
                throw new Exception ("Invalid current instance state. Expected 1, found: " . $lastSessionState);
            }
            $saveSessionId = $lastSessionId;
        }

        if($this->action == 'reset') {
            if( $lastSessionState != 2) {
                throw new Exception ("Invalid current instance state. Expected 2, found: " . $lastSessionState);
            }
            $saveSessionId = $lastSessionId;
        }
        return $saveSessionId;
    }

    public function validateTrackingId($currentTrackingId) {
            //  Do checks (different for every action)
            if($this->action == 'assign') {
                if(!empty($currentTrackingId)) {
                    throw new Exception("Invalid current tracking field. Expected NULL found: " . $currentTrackingId);
                }
            } else {
                if($currentTrackingId != $this->id) {
                    throw new Exception("Invalid current tracking field. Expected ".$this->id." found: " . $currentTrackingId);
                } 
            }
    }

    public function saveDataToDevices($devices_project_id, $devices_event_id, $saveSessionId) {

        if($this->action == "assign") {
            $values = [
                "session_tracking_id" => $this->id,
                "session_owner_id" => $this->owner,
                "session_event_id" => $this->event,
                "session_project_id" => $this->project,
                "session_device_state" => 1,
                "session_assign_date" => $this->timestamp
            ];
        }

        if($this->action == "return") {
            $values = [
                "session_device_state" => 2,
                "session_return_date" => $this->timestamp
            ];
        }

        if($this->action == "reset") {
            $values = [
                "session_device_state" => 0,
                "session_reset_date" => $this->timestamp
            ];
        }

        //  Format array for REDCap::save()
        $data = [
            $this->device => [
                "repeat_instances" => [
                    $devices_event_id => [
                        "sessions" => [
                            $saveSessionId => $values
                        ]
                    ]
                ]
            ]
        ];        
        
        $params_d = [
            'project_id' => $devices_project_id,
            'data' => $data
        ];

        //  Perform actual saving
        $saved_d = REDCap::saveData($params_d);       
        return $saved_d;
    }

    public function saveDataToTracking($tracking_settings) {

        $dataValues_t = [];
        
        //  Save tracking id into tracking project
        if($this->action == 'assign') {
            $dataValues_t[$this->field] = $this->id;
        }

        //  Check if has extra and set if true
        $hasExtra = !empty($this->extra) && (bool) $tracking_settings["use-additional-".$this->action];
        if($hasExtra) {
            //  Add extra fields to data to be saved
            foreach ($this->extra as $key => $value) {
                //  push values to fields and add to $dataValues_t
                $dataValues_t[$key] = $value;
            }                
        }

        //  Check if sync is enabled and sync if true
        $hasSync = (bool) $tracking_settings["use-sync-data"];
        if($hasSync) {

            $sync_data = [];

            if($this->action == 'assign' && !empty($tracking_settings["sync-date-assign"])) {
                $sync_data[$tracking_settings["sync-date-assign"]] = $this->timestamp;
            }
            
            if($this->action == 'return' && !empty($tracking_settings["sync-date-return"]) ) {
                $sync_data[$tracking_settings["sync-date-return"]] = $this->timestamp;
            }

            if($this->action == 'reset' && !empty($tracking_settings["sync-date-reset"]) ) {
                $sync_data[$tracking_settings["sync-date-reset"]] = $this->timestamp;
            }                

            if( !empty($tracking_settings["sync-state"])) {
                $sync_data[$tracking_settings["sync-state"]] = $this->getDeviceStateByMode();
            }
            
            //  Add sync fields to data to be saved
            foreach ($sync_data as $key => $value) {
                //  push values to fields and add to $dataValues_t
                $dataValues_t[$key] = $value;
            }
        }
        
        //  Perform actual save only if we have data for the specific action to be saved or sync enabled
        if($this->action == 'assign' || $hasSync || $hasExtra) {

            $data_t = [ $this->owner => [$this->event => $dataValues_t ] ];
            
            $params_t = [
                'project_id' => $this->project,
                'data' => $data_t
            ];
            $saved_t = REDCap::saveData($params_t);

            return [$saved_t, $hasExtra, $sync_data];
        }        

    }

    /**
     * Needed for sync
     * 
     */
    public function getDeviceStateByMode() {
        if($this->action == "assign" ) {
            return 1;
        }
        if($this->action == "return" ) {
            return 2;
        }
        if($this->action == "reset" ) {
            return 0;
        }        
    }

}