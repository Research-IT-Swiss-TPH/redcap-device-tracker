<?php 

namespace STPH\deviceTracker;

use ExternalModules\ExternalModules;
use Project;
use Vanderbilt\REDCap\Classes\ProjectDesigner;

// For now, the path to "redcap_connect.php" on your system must be hard coded.
require_once __DIR__ . '/../../../redcap_connect.php';

//-----------------------------------------------------
// Constants
//-----------------------------------------------------

const PATH_FIXTURE_DEVICE_DICT = "/fixtures/data_dictionary_device_data.csv";
const PATH_FIXTURE_DEVICE_DATA = "/fixtures/data_import_device_data.json";

const TEST_USER_ID = "phpunit_test_user";

const TEST_DEVICES_RECORD_ID    = "A10000";
const TEST_TRACKING_RECORD_ID   = 1;
const TEST_TRACKING_FIELD_ID    = "test_tracking_field";

const TEST_SETTING_DEVICE_TYPES = "0,3";

abstract class BaseTest extends \ExternalModules\ModuleBaseTest{

    public static array $testPIDs = [];

    //-----------------------------------------------------
    // Fixtures
    //-----------------------------------------------------    

    static function setUpBeforeClass(): void
    {
        self::echo("\n=== Setting up before class ===\n", 'raw');
        parent::setUpBeforeClass();
        self::createTestProjects();

        # Fixture Projects
        define('TEST_DEVICES_PROJECT',          explode(',', $GLOBALS['external_modules_test_pids'])[0]);
        define('TEST_TRACKING_PROJECT_SINGLE',  explode(',', $GLOBALS['external_modules_test_pids'])[1]);
        define('TEST_TRACKING_PROJECT_MULTPLE', explode(',', $GLOBALS['external_modules_test_pids'])[2]);

        define('TEST_EVENT_TRACKING_SINGLE',    self::getFirstEventIdByProject(TEST_TRACKING_PROJECT_SINGLE));
        define('TEST_EVENT_TRACKING_MULTIPLE',  self::getFirstEventIdByProject(TEST_TRACKING_PROJECT_MULTPLE));

        
        #   Fixture Devices Project
        //  Create Device Project Structure    from xml PATH_FIXTURE_DEVICE_TEMP
        self::fixtureDataDictionaryDevices();
        //  Import Device Project Data         from csv PATH_FIXTURE_DEVICE_DATA
        self::fixtureRecordDataDevices();

        self::echo("=== Done ===\n", 'raw');
    }

    static function tearDownAfterClass():void{
        self::echo("\n=== Tearing down after class ===\n", 'raw');
        // self::cleanupTestProjects();
        // self::cleanupRecordData();
        // self::preserveProjectsTable();
        self::echo("=== Done ===\n", 'raw');
    }

    //-----------------------------------------------------
    // Helpers
    //-----------------------------------------------------

    static public function getFirstEventIdByProject($project_id) {
        $project = new Project($project_id);
        return $event_id = $project->firstEventId;
    }

 
    static public function addField($project_id, $formName="form_1", $fieldParams = []) {

        $fieldParams = array(
            "field_label"   => "Test Tracking Field",
            "field_name"    => TEST_TRACKING_FIELD_ID,
            "field_type"    => "text"
        );

        $project = new Project($project_id);
        $projectDesigner = new ProjectDesigner($project);

        $projectDesigner->createField($formName, $fieldParams);

    }


    /**
     * Fixture to import data dictionary into Devices Project
     * 
     */
    static private function fixtureDataDictionaryDevices() {
            
        $dictionary_array = \Design::excel_to_array( dirname(__FILE__) . PATH_FIXTURE_DEVICE_DICT, "," );
        \MetaData::save_metadata($dictionary_array, false, true, TEST_DEVICES_PROJECT);

        self::echo("Data Dictionary imported to Device Data.", "fixture");            

    }

    /**
     * Fixture to import record data into Devices Project
     * 
     */
    static private function fixtureRecordDataDevices() {

        $json = file_get_contents( dirname(__FILE__) . PATH_FIXTURE_DEVICE_DATA);
        $params = array(
            "project_id" => TEST_DEVICES_PROJECT,
            "dataFormat" => 'json',
            "data" => $json
        );
        $result = \Records::saveData($params);

        self::echo($result["item_count"] . " items added to Device Data.", "fixture");
    }    

    /**
     * Create Test Projects 
     * 
     */
    static function createTestProjects() {
         // Get test PIDs
        ExternalModules::getTestPIDs();
        self::echo("Test Projects have been created. (PIDs: ". $GLOBALS['external_modules_test_pids'] . ")");
    }

    /**
     * Delete Test Projects from redcap_projects and redcap_config
     * 
     */
    static function cleanupTestProjects() {
        
        $sql = 'DELETE FROM redcap_projects WHERE `app_title` LIKE "External Module Unit Test Project%" ';
        ExternalModules::query($sql, []);


        ExternalModules::query(
            "DELETE FROM `redcap_config` WHERE  `field_name`='external_modules_test_pids'", []
        );

        $GLOBALS['external_modules_test_pids'] = '';

        self::echo("Test Projects have been deleted.");
    }

    static function cleanupRecordData() {

        $sql = 'DELETE FROM redcap_data WHERE `project_id` = ?';
        ExternalModules::query($sql, [TEST_DEVICES_PROJECT]);

    }

    /**
     * Preserve REDCap projects table
     * 
     * Sets redcap_project AUTO_INCREMENT to MAX(project_id)
     * https://stackoverflow.com/a/41466825/3127170
     * 
     */
    static function preserveProjectsTable() {

        ExternalModules::query("SET @m = (SELECT MAX(project_id) + 1 FROM redcap_projects)", []);
        ExternalModules::query("SET @s = CONCAT('ALTER TABLE redcap_projects AUTO_INCREMENT=', @m)", []);
        ExternalModules::query("PREPARE stmt1 FROM @s", []);
        ExternalModules::query("EXECUTE stmt1", []);
        ExternalModules::query("DEALLOCATE PREPARE stmt1", []);

        self::echo("Projects table has been preserved.");

    }

    /**
     * Output formatted message string to console during testing.
     * 
     */
    protected static function echo($message, $mode = "")
    {
        // if output buffer has not started yet
        if (ob_get_level() == 0) {
            // current buffer existence
            $hasBuffer = false;
            // start the buffer
            ob_start();
        } else {
            // current buffer existence
            $hasBuffer = true;
        }


        // echo message to output with color and unicode
        if($mode != 'raw') {

            //$unicode = "\u{2588}\u{2588}";
            $unicode = "\u{2724}";           
            $format = " \33[34m".$unicode."\33[0m \33[44m";

            //  Different color for fixtures
            if($mode == "fixture") {
                $format = " \33[35m".$unicode."\33[0m \33[45mFixture success: ";
            }

             $message = $format . $message . "\33[0m\n";
        }

        echo $message;

        // flush current buffer to output stream
        ob_flush();
        flush();
        ob_end_flush();

        // if there were a buffer before this method was called
        //      in my version of PHPUNIT it has its own buffer running
        if ($hasBuffer) {
            // start the output buffer again
            ob_start();
        }
    }
}