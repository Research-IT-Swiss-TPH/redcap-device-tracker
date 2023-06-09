<?php
/** @var \STPH\deviceTracker\deviceTracker $module */
namespace STPH\deviceTracker;
use Exception;

//  DEFAULT MODES
const DEFAULT_MODES = array('assign', 'return', 'reset');

//  Require tracking class
if (!class_exists("Tracking")) require_once("classes/tracking.class.php");

//  Tracking Handler
if( ($_REQUEST['action'] == 'handle-tracking') ) {
   
    if(!isset($_GET["device_id"]) || !isset($_GET["field_id"]) || !isset($_GET["owner_id"]) || !isset($_GET["mode"])) {
        header("HTTP/1.1 400 Bad Request");
        header('Content-Type: application/json; charset=UTF-8');    
        die("Invalid parameters."); 
    }

    if( !in_array( $module->escape($_GET["mode"]), DEFAULT_MODES)) {
        header("HTTP/1.1 400 Bad Request");
        header('Content-Type: application/json; charset=UTF-8');    
        die("Invalid tracking mode."); 
    }
    //  Will be escaped within class Tracking
    //  since otherwise it breaks json_encode($_GET["extra"])
    $module->handleTracking(new Tracking($_GET));

}

//  General Error handler
else {
    header("HTTP/1.1 400 Bad Request");
    header('Content-Type: application/json; charset=UTF-8');    
    die("The action does not exist.");
}