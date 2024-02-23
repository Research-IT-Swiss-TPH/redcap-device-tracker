<?php
/** @var \STPH\deviceTracker\deviceTracker $module */
namespace STPH\deviceTracker;
    
    $module->initializeJavascriptModuleObject();
?>
<h4 style="margin-top:0;" class="clearfix">
<div class="pull-left float-left">
    <i class="fas fa-satellite-dish"></i>
    Device Tracker	</div>
</h4>
<p>
    This page gives you an overview of your Device Tracker Configuration status and also access to project-wide tracking logs.
</p>
<div id="STPH_DT_MONITOR"></div>
<script type='text/javascript'>

    const stph_dt_getModuleFromBackend = function() {
        return <?=$module->getJavascriptModuleObjectName()?>;
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
