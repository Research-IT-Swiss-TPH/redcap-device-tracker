<?php
/** @var \STPH\deviceTracker\deviceTracker $module */
namespace STPH\deviceTracker;

?>

<div id="STPH_DT_MONITOR"></div>

<script>
    const stph_dt_getBaseUrlFromBackend = function () {
        return '<?= $module->getUrl("requestHandler.php") ?>'
    }
    const stph_dt_getPageInfoFromBackend = function() {
        return <?= json_encode( array("super_user" => $module->isSuperUser(), "project_id"=>$module->getProjectId())) ?>
    }
</script>
<!-- Insert Vue.js after DOM -->
<script src="<?= $module->getUrl('./dist/appMonitor.js') ?>"></script>
