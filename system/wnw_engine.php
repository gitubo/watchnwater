<?php

/**
* Watch 'n' Water engine
*
* This is the core of the WnW project, where the logic of the watering system is implemented
* and the commands are actually sent to the sketch uploaded into the Arduino
*
* LICENSE: GPL v3
*
*/
define('VERBOSE', true);
define("LOG_FILENAME", "/mnt/sd/rhc/log/engine.log");  // the log file
define("DB_FILENAME", 'sqlite:/mnt/sd/rhc/wnwdb.sqlite');  // the sqlite3 db
define('SUCCESS', 0);
define('GENERIC_ERROR', 1);

/**
 * The class to talk with the 32U4 processore is needed
 */
require("bridgeclient.php"); 

/**
 * Global variables
 */
$bridge = null;
$dblink = null;
$stay_in_the_loop = true:
$watering_plan = null;

try {
    logi(" <-- WnW Engine started --> ");

    /**
     * In order to communicate with the 32U4 processor,
     * the PHP engine creates just one connection (bridge)
     * and open it at the very beginning and close it at the very end
     */
   	logi("Connecting to the bridge...");
    global $bridge = new wnw_bridge();
	$bridge->connect();

	/**
	 * Start the connection to the database to store/retrieve info
	 */
	logi("Connecting to the database...");
    global $dblink = new \PDO(DB_FILENAME);

    /**
     * Startup process
     */
    logi("Calling the startup procedure...");
    global $stay_in_the_loop = true;
    startup();

    /**
     * Main loop
     */
    if($stay_in_the_loop) logi("Running the main loop...");
    while($stay_in_the_loop){

    }

	/**
	 * Disconnecting the bridge and release the database
	 */
	logi("Disconnecting the bridge...");
	$bridge->disconnect();
	$dblink = null;

} catch (Exception $e) {
	global $bridge;
	if($bridge != null) {
		$bridge->disconnect();
	}
    echo $e->getMessage();
    logi($e->getMessage());
    exit;
}

/**
 * Startup process
 *
 * Code here anything we want to run before the main loop 
 * will be executed
 */

function startup()
{
	global $stay_in_the_loop = false;

	// Startup procedure complited successfully
	$stay_in_the_loop = true;
}

/**
 * Main loop processes
 */

function retrieve_watering_plan()
{
	if (DEBUG):
	    logi("Retrieving the watering plan...");
	endif;

    $query = "SELECT id, actuator, time(from, '%H:%M') as start_time, duration, weekdays_bitmask, is_forced FROM watering_plan";
    $query .= " WHERE is_valid = 1";
    global $watering_plan = getDataArray($query);
	
	if (DEBUG):
	    foreach ($watering_plan as $i) {
	        $msm = "PlanID = " . $i['id'] . " -> actuatorID=" . $i['actuator'];
	        $msm .= " -> @ " . $i['start_time'] . ", duration " . $i['duration'] . " min(s)";
	        $msm .= " weekdays = '" . $i['weekdays_bitmask'] . " (forced = " . $i['is_forced'] . ")";
	        logi($msm);
	    }
	endif;
}

/**
 * Utilities
 */

function logi($message)
{
    try {
        $a = date("Y-m-d H:i:s");
        $s = $a . "   " . $message . "\n";
        error_log($s, 3, LOG_FILENAME);
    } catch (\Exception $ex) {
        echo $ex->getMessage();
    }
}

?>
