<?php

$dir = 'sqlite:/mnt/sda1/wnw/wnwdb.sqlite';
$db = new PDO($dir) or die("cannot open database");

//Fetch events from database as associative array
//$query = 'SELECT [date], temperature, humidity, pressure, soil_moisture, luminosity FROM sensors_log ORDER BY [date] DESC';
$query = 'SELECT [date],CAST(AVG(temperature) AS INTEGER),CAST(AVG(humidity) AS INTEGER),CAST(AVG(pressure) AS INTEGER),CAST(AVG(soil_moisture) AS INTEGER),CAST(AVG(luminosity) AS INTEGER) FROM sensors_log';
$query .= " GROUP BY strftime('%Y%m%d%H0', [date]) + strftime('%M', [date])/20";

if(isset($_POST['limit']) && $_POST['limit']<100){
	$query .= ' LIMIT '.$_POST['limit'];
} else {
	$query .= ' LIMIT 80';
}

$date = array();
$temperature = array();
$humidity = array();
$pressure = array();
$soilMoisture = array();
$luminosity = array();


date_default_timezone_set('UTC');

foreach ($db->query($query) as $row) {
	
	$_date = strftime("%Y-%m-%d %I:%M:%S%p",strtotime($row[0]));
	$date[] = $row[0];
	$_temperature = floatval($row[1])/100.0;
	$temperature[] = array($_date, $_temperature);
	
	$_humidity = floatval($row[2])/100.0;
	$humidity[] = array($_date, $_humidity);

	$_pressure = floatval($row[3])/100.0;
	$pressure[] = array($_date, $_pressure);

	$soilMoisture[] = $row[4];
	$luminosity[] = $row[5];
}

if(count($temperature) > 0){
    $data = array('success'=> true,
    			  'message'=>'',
    			  'itemsNumber' => count($temperature),
    			  'temperature' => $temperature,
    			  'humidity' => $humidity,
    			  'pressure' => $pressure,
    			  'soilMoisture' => $soilMoisture,
    			  'luminosity' => $luminosity
    			  );
    echo json_encode($data);
} else {
    $data = array('success'=> false,
    			  'message'=>'No data found');
    echo json_encode($data);
}

?>
