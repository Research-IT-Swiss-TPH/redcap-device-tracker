<?php
/** @var \STPH\deviceTracker\deviceTracker $module */
namespace STPH\deviceTracker;


//  DEFAULT MODES
const DEFAULT_MODES = array('assign-device', 'return-device', 'reset-device');

//  Require tracking class
if (!class_exists("Tracking")) require_once("classes/tracking.class.php");

if($_REQUEST['action'] == 'get-tracking-data') {
    if( !isset($_GET["record_id"]) || !isset($_GET["field_id"]) ) {
        header("HTTP/1.1 400 Bad Request");
        header('Content-Type: application/json; charset=UTF-8');    
        die("Invalid parameters."); 
    }

    $module->getTrackingData(
        $module->escape($_GET["record_id"]), 
        $module->escape($_GET["field_id"])
    );

}

//  Validation handler
if ($_REQUEST['action'] == 'validate-device') {
    if(!isset($_GET["device_id"]) || !isset($_GET["tracking_field"])) {
        header("HTTP/1.1 400 Bad Request");
        header('Content-Type: application/json; charset=UTF-8');    
        die("Invalid parameters."); 
    }
    $module->validateDevice(
        $module->escape($_GET["device_id"]),
        $module->escape($_GET["tracking_field"])
    );
} 

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

    try {        
        $tracking = new Tracking($_GET);
        $module->handleTracking($tracking);

    } catch(\Throwable $th ) {
        header("HTTP/1.1 400 Bad Request");
        header('Content-Type: application/json; charset=UTF-8');
        die($th->getMessage());
    }    

}

//  Action handlers
if ($_REQUEST['action'] == 'assign-device' || $_REQUEST['action'] == 'return-device' || $_REQUEST['action'] == 'reset-device') {

    if(!isset($_GET["device_id"]) || !isset($_GET["field_id"]) || !isset($_GET["owner_id"])) {
        header("HTTP/1.1 400 Bad Request");
        header('Content-Type: application/json; charset=UTF-8');    
        die("Invalid parameters."); 
    }

    try {
        $action = $_REQUEST['action'];
        $tracking = new Tracking($_GET);
        $module->handleTracking($action, $tracking);
    } catch(\Throwable $th ) {
        header("HTTP/1.1 400 Bad Request");
        header('Content-Type: application/json; charset=UTF-8');
        die($th->getMessage());
    }
}

if($_REQUEST['action'] == 'get-additional-fields') {
    $module->getAdditionalFields(
        $module->escape($_GET["mode"]),
        $module->escape($_GET["field_id"])
    );
}

//  Log Handler
if ($_REQUEST['action'] == 'get-tracking-logs')  {
    if(!isset($_GET["owner_id"]) || !isset($_GET["tracking_field"])) {
        header("HTTP/1.1 400 Bad Request");
        header('Content-Type: application/json; charset=UTF-8');    
        die("Invalid parameters."); 
    }
    $module->getTrackingLogs(
        $module->escape($_GET["owner_id"]), 
        $module->escape($_GET["tracking_field"])
    );
}

if ($_REQUEST['action'] == 'provide-logs')  {
    $module->provideLogs();
}

//  Error handler
else {
    header("HTTP/1.1 400 Bad Request");
    header('Content-Type: application/json; charset=UTF-8');    
    die("The action does not exist.");
}