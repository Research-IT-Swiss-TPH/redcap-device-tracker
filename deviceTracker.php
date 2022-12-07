<?php

namespace STPH\deviceTracker;

use REDCap;
use ExternalModules\ExternalModules;
require __DIR__ . '/vendor/autoload.php';


// Declare your module class, which must extend AbstractExternalModule 
class deviceTracker extends \ExternalModules\AbstractExternalModule {    

    private int $id;

    /**
    * Constructs the class
    *
    */
    public function __construct()
    {
        parent::__construct();
        //$this->trackings = $this->getTrackings();
        $this->id = $_GET["id"] ?? 0;
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

            //  Check if is form page and has tracking fields
            if($_GET["page"] && in_array($_GET["page"], array_keys($this->trackings))) {
                

                //  Include Javascript             
                $this->includeJavascript($this->trackings[$_GET["page"]], $_GET["id"]);
            }

        }

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

    public function getAvailableDevices($types=[]) {

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

    private function getFieldStates($fields) {

         $response = [];

         // 0. Loop through tracking_fields
         foreach ($fields as $key => $field) {
            
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

            //  Switch case trough calculated device_state and return field state
            switch ($device_state) {
                case 0:
                    $response[$field] = "reset";  //  reset
                break;                    
                case 1:
                    $response[$field] = "assigned";  //  assigned
                    break;
                case 2:
                    $response[$field] = "returned";  //  returned
                    break;
                default:
                    $response[$field] = $device_state;  //  undefined
                    break;
            }
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
                        //  Insert vue target
                        var target = $('tr#'+field_name+'-tr').find('input');
                        var wrapper = $('#STPH_DT_WRAPPER_' + field_name);
                        target.parent().prepend(wrapper);
                        wrapper.show();
                        console.log(field_name);

                    });
                })
            });
        </script>
        <!-- backend data helpers -->
        <script>
            const stph_dt_getTrackingFieldsWithStateFromBackend = function() {
                    return <?= json_encode($this->getFieldStates($tracking_fields, $record_id)) ?>
            }
        </script>
        <!-- actual vue scripts -->
        <script src="<?= $this->getUrl('./dist/render.js') ?>"></script>
        <?php
    }
    
}