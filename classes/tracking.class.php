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

    public function __construct($request = []) {
        if(!empty($request) && is_array($request)) {

            $this->project = $request["pid"];
            $this->event = $request["event_id"];
            $this->owner = $request["owner_id"];
            $this->field = $request["field_id"];
            $this->device = $request["device_id"];

            if(!empty($request["extra"]) && is_array(json_decode($_GET["extra"]))) {
                $this->extra = json_decode($request["extra"]);
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
        if($action == "assign") {
            $values = [
                "session_owner_id" => $this->owner,
                "session_project_id" => $this->project,
                "session_device_state" => 1,
                "session_assign_date" => date("Y-m-d")
            ];
            $instance++;
        }

        if($action == "return") {
            $values = [
                "session_device_state" => 2,
                "session_return_date" => date("Y-m-d")
            ];
        }

        if($action == "reset") {
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