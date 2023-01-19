<?php 

namespace STPH\deviceTracker;

use ExternalModules\ExternalModules;
use Project;

// For now, the path to "redcap_connect.php" on your system must be hard coded.
require_once __DIR__ . '/../../../redcap_connect.php';

abstract class BaseTest extends \ExternalModules\ModuleBaseTest{

    public static array $testPIDs = [];

    static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        
        //  Create test PIDs
        self::$testPIDs = ExternalModules::getTestPIDs();
        self::echo("Test Projects have been created.(PIDs: ". implode(", ", self::$testPIDs) . ")");
        
        //  Set Project Context and global project object
        define('PROJECT_ID', self::$testPIDs[0]);
        global $Proj;
        $Proj = new Project(PROJECT_ID);

        //  Give info to see better whats up
        self::echo("Available forms: " . implode(", ", array_keys($Proj->forms)));
    }

    static function tearDownAfterClass():void{
        self::cleanupTestProjects();
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

        self::echo("Test Projects have been deleted.");
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

        $unicode = "\u{2588}\u{2588}";
        $format = " \33[34m".$unicode."\33[0m \33[44m";

        if($mode == "fixture") {
            $format = " \33[35m".$unicode."\33[0m \33[45m";
        }

        // echo message to output with color and unicode
        $message = $format . $message . "\33[0m\n";
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