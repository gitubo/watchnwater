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
define("PHP_LOG_FOLDER", "/mnt/sd/rhc/log/php.log");
define("DATABASE_FILE", 'sqlite:/mnt/sd/rhc/wnwdb.sqlite');


/* * **************************************
 * ******End of configuration section*******
 * ************************************** */

require("bridgeclient.php");
/*
 * Available commands:
 *
 */

/* 
 * There are just one connection to the DB and one connection to the bridge 
 * so, in order to make the code easy, we will have two global variables 
 * to manage these two connections, created only when managing a command
 */
$dblink = null;
$bridge = null;

try {

    if (isset($_POST["command"]) && $_POST["command"] != "") {
        #Retrieve the task
        $command = $_POST["command"];

        $logString = "";
        logi("Evaluation of command '" . $task . "' started");

		logi("Connecting to the bridge");
		$bridge = new bridgeclient();
		$bridge->connect();

		logi("Connecting to the database");
        $dblink = new \PDO(DATABASE_FILE);

		// Main switch
		switch ($command) {
		    case "retrieve_settings":
        		retrieve_settings();
        		break;
    		case "version":
	            send_output(VERSION);
        		break;
    		case "log_filename":
	            send_output(PHP_LOG_FOLDER);
        		break;
        	default:
        		$logString = "Invalid command specified";
        		send_output($logString);
        		logi($logString);		
		}
		
		logi("Disconnecting the bridge");
		$bridge->disconnect();

		logi("Disconnecting from the database");
        $dblink = null;

        logi("Evaluation of command '" . $command . "' completed");
        
/*

        if ($task === "publish_settings") {
            publish_settings($link);
        }

        if ($task === "save_program") {

            $program = $_POST["program"];
            $dia = $_POST["day"];
            save_program($program, $link, $dia);

        }


        if ($task === "load_program") {

            $day = $_POST["day"];
            logi("Loading program day " . $day);
            $query = "select * from programs_detail where week_day=" . $day . " order by hour asc";
            $result = do_query2($query, $link);
            send_output($result);

        }

        if ($task === "check_program") {
            check_program($link);
        }

        //Saves the actual temperature in the temperatures table
        if ($task === "historical") {
            historical($link);
        }

        if ($task === "create_settings1Time") {
            create_settings1Time($link);
        }


        //Updates the IP on NO_IP
        if ($task === "update_ip") {
            update_ip($ip);

        }

        if ($task === "process_historical_data") {
            process_historical_data($link);
        }


        if ($task === "v") {
            send_output(VERSION);
        }


        if ($task === "clean") {
            clean_tables($link);
        }


        if ($task === "update_setting") {
            $key = $_POST["key"];
            $value = $_POST["value"];
            update_setting($link, $key, $value);
        }

        if ($task === "temps_between") {
            $start = $_POST["start"];
            $end = $_POST["end"];
            temps_between($start, $end, $link, $logString);
        }


        if ($task === "stats_per_day") {
            $start = $_POST["start"];
            $end = $_POST["end"];
            $query = "select * from statistics ";
            if ($start != "" && $end != "") {
                $query .= " where date between '" . $start . "' and '" . $end . "' ";
            }

            $query .= " order by date asc";

            $result = do_query2($query, $link);
            send_output($result);
            //$logString .= "Done";

        }


        if ($task === "stats_grouped") {
            //W to group by week, m to group by month, and Y to group by year

            $g = $_POST["group"];

            if ($g === "W" || $g === "w") {
                $g = "%W/%Y";
            }
            if ($g === "m" || $g === "M") {
                $g = "%m/%Y";
            }

            if ($g === "y" || $g === "Y") {
                $g = "%Y";
            }


            //"select strftime('%" . $g . "',date) as date, "
            $query = "select date, "
                . " sum (system_on) as system_on, "
                . " sum(heating) as heating, "
                . " avg (average_temp)as average_temp, "
                . " avg(average_desired_temp) as average_desired_temp "
                . " from statistics "
                . " group by strftime('" . $g . "',date) "
                . " order by date asc ";
            $result = do_query2($query, $link);
            send_output($result);
            //$logString .= "Done";
        }
        */

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

function retrieve_settings($link)
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

function getDataArray($query)
{
	if($dblink == null) {
		logi("Invalid database link");
		return null;
	}
    $handle = $dblink->prepare($query);
    $handle->execute();
    return $handle->fetchAll(PDO::FETCH_ASSOC);

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
 * @param $program
 * @param $link
 * @param $dia
 * @return array
 */
function save_program($program, $link, $dia)
{
    $array = json_decode($program);

    $query = "delete from programs";
    $link->exec($query);
    $query = "delete from programs_detail where week_day=?";
    $handle = $link->prepare($query);
    $handle->bindValue(1, $dia);
    $handle->execute();

    $query = "INSERT INTO  programs (id,description) VALUES (?,?)";
    $handle = $link->prepare($query);
    $handle->bindValue(1, "1");
    $handle->bindValue(2, "Prueba");
    $handle->execute();
    $id_programa = $link->lastInsertId();
    //Recorremos el array y hacemos los inserts pertinentes.

    //$contador_dia = 0;
    $query = "INSERT INTO  programs_detail (id_program,week_day,hour,desired_temp)VALUES (?,?,?,?)";
    $handle = $link->prepare($query);


    $contador_hora = 0;
    foreach ($array as $temperatura) {
        $handle->bindValue(1, $id_programa);
        $handle->bindValue(2, $dia);
        $handle->bindValue(3, $contador_hora);
        $handle->bindValue(4, $temperatura);
        $handle->execute();
        $contador_hora++;
    }
    logi("Day " . $dia . " saved.");
}

/**
 * @param $link
 * @return array
 */
function check_program($link)
{
//Recuperamos de la tabla de parametros, si se ha establecido el modo de funcionamiento en auto o manual.
    $query = "select value from settings where key='work_mode'";
    $handle = $link->prepare($query);
    $handle->execute();
    $fila = $handle->fetch();
    $valor = $fila["value"];


    if ($valor === "auto") {
        //TODO: pendiente_ solo enviar variaciones si es diferente de el estado actual del sistema.

        //Si es automatico, recuperamos la hora de 0 a 23 y el dia de la semana actual de 0  a 6
        $diaDeLaSemana = date("N") - 1;
        $horaDelDia = date("H");
        logi("Task: Check program. Status: Auto. Day " . $diaDeLaSemana . ", hour " . $horaDelDia);
        //Consultamos en la tabla de programaciones si existe un registro para este dia-hora. En caso afirmativo, insertamos el dato apropiado en la memoria intermedia
        $query = "select desired_temp from programs_detail where week_day=" . $diaDeLaSemana . " and hour=" . $horaDelDia;
        $handle = $link->prepare($query);
        $handle->execute();
        $fila = $handle->fetch();
        $temperaturaDeseada = 0;
        if ($fila) {
            $temperaturaDeseada = $fila["desired_temp"];
        } else {
            logi("Check program, no program fot this day/hour");
        }

        $client = new bridgeclient();


        //Si la temperatura es mayor que cero, insertamos la temperatura deseada y el on en la memoria intermedia
        $estado = "0";
        if ($temperaturaDeseada > 0) {
            $client->put("desired_temp", $temperaturaDeseada);
            //$a = "http://localhost/data/put/desired_temp/" . $temperaturaDeseada;

            //do_Curl($a);
            $estado = "1";

        }
        logi("Task: Check program, desired_temp: " . $temperaturaDeseada);
        //Averiguamos el estado del sistema, on u off
        //$system_on = do_curl("http://localhost/data/get/system_on");
        $system_on = $client->get("system_on");
        //Solo cambiamos el estado, si el nuevo estado es diferente del anterior
        if ($system_on != $estado) {
            $client->put("pending_command", $estado);
            $client->put("pending_command_client", "PROGRAM");
            /*$a = "http://localhost/data/put/pending_command/" . $estado;
            do_Curl($a);
            $a = "http://localhost/data/put/pending_command_client/PROGRAM";
            do_Curl($a);*/
        }
    } else {
        logi("Task: Check program. Status: manual");
        //    $estado = "0";
    }


}

/**
 * @param $link
 * @return array
 */
function historical($link)
{
    $client = new bridgeclient();

    $query = "INSERT INTO  temperatures (node0,node1,node2,node3,node4,node5,system_on,heating,desired_temperature)VALUES (?,?,?,?,?,?,?,?,?)";
    $handle = $link->prepare($query);

    $node0_temp = null;
    $node0_date = null;
    $node0_name = null;

    $node1_temp = null;
    $node1_date = null;
    $node1_name = null;

    $node2_temp = null;
    $node2_date = null;
    $node2_name = null;

    $node3_temp = null;
    $node3_date = null;
    $node3_name = null;

    $node5_temp = null;
    $node5_date = null;
    $node5_name = null;

    $node4_temp = null;
    $node4_date = null;
    $node4_name = null;


    for ($a = 0; $a < 6; $a++) {
        $temp = $client->get("node" . $a . "_temp");
        $name = $client->get("node" . $a . "_name");

        $date = $client->get("node" . $a . "_date");

        ${"node" . $a . "_temp"} = ($temp === "None") ? null : $temp;
        ${"node" . $a . "_name"} = ($name === "None") ? null : $name;
        ${"node" . $a . "_date"} = ($date === "None") ? null : $date;

        $temp_string = "Node " . $a . ": " . ${"node" . $a . "_temp"} . ". Name: " . ${"node" . $a . "_name"} . ". Date: " . ${"node" . $a . "_date"};
        logi($temp_string);

    }


    $system_on = $client->get("system_on");
    $heating = $client->get("heating");
    $desired_temp = $client->get("desired_temp");

    //Sometimes the retrieved data is not correct (for example, instead system_on = true or false, it retrieves system_on=20.0
    //Because of that, we need to validate the date before the insert

    $ok = true;
    if (!is_numeric($node0_temp) || !is_numeric($desired_temp)) {
        $ok = false;
    }


    //The other nodes the value can be a digit or null, and the name and the date must be non null
    for ($a = 1; $a < 6; $a++) {

        if (!is_numeric(${"node" . $a . "_temp"}) || ${"node" . $a . "_date"} == null || ${"node" . $a . "_name"} == null) {
            ${"node" . $a . "_temp"} = null;
        }
    }

    if ($system_on != "true" && $system_on != "false") {
        $ok = false;
    }

    if ($heating != "true" && $heating != "false") {
        $ok = false;
    }


    if ($ok) {
        $handle->bindValue(1, $node0_temp);
        $handle->bindValue(2, $node1_temp);
        $handle->bindValue(3, $node2_temp);
        $handle->bindValue(4, $node3_temp);
        $handle->bindValue(5, $node4_temp);
        $handle->bindValue(6, $node5_temp);
        $handle->bindValue(7, $system_on);
        $handle->bindValue(8, $heating);
        $handle->bindValue(9, $desired_temp);
        $handle->execute();
        send_output("OK");
        logi("Data ok. Node 0: ".$node0_temp." Node 1: ".$node1_temp." Node 2: ".$node2_temp." Node 3: ".$node3_temp." Node 4: ".$node4_temp." Node 5: ".$node5_temp);
    } else {
        //Maybe the next time we will have more luck
        logi("Bad data in get from arduino");
    }
}

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

/**
 * @param $start
 * @param $end
 * @param $link
 * @param $log_string
 * @return array
 */
function temps_between($start, $end, $link)
{
    $query = "select temperature_date,node0,node1,node2,node3,node4,node5,system_on,desired_temperature,heating from temperatures ";
    //$query .= " where desired_temperature>0 and desired_temperature is not null ";
    $query .= " where desired_temperature>0 and desired_temperature is not null ";
    if ($start != "" && $end != "") {
        $query .= " and temperature_date between '" . $start . "' and '" . $end . "' ";
    }
    $query .= " order by id asc ";

    $result = do_query2($query, $link);
    send_output($result);

}