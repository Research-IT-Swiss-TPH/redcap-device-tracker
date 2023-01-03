<?php
/** @var \STPH\deviceTracker\deviceTracker $module */
namespace STPH\deviceTracker;

if (!class_exists("Tracking")) require_once("classes/tracking.class.php");

if ($_REQUEST['action'] == 'validate-device') {
    if(!isset($_GET["device_id"]) || !isset($_GET["tracking_field"])) {
        header("HTTP/1.1 400 Bad Request");
        header('Content-Type: application/json; charset=UTF-8');    
        die("Invalid parameters."); 
    }
    $module->validateDevice($_GET["device_id"], $_GET["tracking_field"]);
} 

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

else {
    header("HTTP/1.1 400 Bad Request");
    header('Content-Type: application/json; charset=UTF-8');    
    die("The action does not exist.");
}