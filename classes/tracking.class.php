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

}