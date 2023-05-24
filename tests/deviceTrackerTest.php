<?php 
namespace STPH\deviceTracker;

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

class deviceTrackerTest extends BaseTest
{
    // Declare this to ommit Inteliphense error
    public $module;
    private $test_devices_project_id;

    public function setUp(): void
    {
        parent::setUp();
        $this->test_devices_project_id  = self::$testPIDs[0];    
    }

    public function testSystemSettingInTestContext(): void
    {
        $expected = $this->test_devices_project_id;
        $actual = $this->module->devices_project_id;

        $this->echo("Test Device Project: ".$this->test_devices_project_id);

        $this->assertSame($expected, $actual);
    }

}