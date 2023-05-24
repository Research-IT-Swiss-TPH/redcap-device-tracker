<?php
/** @var \STPH\deviceTracker\deviceTracker $module */
namespace STPH\deviceTracker;

?>

<div id="STPH_DT_MONITOR"></div>

<script type='text/javascript'>
    const stph_dt_getBaseUrlFromBackend = function () {
        return '<?= $module->getUrl("requestHandler.php") ?>'
    }
    const stph_dt_getConfigFromBackend = function() {
        return <?= json_encode($module->getConfig()) ?>
    }
    
    const stph_dt_getIsProjectPage = function() {
        return true
    }
</script>
<!-- Insert Vue.js after DOM -->
<script src="<?= $module->getUrl('./dist/appMonitor.js') ?>"></script>
