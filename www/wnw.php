<?php


/* * **************************************
 * ******Configuration section*************
 * ************************************** */

define("VERSION", "0.1");
date_default_timezone_set('Europe/Rome');
define("DAYS_HISTORIC", 30);
define("USER_NO_IP", "user");
define("PASSWORD_NO_IP", "password");
define("HOST_NAME_NO_IP", "host");
define("LOG_FILENAME", "/mnt/sd/rhc/log/webinterface.log");
define("DB_FILENAME", 'sqlite:/mnt/sd/rhc/wnwdb.sqlite');


/* * **************************************
 * ******End of configuration section*******
 * ************************************** */

/* 
 * There is just one connection to the DB so, in order to make the code easy, 
 * we will have a global variable to manage it
 */
$dblink = null;

try {

    logi(" <-- NEW REQUEST COMING --> ");
    if (isset($_POST["command"]) && $_POST["command"] != "") {
        #Retrieve the task
        $command = $_POST["command"];

        logi("Evaluating command '" . $task . "'...");

		logi("Connecting to the database...");
        global $dblink = new \PDO(DB_FILENAME);

		// Main switch
		switch ($command) {
            case "retrieve_settings":
                retrieve_settings();
                break;
            case "retrieve_actions":
                retrieve_actions();
                break;
            case "retrieve_actions_log":
                $from = null;
                $to = null;
                if (isset($_POST["from"]) && $_POST["from"] != "") $from = $_POST["from"];
                if (isset($_POST["to"]) && $_POST["to"] != "") $to = $_POST["to"];
                retrieve_actions_log($from,$to);
                break;
            case "retrieve_actuators":
                retrieve_actuators();
                break;
            case "retrieve_actuators_latest_status":
                retrieve_actuators_status();
                break;
            case "retrieve_actuators_log":
                $from = null;
                $to = null;
                if (isset($_POST["from"]) && $_POST["from"] != "") $from = $_POST["from"];
                if (isset($_POST["to"]) && $_POST["to"] != "") $to = $_POST["to"];
                retrieve_actuators_log($from,$to);
                break;
            case "retrieve_sensors_latest_values":
                retrieve_sensors_latest_values();
                break;
            case "retrieve_sensors_log":
                $from = null;
                $to = null;
                if (isset($_POST["from"]) && $_POST["from"] != "") $from = $_POST["from"];
                if (isset($_POST["to"]) && $_POST["to"] != "") $to = $_POST["to"];
                retrieve_sensors_log($from,$to);
                break;
    		case "version":
	            send_output(VERSION);
        		break;
    		case "log_filename":
	            send_output(LOG_FILENAME);
        		break;
        	default:
        		$logString = "Invalid command specified";
        		send_output($logString);
        		logi($logString);		
		}
		
		logi("Disconnecting from the database");
        $dblink = null;

        logi("Evaluation of command '" . $command . "' completed");
    } else {
        $logString = "No command specified";
        send_output($logString);
        logi($logString);
    }
    ob_end_flush();
    exit;
} catch (\PDOException $ex) {
    echo $ex->getMessage();
    logi($ex->getMessage());
    exit;
} catch (Exception $e) {
    echo $e->getMessage();
    logi($e->getMessage());
    exit;
}


/***** FUNCTIONS *****/

/* Settings */

function retrieve_settings()
{
    logi("Retrieving settings...");

    $query = "select name, int_value, string_value, change_date from settings";
    $settings = getDataArray($query);

    foreach ($settings as $s) {
        $msm = "Setting " . $s['name'] . " -> int_value=" . $s['int_value'];
        $msm .= ", string_value='" . $s['string_value'] . "' (last changed on " . $s['change_date'] . ")");
        logi($msm);
    }
}

/* Actuators */

function retrieve_actuators()
{
    logi("Retrieving the list of the actuators...");

    $query = "SELECT id, description FROM actuators";
    $items = getDataArray($query);

    foreach ($items as $i) {
        $msm = "ActuatorID = " . $i['id'] . " -> type=" . $i['type'] . ", '" . $i['description'] . "'";
        logi($msm);
    }
}

function retrieve_actuators_latest_status()
{
    logi("Retrieving the latest status of the actuators...");

    logi("Retrieving the actuators list...");

    $query = "SELECT id FROM actuators";
    $actuators = getDataArray($query);

    foreach ($actuators as $a) {
        $query2 = "SELECT actuator, date, boolean_value, int_value, string_value FROM actuators_log";
        $query2 .= " WHERE id = (SELECT MAX(id) FROM actuators_log WHERE actuator = " . $a . ")"
        $items = getDataArray($query2);

        foreach ($items as $i) {
            $msm = "ActuatorID = " . $i['actuator'] . " @ " . $i['date'] . " -> BOOL=" . $i['boolean_value'];
            $msm .= ", INT=" . $i['int_value'] . ", STRING='" . $i['string_value'] . "'";
            logi($msm);
        }
    }
}

function retrieve_actuators_log($from, $to)
{
    logi("Retrieving the log of the actuators [$from=" . $from . ", $to=" . $to . "]...");

    $query = "SELECT date, actuator, boolean_value, int_value, string_value FROM actuators_log";
    if ($from != null || $to != null) $query .= " WHERE";
    if ($from != null) {
        $query .= " date >= " . $from;
        if ($to != null) $query .= " AND date <= " . $to;
    } elseif ($to != null) $query .= " date <= " . $to;
    $query .= " ORDER BY date";
    $items = getDataArray($query);

    foreach ($items as $i) {
        $msm = "ActuatorLOG " . $i['date'] . " -> actuatorid=" . $i['actuator'];
        $msm .= " -> BOOL=" . $i['boolean_value'] . ", INT=" . $i['int_value'] . ", STRING='" . $i['string_value'] . "'";
        logi($msm);
    }
}

/* Actuators */

function retrieve_sensors_latest_values()
{
    logi("Retrieving the latest values of the sensors...");

    $query = "SELECT id, date, temperature, humidity, pressure, soil_moisture, luminosity FROM sensors_log";
    $query .= " WHERE date = (SELECT MAX(date) FROM sensors_log)"
    $items = getDataArray($query);

    foreach ($items as $i) {
        $msm = $i['date'] . " -> temperature=" . $i['temperature'] . ", humidity=" . $i['humidity'];
        $msm .= ", pressure=" . $i['pressure'] . ", soil_moisture='" . $i['soil_moisture'];
        $msm .= ", luminosity=" . $i['luminosity']; 
        logi($msm);
    }
}

function retrieve_sensors_log($from, $to)
{
    logi("Retrieving the log of the sensors [$from=" . $from . ", $to=" . $to . "]...");

    $query = "SELECT id, date, temperature, humidity, pressure, soil_moisture, luminosity FROM sensors_log";
    if ($from != null || $to != null) $query .= " WHERE";
    if ($from != null) {
        $query .= " date >= " . $from;
        if ($to != null) $query .= " AND date <= " . $to;
    } elseif ($to != null) $query .= " date <= " . $to;
    $query .= " ORDER BY date";
    $items = getDataArray($query);

    foreach ($items as $i) {
        $msm = $i['date'] . " -> temperature=" . $i['temperature'] . ", humidity=" . $i['humidity'];
        $msm .= ", pressure=" . $i['pressure'] . ", soil_moisture='" . $i['soil_moisture'];
        $msm .= ", luminosity=" . $i['luminosity']; 
        logi($msm);
    }
}

/* Actions */

function retrieve_actions()
{
    logi("Retrieving the list of the actions...");

    $query = "SELECT id, description FROM actions";
    $items = getDataArray($query);

    foreach ($items as $i) {
        $msm = "ActionID = " . $i['id'] . " -> '" . $i['description'] . "'";
        logi($msm);
    }
}

function retrieve_actions_log($from, $to)
{
    logi("Retrieving the log of the actions [$from=" . $from . ", $to=" . $to . "]...");

    $query = "SELECT date, action, from, to FROM actions_log";
    if ($from != null || $to != null) $query .= " WHERE";
    if ($from != null) {
        $query .= " date >= " . $from;
        if ($to != null) $query .= " AND date <= " . $to;
    } elseif ($to != null) $query .= " date <= " . $to;
    $query .= " ORDER BY date";
    $items = getDataArray($query);

    foreach ($items as $i) {
        $msm = "ActionLOG " . $i['date'] . " -> actionID=" . $i['action'];
        logi($msm);
    }
}

/* Watering plan */

function retrieve_watering_plan()
{
    logi("Retrieving the watering plan...");

    $query = "SELECT id, actuator, time(from, '%H:%M') as start_time, duration, weekdays_bitmask, is_forced FROM watering_plan";
    $query .= " WHERE is_valid = 1";
    $items = getDataArray($query);

    foreach ($items as $i) {
        $msm = "PlanID = " . $i['id'] . " -> actuatorID=" . $i['actuator'];
        $msm .= " -> @ " . $i['start_time'] . ", duration " . $i['duration'] . " min(s)";
        $msm .= " weekdays = '" . $i['weekdays_bitmask'] . " (forced = " . $i['is_forced'] . ")";
        logi($msm);
    }
}
/* Utility */

function getDataArray($query)
{
    gloabal $dblink;
	if($dblink == null) {
		logi("Invalid database link");
		return null;
	}
    $handle = $dblink->prepare($query);
    $handle->execute();
    return $handle->fetchAll(PDO::FETCH_ASSOC);

}

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

function send_output($output)
{
    if (function_exists('ob_gzhandler')) ob_start('ob_gzhandler');
    else ob_start();
    echo $output;
    ob_end_flush();

}

/*
function do_query($query, $link)
{
    $handle = $link->prepare($query);
    $handle->execute();
    $resultado = $handle->fetchObject();

    if ($resultado) {
        $resultado = json_encode($resultado);
        return $resultado;
    } else {
        return null;
    }
}

function do_query2($query, $link)
{
    $resultado = null;
    try {
        $handle = $link->prepare($query);
        //   logi($query);

        if ($handle->execute()) {
            $r = $handle->fetchAll(PDO::FETCH_ASSOC);
            if ($r) {
                $r2 = json_encode($r);
                if (!$r2) {
                    logi("Error converting to json.");
                } else {
                    $resultado = $r2;
                }
            } else {
                logi("No data returned.");
            }
        } else {
            logi("Error executing the query.");
        }
    } catch (Exception $e) {
        throw $e;
    }
    return $resultado;
}

function retrieve_external_ip()
{
    $i = do_Curl("http://ipecho.net/plain");
    logi("External ip according to IPECHO: " . $i);
    return $i;
}

function do_Curl($url)
{
    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    return curl_exec($curl);
}

function logi($message)
{
    try {
        $a = date("Y-m-d H:i:s");
        $s = $a . "   " . $message . "\n";
        error_log($s, 3, PHP_LOG_FOLDER);
    } catch (\Exception $ex) {
        echo $ex->getMessage();
    }
}

function eko($cadena)
{
    try {
        echo date("Y-m-d H:i:s") . "   " . $cadena . "\n";
    } catch (\Exception $ex) {
        echo $ex->getMessage();
    }
}

function str_starts_with($cadena, $prefijo)
{
    return substr_compare($cadena, $prefijo, 0, strlen($prefijo)) === 0;
}

function str_ends_with($cadena, $sufijo)
{
    return substr_compare($cadena, $$sufijo, -strlen($$sufijo)) === 0;
}

function clean_tables($link)
{

    $query = "delete from temperatures where temperature_date<date('now','-" . DAYS_HISTORIC . " day')";

    logi($query);
    eko($query);
    $handle = $link->prepare($query);
    $execute = $handle->execute();
    logi($execute);
    eko($execute);


}


function process_historical_data($link)
{
    logi("Start of processing of the historical data.");

    $query = "select distinct(date(t.temperature_date)) as d
from temperatures t
where date(t.temperature_date) not in (select date(date) from statistics order by date asc)
and date(t.temperature_date)<date('now')
order by t.temperature_date asc
";

    $diasPendientes = obtiene_Array($query, $link);

    //Recorremos el array, y para cada dia, calculamos las estadisticas
    foreach ($diasPendientes as $dia) {
        //Seleccionamos las temperaturas de ese dia
        $query = "select * from temperatures where date(temperature_date)='" . $dia['d'] . "' order by id asc";
        $arrayTemperaturas = obtiene_Array($query, $link);


        $segundosTotales = 0;
        $segundosTotalesSystemOn = 0;
        $inicioCalentamiento = "";


        $inicioSystemOn = "";

        $systemOn = false;

        $heating = false;
        $averageTemp = 0;
        $averageDesiredTemp = 0;

        foreach ($arrayTemperaturas as $temp) {

            $h = ($temp['heating'] === 'true') ? true : false;
            $s = ($temp['system_on'] === 'true') ? true : false;

            $averageTemp += $temp['node0'];
            $averageDesiredTemp += $temp['desired_temperature'];

            if ($heating) {
                if (!$h) {
                    $finCalentamiento = $temp['temperature_date'];
                    $heating = false;
                    $segundosTotales += segundos_entre_fechas($inicioCalentamiento, $finCalentamiento);

                }

            } else {
                if ($h) {
                    $inicioCalentamiento = $temp['temperature_date'];
                    $heating = true;
                }

            }


            if ($systemOn) {
                if (!$s) {
                    $finSystemOn = $temp['temperature_date'];
                    $systemOn = false;
                    $segundosTotalesSystemOn += segundos_entre_fechas($inicioSystemOn, $finSystemOn);

                }

            } else {
                if ($s) {
                    $inicioSystemOn = $temp['temperature_date'];
                    $systemOn = true;
                }

            }

        }
        if ($heating) {
            $finCalentamiento = $temp['temperature_date'];
            $segundosTotales += segundos_entre_fechas($inicioCalentamiento, $finCalentamiento);
        }


        if ($systemOn) {
            $finSystemOn = $temp['temperature_date'];
            $segundosTotalesSystemOn += segundos_entre_fechas($inicioSystemOn, $finSystemOn);
        }


        $averageDesiredTemp = $averageDesiredTemp / count($arrayTemperaturas);
        $averageTemp = $averageTemp / count($arrayTemperaturas);


        $query = "INSERT INTO statistics(date,system_on,heating,average_temp,average_desired_temp) VALUES (?,?,?,?,?)";
        $handle = $link->prepare($query);
        $handle->bindValue(1, $dia['d']);
        $handle->bindValue(2, $segundosTotalesSystemOn / 60);
        $handle->bindValue(3, $segundosTotales / 60);
        $handle->bindValue(4, $averageTemp);
        $handle->bindValue(5, $averageDesiredTemp);
        $handle->execute();

        logi("Historical data processed for the day " . $dia['d']);
    }
    logi("End of processing of historical data");

}

function segundos_entre_fechas($inicio, $fin)
{
    $timeFirst = strtotime($inicio);
    $timeSecond = strtotime($fin);
    $differenceInSeconds = $timeSecond - $timeFirst;
    return $differenceInSeconds;
}

function obtiene_Array($query, $link)
{
    $handle2 = $link->prepare($query);
    $handle2->execute();
    return $handle2->fetchAll(PDO::FETCH_ASSOC);

}


function publish_settings($link)
{
    logi("Publishing data...");

    $query = "select key,value from settings where value is not null";

    $settings = obtiene_Array($query, $link);

    $client = new bridgeclient();

    $client->put("version_script_php", VERSION);

    foreach ($settings as $s) {
        $cadena = "Publishing key " . $s['key'] . " with value " . $s['value'];
        $cadena .= ". Result: " . $client->put($s['key'], $s['value']);
        logi($cadena);
    }
}


function create_settings1Time($link)
{
    //When the yun starts, it checks if the settings are in the setting table. If don't, then it creates a per default setting.

    logi("Creating settings for the first time");

    if (!setting_exists("hysteresis",$link)) {
        update_setting($link,"hysteresis","0.5");
	}

    if (!setting_exists("max_temp",$link)) {
        update_setting($link,"max_temp","22");
    }

    if (!setting_exists("min_temp",$link)) {
        update_setting($link,"min_temp","17");
    }

    if (!setting_exists("calibration",$link)) {
        update_setting($link,"calibration","0");
    }

    if (!setting_exists("work_mode",$link)) {
        update_setting($link,"work_mode","manual");
    }

    if (!setting_exists("master_node",$link)) {
        update_setting($link,"master_node","0");
    }

}

function setting_exists($key, $link)
{
    $query = $link->prepare("SELECT * from settings  WHERE key= '".$key."' ");
    $query->execute();
    $fetch = $query->fetch();
    if ($fetch["key"]!=null) {
        return true;
    } else {
        return false;
    }
}

function send_output($output)
{
    if (function_exists('ob_gzhandler')) ob_start('ob_gzhandler');
    else ob_start();
    echo $output;
    ob_end_flush();

}

*/


/**
 * @param $ip
 */
function update_ip($ip)
{
//First we send a fake ip. The point is that if no-ip does not detect an ip change in 30 days the account expires
    $aux = "http://" . USER_NO_IP . ":" . PASSWORD_NO_IP . "@dynupdate.no-ip.com/nic/update?hostname=" . HOST_NAME_NO_IP . "&myip=1.1.1.1";
    logi($aux);
    logi("Result: " . do_Curl($aux));
    //No we send the real ip
    $actualExternalIp = retrieve_external_ip();
    logi("Actual external ip: " . $actualExternalIp);
    $aux = "http://" . USER_NO_IP . ":" . PASSWORD_NO_IP . "@dynupdate.no-ip.com/nic/update?hostname=" . HOST_NAME_NO_IP . "&myip=" . $ip;
    logi($aux);
    logi("Result: " . do_Curl($aux));
}


/**
 * @param $link
 * @param $key
 * @param $value
 * @param $logString
 * @return array
 */
function update_setting($link, $key, $value)
{
    $query = "delete from settings where key=?";
    $handle = $link->prepare($query);
    $handle->bindValue(1, $key, PDO::PARAM_STR);
    $handle->execute();

    $query = "insert into settings(key,value) values(?,?)";
    $handle = $link->prepare($query);
    $handle->bindValue(1, $key);
    $handle->bindValue(2, $value);
    $handle->execute();

    logi("Update setting " . $key . ". completed.");
    send_output("Update setting " . $key . ". completed.");

}



?>
