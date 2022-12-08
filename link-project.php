<?php
/** @var \STPH\deviceTracker\deviceTracker $module */
namespace STPH\deviceTracker;

//dump($module->trackings);
//dump($module->getAvailableDevices([1,2,3]));

$fieldName = "device_type";
$pid = 15;
$choiceLabels = $module->getChoiceLabels($fieldName, $pid);

dump($choiceLabels);
