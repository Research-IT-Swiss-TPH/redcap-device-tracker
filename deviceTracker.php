<?php

namespace STPH\deviceTracker;

use REDCap;
use ExternalModules\ExternalModules;
require __DIR__ . '/vendor/autoload.php';


// Declare your module class, which must extend AbstractExternalModule 
class deviceTracker extends \ExternalModules\AbstractExternalModule {    


    /**
    * Constructs the class
    *
    */
    public function __construct()
    {
        parent::__construct();
        //$this->trackings = $this->getTrackings();
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
                
                //dump($this->trackings[$_GET["page"]]);
                //  Include Javascript
                $data = (object) [];
                $data->trackings = json_encode($this->trackings[$_GET["page"]]);                
                $this->includeJavascript($data);
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
            'fields'=>array('device_type','record_id', 'session_device_state')
        );

        return REDCap::getData($params);
    }


    /**
     * Include Javascript
     * 
     * @since 1.0.0
     */
    private function includeJavascript($data): void {
        ?>
        <script src="<?php print $this->getUrl('js/main.js'); ?>"></script>
        <script>
            $(function() {
                $(document).ready(function() {
                    STPH_deviceTracker.trackings = <?= $data->trackings ?>;
                    STPH_deviceTracker.init();
                })
            });
        </script>
        <?php
    }
    
}