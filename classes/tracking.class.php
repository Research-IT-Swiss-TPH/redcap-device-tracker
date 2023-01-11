<?php
namespace STPH\deviceTracker;

use Exception;
use ExternalModules\ExternalModules;

class Tracking {

    public Int $project;
    public Int $event;
    public String $owner;
    public String $field;
    public String $device;
    public Array $extra;
    public String $user;
    public String $timestamp;

    public function __construct($request = []) {
        if(!empty($request) && is_array($request)) {

            $this->project  = PROJECT_ID;
            $this->event    = htmlspecialchars($request["event_id"]);
            $this->owner    = htmlspecialchars($request["owner_id"]);
            $this->field    = htmlspecialchars($request["field_id"]);
            $this->device   = htmlspecialchars($request["device_id"]);
            $this->user     = htmlspecialchars($request["user_id"]);
            $this->extra = [];
            $this->timestamp = date("Y-m-d H:i:s");

            if(!empty($request["extra"]) && is_array(json_decode($request["extra"], true))) {
                $this->extra = array_map("htmlspecialchars", json_decode($request["extra"], true));
            }
        } else {
            throw new Exception("Invalid Request");
        }
    }

    /**
     * Prepare data to save into devices project
     * 
     * 
     */
    public function getDataDevices($action, $instance, $event) {
        if($action == "assign-device") {
            $values = [
                "session_owner_id" => $this->owner,
                "session_project_id" => $this->project,
                "session_device_state" => 1,
                "session_assign_date" => $this->timestamp
            ];
            $instance++;
        }

        if($action == "return-device") {
            $values = [
                "session_device_state" => 2,
                "session_return_date" => $this->timestamp
            ];
        }

        if($action == "reset-device") {
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