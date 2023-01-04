<?php
/** @var \STPH\deviceTracker\deviceTracker $module */
namespace STPH\deviceTracker;

//dump($module->trackings);
//dump($module->getAvailableDevices([1,2,3]));

$pseudoSql = "select log_id, message, user, action, field, owner, instance where message = ?";
$parameters = ['Tracking Action'];

$result = $module->queryLogs($pseudoSql, $parameters);
$logs = [];
while($row = $result->fetch_assoc()){
    $logs[] = $row;
}

dump($logs);

// $fieldName = "device_type";
// $pid = 15;
// $choiceLabels = $module->getChoiceLabels($fieldName, $pid);

// dump($choiceLabels);
