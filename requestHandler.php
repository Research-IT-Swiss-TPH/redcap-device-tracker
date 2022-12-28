<?php
/** @var \STPH\deviceTracker\deviceTracker $module */
namespace STPH\deviceTracker;

if ($_REQUEST['action'] == 'validate-device') {
    if(!isset($_GET["device_id"]) || !isset($_GET["tracking_field"])) {
        header("HTTP/1.1 400 Bad Request");
        header('Content-Type: application/json; charset=UTF-8');    
        die("Invalid parameters."); 
    }
    $module->validateDevice($_GET["device_id"], $_GET["tracking_field"]);
} 

if ($_REQUEST['action'] == 'assign-device') {
    if(!isset($_GET["device_id"]) || !isset($_GET["tracking_field"]) || !isset($_GET["owner_id"])) {
        header("HTTP/1.1 400 Bad Request");
        header('Content-Type: application/json; charset=UTF-8');    
        die("Invalid parameters."); 
    }
    $tracking = (object) [
        "project"=> $_GET["pid"],
        "event"  => $_GET["event_id"],
        "owner"  => $_GET["owner_id"],
        "field"  => $_GET["field_id"],
        "device" => $_GET["device_id"],
        "extra"  => $_GET["extra"]
    ];

    $module->assignDevice($tracking);
}


else {
    header("HTTP/1.1 400 Bad Request");
    header('Content-Type: application/json; charset=UTF-8');    
    die("The action does not exist.");
}