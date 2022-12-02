<?php

namespace STPH\deviceTracker;

// Declare your module class, which must extend AbstractExternalModule 
class deviceTracker extends \ExternalModules\AbstractExternalModule {    

    
    /**
    * Constructs the class
    *
    */
    public function __construct()
    {
        parent::__construct();
    }


   /**
    * Hooks Device Tracker module to redcap_every_page_top
    *
    * @since 1.0.0
    */
    public function redcap_every_page_top($project_id = null) {

        

    }
    
}