<?php
namespace STPH\deviceTracker;

use Exception;
use ExternalModules\ExternalModules;

class Tracking {

    public String $mode;
    public String $id;
    public Int $project;
    public Int $event;
    public String $owner;
    public String $field;
    public String $device;
    public Array $extra;
    public String $user;
    public String $timestamp;

    public function __construct($request = []) {

        if(!empty($request) && is_array($request) ) {

            
            $this->project  = PROJECT_ID;
            
            $this->mode     = $request["mode"];
            $this->event    = $request["event_id"];
            $this->owner    = $request["owner_id"];
            $this->field    = $request["field_id"];
            $this->device   = $request["device_id"];
            $this->user     = $request["user_id"];

            //  Replace this in future with https://github.com/ramsey/uuid
            $this->id       = hash('sha256', $this->owner . "." . $this->device . "." . $this->project);
            $this->timestamp = date("Y-m-d H:i:s");

            $this->extra = [];
            if(!empty($request["extra"]) && is_array(json_decode($request["extra"], true))) {
                $this->extra = json_decode($request["extra"], true);
            }            
            

        } else {
            throw new Exception("Invalid Request");
        }
    }


    /**
     * Needed for sync
     * 
     */
    public function getDeviceStateByMode() {
        if($this->mode == "assign" ) {
            return 1;
        }
        if($this->mode == "return" ) {
            return 2;
        }
        if($this->mode == "reset" ) {
            return 0;
        }        
    }

    /**
     * Prepare data to save into devices project
     * 
     * 
     */
    public function getDataDevices($instance, $event) {
        if($this->mode == "assign") {
            $values = [
                "session_tracking_id" => $this->id,
                "session_owner_id" => $this->owner,
                "session_project_id" => $this->project,
                "session_device_state" => 1,
                "session_assign_date" => $this->timestamp
            ];
            //$instance++;
        }

        if($this->mode == "return") {
            $values = [
                "session_device_state" => 2,
                "session_return_date" => $this->timestamp
            ];
        }

        if($this->mode == "reset") {
            $values = [
                "session_device_state" => 0,
                "session_reset_date" => $this->timestamp
            ];
        }

        //  Format array for REDCap::save()
        $data = [
            $this->device => [
                "repeat_instances" => [
                    $event => [
                        "sessions" => [
                            $instance => $values
                        ]
                    ]
                ]
            ]
        ];

        return $data;
    }

}