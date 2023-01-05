<?php
/** @var \STPH\deviceTracker\deviceTracker $module */
namespace STPH\deviceTracker;

//dump($module->trackings);
//dump($module->getAvailableDevices([1,2,3]));

$pseudoSql = "select log_id, message, date,  user, action, field, record, instance where message = ?";
$parameters = ['tracking-action']; 

$result = $module->queryLogs($pseudoSql, $parameters);
$logs = [];
while($row = $result->fetch_object()){
    $logs[] = $row;
}
?>
<table class="table">
    <thead>
        <tr>
            <th scope="col">Log Id</th>
            <th scope="col">Date</th>
            <th scope="col">Message</th>
            <th scope="col">Action</th>
            <th scope="col">User</th>
            <th scope="col">Field</th>
            <th scope="col">Owner</th>
            <th scope="col">Session Id</th>
        </tr>
    </thead>
  <tbody>
  <?php
foreach ($logs as $key => $log) {

    echo '<tr scope="row">';
    echo "<td>".$log->log_id."</td>";
    echo "<td>".$log->date."</td>";
    echo "<td>".$log->message."</td>";
    echo "<td>".$log->action."</td>";
    echo "<td>".$log->user."</td>";
    echo "<td>".$log->field."</td>";
    echo "<td>".$log->record."</td>";
    echo "<td>".$log->instance."</td>";
    echo "</tr>";
}

?>
  </tbody>
</table>
<?php
// $fieldName = "device_type";
// $pid = 15;
// $choiceLabels = $module->getChoiceLabels($fieldName, $pid);

// dump($choiceLabels);
