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
define('SOIL_MOISTURE_SENSOR', false);
define('SOIL_MOISTURE_THRESHOLD', 1000);

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
$wateringPlan = null;
$settings = null
$actuators = null;

try {
    logi("\n\n    <-- WnW Engine started --> \n");

    /**
     * In order to communicate with the 32U4 processor,
     * the PHP engine creates just one connection (bridge)
     * and open it at the very beginning and close it at the very end
     */
   	logi("Connecting to the bridge...");
    global $bridge = new wnw_bridge();
	$bridge->connect();
	if($bridge == null) {
		logi("ERROR: Bridge not available");
		logi("ERROR: Exit");
		return;
	}

	/**
	 * Start the connection to the database to store/retrieve info
	 */
	logi("Connecting to the database...");
    global $dblink = new \PDO(DB_FILENAME);
	if($dblink == null) {
		logi("ERROR: Database not accessible");
		logi("ERROR: Exit");
		return;
	}

    /**
     * Startup process
     */
    logi("Calling the startup procedure...");
    global $stay_in_the_loop = true;
    $actuatorArray = startup();

    /**
     * Main loop
     */
    if($stay_in_the_loop) logi("Running the main loop...");
    while($stay_in_the_loop){

    	// Set the right status for each actuator
    	foreach($actuatorArray as $actuator){
    		// Get the current status of the actuator
    		$currentStatus = getCurrentStatus($actuator);

    		// Check the expected status of this actuator
    		$expectedStatus = getExpectedStatus($actuator);

    		// Change the status if needed
    		switch($expectedStatus){
    			case 0:
    				if($currentStatus == 0 || $currentStatus == false) ;
    				else {
    	  				logi("Turning OFF actuator " . $actuator);
    					turnOffActuator($actuator);
    				}
    				break;
    			case 1:
    				if($currentStatus == 1 || $currentStatus == true) ;
    				else {
    	  				logi("Considering to turn ON actuator " . $actuator);
    	  				turnOnActuator($actuator, false); //consider other params before turning on
    	  			}
    				break;
    			case 2:
    				if($currentStatus == 1 || $currentStatus == true) ;
    				else {
    	  				logi("Turning ON actuator " . $actuator . " [FORCED]");
    	  				turnOnActuator($actuator, true);  //force it to turn on
    	  			}
    				break;
    			default:
    				logi("ERROR: Unsupported expected status for actuator " . $actuator);
    				logi("ERROR: The actuator will be turned off!");
    				turnOffActuator($actuator);
    				break;
    		}

    	}

    	//wait 30 seconds 
    	if (DEBUG):
    		logi("DELAY 30000...");
    	endif
    	delay(30000);

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
 * Startup procedure
 *
 * Code here anything we want to run before the main loop 
 * will be executed
 */

function startup()
{
	global $dblink;
	global $bridge;
	global $actuators;
	global $wateringPlan;


	// Prevent the main loop to be executed in case startup procedure fails
	global $stay_in_the_loop = false;

	// Ask RTC to align the system date
	logi("System datetime is " + date("m/d/Y h:i:sa"));
	logi("Aligning the system with the RTC datetime...");
	$bridge->put("align_datetime","1");
	if (DEBUG):
		logi("DELAY 2000...");
	endif
	delay(2000);
	if($bridge->get("align_datetime") != "0") {
		logi("ERROR: Onboard RTC doesn't respond");
		return;
	}
	logi("System datetime post alignment is " + date("m/d/Y h:i:sa"));

	// Retrieve settings
	logi("Retrieving settings...");
	retrieve_settings();

	// Retrieve the actuators
	logi("Retrieving actuators...");
	retrieve_actuators();
	if(count($actuators) == 0) {
		logi("WARNING: No actuator defined");
		return;
	}

	// Retrieve the watering plan (only valid entries)
	logi("Retrieving watering plan...");
	retrieve_watering_plan();
	if(count($wateringPlan) == 0) {
		logi("WARNING: No watering plan defined");
		return;
	}

	// Retrieve the watering plan (only valid entries)
	logi("Get actuators impacted by the watering plan");
	$actuatorArray = calculate_impacted_actuators();
	if(count($actuatorArray) == 0) {
		logi("WARNING: No actuator impacted by the defined watering plan");
		return;
	} else
		logi("The watering plan impacts " . count($actuatorArray) . " actuator(s)");

	// Startup procedure completed successfully
	$stay_in_the_loop = true;
	return $actuatorArray;
}

function retrieve_settings(){
	$query = "SELECT name, int_value, string_value FROM settings";
    $entries = getDataArray($query);
	
    foreach ($entries as $i) {
		if (DEBUG):
	        $msm = "Setting = " . $i['name'] . " -> int_value=" . $i['int_value'] . ", string_value=" . $i['string_value'];
	        logi($msm);
		endif;
		$setting = array("name" => $i['name'], 
						 "intValue" => $i['int_value'],
						 "stringValue" => $i['string_value']);
		global $settings[] = $setting;
		if (DEBUG):
			logi("Loaded " . count($settings) . " settings");
		endif;
    }
}

function retrieve_watering_plan()
{
	global $wateringPlan = null;

    $query = "SELECT id, actuator, time(from, '%H:%M') as start_time, duration, weekdays_bitmask, is_forced FROM watering_plan";
    $query .= " WHERE is_valid = 1";
    $entries = getDataArray($query);
	
    foreach ($entries as $i) {
		if (DEBUG):
	        $msm = "PlanID = " . $i['id'] . " -> actuatorID=" . $i['actuator'];
	        $msm .= " -> @ " . $i['start_time'] . ", duration " . $i['duration'] . " min(s)";
	        $msm .= " weekdays = '" . $i['weekdays_bitmask'] . " (forced = " . $i['is_forced'] . ")";
	        logi($msm);
		endif;
		$planItem = array("actuator" => $i['actuator'], 
						  "startTime" => $i['start_time'],
						  "duration" => $i['duration'],
						  "weekdays" => $i['weekdays_bitmask'],
						  "is_forced" => $i['is_forced']);
		global $wateringPlan[] = $planItem;
		if (DEBUG):
			logi("Loaded " . count($wateringPlan) . " records into the watering plan");
		endif;
    }
}

function retrieve_actuators()
{
	global $actuators = null;

    $query = "SELECT id, sketch_name FROM actuators";
    $entries = getDataArray($query);
	
    foreach ($entries as $i) {
		if (DEBUG):
	        $msm = "ActuatorID = " . $i['id'] . " -> sketch_name=" . $i['sketch_name'];
	        logi($msm);
		endif;
		$actuator = array($i['id'] => $i['sketch_name']);
		global $actuators[] = $actuator;
		if (DEBUG):
			logi("Loaded " . count($actuators) . " actuators");
		endif;
    }
}

function calculate_impacted_actuators(){
	global $wateringPlan;
	$actuatorsArray = null;

	foreach ($wateringPlan as $i) {
		if (in_array($i["actuator"], $actuatorsArray))
			$actuatorsArray[] = $i["actuator"];
	}

	return $actuatorsArray;
}

/**
 * Main loop procedures
 *
 */

/**
 * Get the status of the actuator as it is supposed to be
 * according to the watering plan:
 * 0 = inactive
 * 1 = active
 * 2 = active (is_forced = true)
 */
function getExpectedStatus($actuator){
	global $wateringPlan;
	// Scroll the watering plan and check if this specific actuator is supposed to be activated now
	foreach($wateringPlan as $wp){
		if($wp["actuator"] == $actuator){ // is the right actuator?
			$weekday = date("N"); // 1 (for Monday) through 7 (for Sunday)
			if($wp['weekdays_bitmask']{$weekday-1} != "0"){ // is the right day?
				// Calculate the number of minutes from midnight 
				$hour = date("H"); $minute = date("i");
				$minutes = $hour * 60 + $minute;
				// Calculate the number of minutes from midnight of the record in watering plan
				$hour = substr($wp["startTime"],0,2); $minute = substr($wp["startTime"],3,2);
				$minutesFrom = $hour * 60 + $minute;
				if($minutes >= $minutesFrom && $minutes <= ($minutesFrom + $wp["duration"]) ){  // is the right time?
					if (DEBUG):
						logi("Found a match in the watering plan:");
						logi(" -> actuator = " . $wp["actuator"]);
						logi(" -> weekday = " . $weekday . " (" . $wp["weekdays_bitmask"] . ")");
						logi(" -> time = " . $wp["startTime"] . " and duration " . $wp["duration"] . " min(s)");
					endif;
					if($wp["is_forced"]) return 2;
					else return 1;
				} 
			} 
		}
	}
	else return 0;
}

/**
 * Turn off the actuator
 * 
 * Here no logic is needed, just turn it off and log the action
 */

function turnOffActuator($actuator){
	global $actuators;

	$bridge->put($actuators[$actuator]["sketch_name"], "0");
	if (DEBUG):
		logi("DELAY 2000...");
	endif
	delay(2000);
	if($bridge->get($actuators[$actuator]["sketch_name"]) != "0") {
		logi("ERROR: It was not possible to set PIN " . $actuators[$actuator]["sketch_name"] . " to LOW");
		return false;
	}
	return true;
}

/**
 * Turn on the actuator
 * 
 * Here we have to implement some logic 
 * in case the $force parameter is false
 */

function turnOnActuator($actuator, $force){
	global $actuators;

	if($force == false || $force == 0) {
		if (SOIL_MOISTURE_SENSOR)
			// TO BE IMPLAMENTED
			/**
			 * We have to consider the avegare value of the soil moisture
			 * Consider that a sample is taken almost every seconds,
			 * so 10 samples means the averege value in 10 seconds (rawly)
			 */
			if(getLatestSoilMoistureAverageValue(10) > SOIL_MOISTURE_THRESHOLD) {
				return false;
			} 
		endif

		if (WEATHER_FORECAST)
			/**
			 * We have to consider the weather forecast
			 */

		endif

	}

	// The actuator must be truned on
	$bridge->put($actuators[$actuator]["sketch_name"], "1");
	if (DEBUG):
		logi("DELAY 2000...");
	endif
	delay(2000);
	if($bridge->get($actuators[$actuator]["sketch_name"]) == "0") {
		logi("ERROR: It was not possible to set PIN " . $actuators[$actuator]["sketch_name"] . " to HIGH");
		return false;
	}
	return true;
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

function getSetting($name, $type)
{
	foreach ($settings as $s){
		if($s["name"] == $name){
			switch($type) {
				case 0:
				case "int":
				case "i":
					return $s["intValue"];
					break;
				case 1:
				case "string":
				case "s":
				case "str":
					return $s["stringValue"];
					break;
				default:
					return $s["intValue"];
			}
		}
	}
	return null;
}

int getLatestSoilMoistureAverageValue(int _samplesNumber){
	$query = "SELECT soil_moisture FROM sensors_log ORDER BY date DESC LIMIT " . _samplesNumber . ";";
    $entries = getDataArray($query);
    int average = 0;
    foreach ($entries as $i) {
    	average += $i["soil_moisture"];
    }
	return average/_samplesNumber;
}

?>
