<?php
$dir = 'sqlite:/mnt/sda1/wnw/wnwdb.sqlite';
$db = new PDO($dir) or die("cannot open database");

//Fetch events from database as associative array
//$query = 'SELECT [date], temperature, humidity, pressure, soil_moisture, luminosity FROM sensors_log ORDER BY [date] DESC';
$query = 'SELECT [date],temperature,humidity,pressure,soil_moisture,luminosity FROM sensors_log ORDER BY [date] DESC';

if(isset($_POST['limit']) && $_POST['limit']<1440){
	$query .= ' LIMIT '.$_POST['limit'];
} else {
	$query .= ' LIMIT 1440';  //1440 samples, 1 sample per second => 1440 seconds = 24 hours
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
	$temperature[] = $row[1];
	$humidity[] = $row[2];
	$pressure[] = $row[3];
	$soilMoisture[] = $row[4];
	$luminosity[] = $row[5];
}

//We want to return one sample per minute
$_MODULE_ = 60;
$samples = 1;
$moduleDate = array();
$moduleTemperature = array();
$moduleHumidity = array();
$modulePressure = array();
$moduleSoilMoisture = array();
$moduleLuminosity = array();
$_temperature = 0;
$_humidity    = 0;
$_pressure    = 0;
$_soilMoisture= 0;
$_luminosity  = 0;
for ($i=0; $i<count($date); $i++, $samples++){
	$_temperature += $temperature[$i];
	$_humidity    += $humidity[$i];
	$_pressure    += $pressure[$i];
	$_soilMoisture+= $soilMoisture[$i];
	$_luminosity  += $luminosity[$i];
	if ($samples == $_MODULE_){
		$_date = $date[$i-$_MODULE_+1];
		$moduleDate[] = $_date;
		$averageFactor = $_MODULE_*100.0;
		$moduleTemperature[] = array($_date, $_temperature/$averageFactor);
		$moduleHumidity[]    = array($_date, $_humidity/$averageFactor);
		$modulePressure[]    = array($_date, $_pressure/$averageFactor);
		$moduleSoilMoisture[]= array($_date, $_soilMoisture/$averageFactor);
		$mioduleLuminosity[] = array($_date, $_luminosity/$averageFactor);
		$samples=0;
		$_temperature = 0;
		$_humidity    = 0;
		$_pressure    = 0;
		$_soilMoisture= 0;
		$_luminosity  = 0;
	}
}

if(count($temperature) > 0){
    $data = array('success'=> true,
    			  'message'=>'',
    			  'itemsNumber' => count($moduleDate),
    			  'date' => $moduleDate,
    			  'temperature' => $moduleTemperature,
    			  'humidity' => $moduleHumidity,
    			  'pressure' => $modulePressure,
    			  'soilMoisture' => $moduleSoilMoisture,
    			  'luminosity' => $moduleLuminosity
    			  );
    echo json_encode($data);
} else {
    $data = array('success'=> false,
    			  'message'=>'No data found');
    echo json_encode($data);
}

?>
