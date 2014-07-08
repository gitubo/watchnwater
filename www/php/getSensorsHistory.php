<?php

$dir = 'sqlite:/mnt/sda1/wnw/wnwdb.sqlite';
$db = new PDO($dir) or die("cannot open database");

//Fetch events from database as associative array
$query = 'SELECT [date], temperature, humidity, pressure, soil_moisture, luminosity FROM sensors_log ORDER BY [date] DESC';

if(isset($_POST['limit']) && $_POST['limit']<50){
	$query .= ' LIMIT '.$_POST['limit'];
} else {
	$query .= ' LIMIT 20';
}

$date = array();
$temperature = array();
$humidity = array();
$pressure = array();
$soilMoisture = array();
$luminosity = array();

$temperatureMax = -100;
$temperatureMin = 100;
$humidityMax = -100;
$humidityMin = 100;
$pressureMax = -100;
$pressureMin = 1000000;


foreach ($db->query($query) as $row) {
	$date[] = $row[0];
	$_temperature = floatval($row[1]);
	$temperature[] = $_temperature;
	if($_temperature > $temperatureMax) {
		$temperatureMax = $_temperature;
	}
	if($_temperature < $temperatureMin) {
		$temperatureMin = $_temperature;
	}
	$_humidity = floatval($row[2]);
	$humidity[] = $_humidity;
	if($_humidity > $humidityMax) {
		$humidityMax = $_humidity;
	}
	if($_humidity < $humidityMin) {
		$humidityMin = $_humidity;
	}
	$_pressure = floatval($row[3])/100;
	$pressure[] = $_pressure;
	if($_pressure > $pressureMax) {
		$pressureMax = $_pressure;
	}
	if($_pressure < $pressureMin) {
		$pressureMin = $_pressure;
	}
	$soilMoisture[] = $row[4];
	$luminosity[] = $row[5];
}

if(count($temperature) > 0){
    $data = array('success'=> true,
    			  'message'=>'',
    			  'itemsNumber' => count($temperature),
    			  'date' => $date,
    			  'dateMin' => $date[count($date)-1],
    			  'dateMax' => $date[0],
    			  'temperature' => $temperature,
    			  'temperatureMax' => $temperatureMax,
    			  'temperatureMin' => $temperatureMin,
    			  'humidity' => $humidity,
    			  'humidityMax' => $humidityMax,
    			  'humidityMin' => $humidityMin,
    			  'pressure' => $pressure,
    			  'pressureMax' => $pressureMax,
    			  'pressureMin' => $pressureMin,
    			  'soilMoisture' => $soilMoisture,
    			  'luminosity' => $luminosity);
    echo json_encode($data);
} else {
    $data = array('success'=> false,
    			  'message'=>'No data found');
    echo json_encode($data);
}

?>
