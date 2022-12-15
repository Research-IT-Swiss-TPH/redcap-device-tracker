<?php
/** @var \STPH\deviceTracker\deviceTracker $module */
namespace STPH\deviceTracker;

if ($_REQUEST['action'] == 'validate-device-id') {
    $module->validateDevice($_POST["device_id"], $_POST["tracking_field"]);
}


else {
    header("HTTP/1.1 400 Bad Request");
    header('Content-Type: application/json; charset=UTF-8');    
    die("The action does not exist.");
}