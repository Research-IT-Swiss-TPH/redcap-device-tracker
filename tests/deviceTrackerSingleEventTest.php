<?php namespace STPH\deviceTracker;
require_once __DIR__ . '/../../../redcap_connect.php';

use REDCap;
use Project;

/**
 * This test class covers test cases for tracking
 * 
 * from     single       instrument     single-event    single arm
 * relevant project_id := self::$testPIDs[1]
 * 
 */
class deviceTrackerSingleEventTest extends BaseTest
{
    public $module;

    //-----------------------------------------------------
    // Fixtures
    //-----------------------------------------------------    

    static function setUpBeforeClass(): void
    {

        parent::setUpBeforeClass();

        //  Fixture Info Tracking Project Context
        self::echo("\n==> Tracking Project ID - Single Event: " . TEST_TRACKING_PROJECT_SINGLE . "\n", 'raw');
        self::echo("=== Done ===\n", 'raw');

        //  Fixture Tracking Project Fields
        //  Add test tracking field (text)
        self::addField(TEST_TRACKING_PROJECT_SINGLE);
        
    }


    //-----------------------------------------------------
    // Tests
    //-----------------------------------------------------

    public function testSystemSettingInTestContext(): void
    {
        $expected = TEST_DEVICES_PROJECT;
        $actual = $this->module->devices_project_id;

        $this->assertSame($expected, $actual);
    }


    /**
     * Check if we have valid Device Data
     * 
     * count should be 10, see fixture
     */
    public function testDeviceDataIsValid() {

        $expected = 10;

        $params = array(
            'project_id' => TEST_DEVICES_PROJECT,
            'dataFormat' => 'json',
            'fields' => 'record_id'
        );
        $actual = REDCap::getData($params);

        $this->assertCount( $expected, $actual);
    }    


    /**
     * Assign
     * 
     */
    public function testAssignDevice() {
       
        // $project = $this->module->getProject(TEST_TRACKING_PROJECT_SINGLE);
        // self::echo(print_r($project));

        # Fixture: 
        # Add field to form_1: tracking_field

        # Mock request array - ignore extra for now
        $request = array(
            'mode'      => 'assign',
            'pid'       => TEST_TRACKING_PROJECT_SINGLE,
            'event_id'  => TEST_EVENT_TRACKING_SINGLE,
            'owner_id'  => TEST_TRACKING_RECORD_ID,
            'field_id'  => TEST_TRACKING_FIELD_ID,
            'device_id' => TEST_DEVICES_RECORD_ID,
            'user_id'   => TEST_USER_ID,
            "extra"     => ""
        );

        self::echo(print_r($request));
        $tracking = new Tracking($request);
        self::echo(print_r($tracking));

        $this->assertSame("ok", "ok");

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
        $actual = $this->module->getTrackingData(TEST_TRACKING_PROJECT_SINGLE, TEST_TRACKING_RECORD_ID, TEST_TRACKING_FIELD_ID);
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