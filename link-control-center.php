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
        This page gives you an overview of your Device Tracker Configuration status and also access to system-wide tracking logs.
    </p>
<?php    
    //dump(new \Project(15));
    //dump("Ok");
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
        return false
    }

    const stph_dt_getRootFromBackend = function() {
        return '<?= APP_PATH_WEBROOT ?>' 
    }

    const stph_dt_jsmo = <?=$module->getJavascriptModuleObjectName()?>
    

</script>
<!-- Insert Vue.js after DOM -->
<script src="<?= $module->getUrl('./dist/appMonitor.js') ?>"></script>
