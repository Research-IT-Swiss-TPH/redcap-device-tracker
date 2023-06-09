<?php namespace STPH\deviceTracker;

use Exception;

class Tracking {

    const DEFAULT_MODES = array('assign', 'return', 'reset');

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

    public function __construct($data) {

        if(!empty($data)) {

            if( !in_array( $data->mode, self::DEFAULT_MODES)) {
                throw new Exception("Invalid Tracking Mode");
            }

            $this->project  = PROJECT_ID;
            $this->mode = $data->mode;
            $this->event = $data->event_id;
            $this->owner = $data->owner_id;
            $this->field = $data->field_id;
            $this->device = $data->device_id;
            $this->user = $data->user_id;

            //  Replace this in future with https://github.com/ramsey/uuid
            $this->id       = hash('sha256', $this->owner . "." . $this->device . "." . $this->project);
            $this->timestamp = date("Y-m-d H:i:s");

            $this->extra = [];
            if(!empty($data->extra) && is_array(json_decode($data->extra, true))) {
                $this->extra = json_decode($data->extra, true);
            }
        } else {
            throw new Exception("Invalid tracking data.");
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
    public function getDataDevices($devices_instance, $devices_event) {
        if($this->mode == "assign") {
            $values = [
                "session_tracking_id" => $this->id,
                "session_owner_id" => $this->owner,
                "session_event_id" => $this->event,
                "session_project_id" => $this->project,
                "session_device_state" => 1,
                "session_assign_date" => $this->timestamp
            ];
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
                    $devices_event => [
                        "sessions" => [
                            $devices_instance => $values
                        ]
                    ]
                ]
            ]
        ];

        return $data;
    }

}