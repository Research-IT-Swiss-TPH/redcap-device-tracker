<?php
namespace STPH\deviceTracker;

use Exception;

class Tracking {

    public Int $project;
    public Int $event;
    public String $owner;
    public String $field;
    public String $device;
    public Array $extra;
    public String $user;

    public function __construct($request = []) {
        if(!empty($request) && is_array($request)) {

            $this->project  = htmlentities($request["pid"], ENT_QUOTES, 'UTF-8');
            $this->event    = htmlentities($request["event_id"], ENT_QUOTES, 'UTF-8');
            $this->owner    = htmlentities($request["owner_id"], ENT_QUOTES, 'UTF-8');
            $this->field    = htmlentities($request["field_id"], ENT_QUOTES, 'UTF-8');
            $this->device   = htmlentities($request["device_id"], ENT_QUOTES, 'UTF-8');
            $this->user     = htmlentities($request["user_id"], ENT_QUOTES, 'UTF-8');
            $this->extra = [];

            if(!empty($request["extra"]) && is_array(json_decode($request["extra"], true))) {
                $this->extra = json_decode($request["extra"], true);
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
                "session_assign_date" => date("Y-m-d")
            ];
            $instance++;
        }

        if($action == "return-device") {
            $values = [
                "session_device_state" => 2,
                "session_return_date" => date("Y-m-d")
            ];
        }

        if($action == "reset-device") {
            $values = [
                "session_device_state" => 0,
                "session_reset_date" => date("Y-m-d")
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