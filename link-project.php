<?php
/** @var \STPH\deviceTracker\deviceTracker $module */
namespace STPH\deviceTracker;
    
    $module->initializeJavascriptModuleObject();
?>

<div id="STPH_DT_MONITOR"></div>
<script type='text/javascript'>

    const stph_dt_getModuleFromBackend = function() {
        return <?=$module->getJavascriptModuleObjectName()?>;
    }

    const stph_dt_getBaseUrlFromBackend = function () {
        return '<?= $module->getUrl("requestHandler.php") ?>'
    }
    const stph_dt_getConfigFromBackend = function() {
        return <?= json_encode($module->getConfig()) ?>
    }
    
    const stph_dt_getIsProjectPage = function() {
        return true
    }

    const stph_dt_getRootFromBackend = function() {
        return '<?= APP_PATH_WEBROOT ?>' 
    }

</script>
<!-- Insert Vue.js after DOM -->
<script src="<?= $module->getUrl('./dist/appMonitor.js') ?>"></script>
