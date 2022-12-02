<?php

namespace STPH\deviceTracker;

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