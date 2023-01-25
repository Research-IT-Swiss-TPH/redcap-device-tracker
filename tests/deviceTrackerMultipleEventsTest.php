<?php namespace STPH\deviceTracker;

require_once __DIR__ . '/../../../redcap_connect.php';


/**
 * This test class covers test cases for tracking
 * 
 * from     single       instrument     multiple-event  single arm
 * 
 * relevant project_id := self::$testPIDs[2]
 * 
 */
class deviceTrackerMultipleEventsTest extends BaseTest
{
    public $module;

    /**
     * Run once before all tests
     * 
     */
    static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        //  Fixture Info Tracking Project Context
        self::echo("\n==> Tracking Project ID - Multiple Event: " . TEST_TRACKING_PROJECT_MULTPLE . "\n", 'raw');
        self::echo("=== Done ===\n", 'raw');
    } 




    //-----------------------------------------------------
    // Tests
    //-----------------------------------------------------

    public function testExampleMultipleEventsProject(): void
    {
        // self::echo("Project ID:");
        // self::echo(PROJECT_ID);

        $this->assertSame("Foo", "Foo");
    }

}