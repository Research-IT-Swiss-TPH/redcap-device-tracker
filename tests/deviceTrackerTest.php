<?php 
namespace STPH\deviceTracker;

use Exception;
use ExternalModules\ExternalModules;


use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

require_once __DIR__ . '/../../../redcap_connect.php';


/**
 * Information about Testing Fixture
 * 
 * To test the module with given testing possibilities in REDCap External Module Framework
 * we will be defining the three test projects returned by self::$testPIDs = ExternalModules::getTestPIDs() as follows:
 * 
 * self::$testPIDs[0] - Device Data Project
 * self::$testPIDs[0] - Tracking Project 1
 * self::$testPIDs[0] - Tracking Project 2
 * 
 * These fixtures currently have to be implemented into module code to achieve testing context for module system settings,
 * see GH issue: https://github.com/vanderbilt-redcap/external-module-framework/issues/546
 * 
 * 
 */


const TEST_USER_ID = "phpunit_test_user";

const TEST_DEVICES_RECORD_ID    = "A10000";
const TEST_TRACKING_RECORD_ID   = 1;
const TEST_TRACKING_FIELD_ID             = "test_tracking_field";


const TEST_SETTING_DEVICE_TYPES = "0,3"; 

const PATH_FIXTURE_DEVICE_DICT = "/fixtures/data_dictionary_device_data.csv";
//const PATH_FIXTURE_DEVICE_DATA = "/fixtures/data_import_device_data.csv";

const PATH_FIXTURE_DEVICE_DATA = "/fixtures/data_import_device_data.json";


class deviceTrackerTest extends BaseTest
{
    // Declare this to ommit Inteliphense error
    public $module;


    /**
     * Run once before all tests
     * 
     */
    static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        /**
         * Set Project Context: Devices Project: self::$testPIDs[0]
         * Data Import will not work without global variables set!
         * 
         */
        global $lang, $table_pk, $longitudinal, $Proj, $user_rights, $project_encoding;
        $table_pk = "record_id";
        $Proj = new \Project(self::$testPIDs[0]);

        //  Fixtures
        #   Create Device Project Structure    from xml PATH_FIXTURE_DEVICE_TEMP
        #   Import Device Project Data         from csv PATH_FIXTURE_DEVICE_DATA

        self::fixtureDataDictionaryDevices();
        self::fixtureRecordDataDevices();


        //  Fixture Tracking Project Context
        define( 'PROJECT_ID', self::$testPIDs[1]);
        $Proj = new \Project(self::$testPIDs[1]);
    }

    /**
     * Run before each test
     * 
     */
    public function setUp(): void
    {
        parent::setUp();


    }

    //-----------------------------------------------------
    // Helpers
    //-----------------------------------------------------

    /**
     * Fixture to import data dictionary into Devices Project
     * 
     */
    static private function fixtureDataDictionaryDevices() {
       
        $dictionary_array = \Design::excel_to_array( dirname(__FILE__) . PATH_FIXTURE_DEVICE_DICT, "," );
        \MetaData::save_metadata($dictionary_array, false, true, self::$testPIDs[0]);

        self::echo("Data Dictionary imported to Device Data.", "fixture");
    }

    /**
     * Fixture to import record data into Devices Project
     * 
     */
    static private function fixtureRecordDataDevices() {

        $json = file_get_contents( dirname(__FILE__) . PATH_FIXTURE_DEVICE_DATA);
        $params = array(
            "project_id" => self::$testPIDs[0],
            "dataFormat" => 'json',
            "data" => $json
        );

        $result = \Records::saveData($params);

        self::echo($result["item_count"] . " items added to Device Data.", "fixture");
    }

    //-----------------------------------------------------
    // Tests
    //-----------------------------------------------------

    public function testSystemSettingInTestContext(): void
    {
        $expected = self::$testPIDs[0];
        $actual = $this->module->devices_project_id;

        self::echo("Test Device Project: ". self::$testPIDs[0] );

        $this->assertSame($expected, $actual);
    }


    /**
     * Assign
     * 
     */
    public function testAssignDevice() {

        /**
         * Mock request array 
         * - ignore extra for now
         * 
         * 
         */
        $request = array(
            'mode'      => 'assign',
            'event_id'  => 'get-event-id-from-tracking-project-form',
            'owner_id'  => TEST_TRACKING_RECORD_ID,
            'field_id'  => TEST_TRACKING_FIELD_ID,
            'device_id' => TEST_DEVICES_RECORD_ID,
            'user_id'   => TEST_USER_ID,
            "extra"     => ""
        );

        $tracking = new Tracking(array(

        ));

    }

    /**
     * Get tracking Data
     * 
     * Test Requirements:
     * 1. Device Data imported              @ Devices Project
     * 2. Module enabled & configured       @ Tracking Project
     * 
     * 
     */
    public function testGetTrackingData() {

        # No data
        $actual = $this->module->getTrackingData(TEST_TRACKING_RECORD_ID, TEST_TRACKING_FIELD_ID);
        $expected = [];
        $this->assertSame($expected, $actual, "Test no data.");

        // # Add some data
        // $tracking_data = [
        //     "record_id" => TEST_TRACKING_RECORD_ID,
        //     TEST_TRACKING_FIELD_ID => "foo"
        // ];
        // $actual= ["foo"];
        // $this->assertSame($expected, $actual, "Test some data.");

        // self::echo(print_r($actual));

        // $csv = str_getcsv(file_get_contents(PATH_FIXTURE_DEVICE_DATA));

        // $this->echo(dirname(__FILE__));
        // $this->echo(count($csv));

        // $actual = $this->module->getTrackingData();
        $actual = [];
        $expected = [];
        //$actual = $this->testGetAvailableDevices();
        //$actual = "foo";

        $this->assertSame($expected, $actual);
    }

}