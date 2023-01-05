<?php
/** @var \STPH\deviceTracker\deviceTracker $module */
namespace STPH\deviceTracker;

//  Require tracking class
if (!class_exists("Tracking")) require_once("classes/tracking.class.php");

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

//  Validation handler
if ($_REQUEST['action'] == 'validate-device') {
    if(!isset($_GET["device_id"]) || !isset($_GET["tracking_field"])) {
        header("HTTP/1.1 400 Bad Request");
        header('Content-Type: application/json; charset=UTF-8');    
        die("Invalid parameters."); 
    }
    $module->validateDevice(
        htmlentities($_GET["device_id"], ENT_QUOTES), 
        htmlentities($_GET["tracking_field"], ENT_QUOTES)
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
        htmlentities($_GET["owner_id"], ENT_QUOTES), 
        htmlentities($_GET["tracking_field"], ENT_QUOTES)
    );
}

//  Error handler
else {
    header("HTTP/1.1 400 Bad Request");
    header('Content-Type: application/json; charset=UTF-8');    
    die("The action does not exist.");
}