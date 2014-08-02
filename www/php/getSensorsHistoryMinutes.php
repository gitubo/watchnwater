<?php
$dir = 'sqlite:/mnt/sda1/wnw/wnwdb.sqlite';
$db = new PDO($dir) or die("cannot open database");

date_default_timezone_set('Europe/Rome');

$dateTo = date('Y-m-d H:i:s');
$dateFrom = date('Y-m-d H:i:s', strtotime($dateTo .' -1 day'));
$dateFrom = date('Y-m-d H:i:s', strtotime($dateFrom .' +1 hours'));

//Fetch events from database as associative array
$query =  "SELECT [date],temperature,humidity,pressure,soil_moisture,luminosity";
$query .= " FROM sensors_log where [date] BETWEEN '".date('Y-m-d H:i:s', strtotime($dateFrom))."' AND '".date('Y-m-d H:i:s', strtotime($dateTo))."' order by [date] asc";

$date = array();
$temperature = array();
$humidity = array();
$pressure = array();
$soilMoisture = array();
$luminosity = array();


foreach ($db->query($query) as $row) {
	$date[] = $row[0];
	$temperature[] = $row[1];
	$humidity[] = $row[2];
	$pressure[] = $row[3];
	$soilMoisture[] = $row[4];
	$luminosity[] = $row[5];
}

//We want to return one sample per hour
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
$_samples = 0;
for ($h=23; $h>=0; $h--){
	$moduleDate[] = date('Y-m-d H', strtotime($dateTo .' -' . $h . ' hours'));
}
$dateBlock = $moduleDate[0];
for ($i=0, $m=0; $i<count($date); $i++){
	if($dateBlock == date('Y-m-d H',strtotime($date[$i]))) {
		$_temperature += $temperature[$i];
		$_humidity    += $humidity[$i];
		$_pressure    += $pressure[$i];
		$_soilMoisture+= $soilMoisture[$i];
		$_luminosity  += $luminosity[$i];	
		$_samples++;
	} else if ($_samples > 0) {
		$_convertion = $_samples*100.0;
		$moduleTemperature[] = array($dateBlock, $_temperature/$_convertion);
		$moduleHumidity[] = array($dateBlock, $_humidity/$_convertion);
		$modulePressure[] = array($dateBlock, $_pressure/$_convertion);
		$moduleSoilMoisture[] = array($dateBlock, $_soilMoisture/$_convertion);
		$moduleLuminosity[] = array($dateBlock, $_luminosity/$_convertion);
		$m++;
		$dateBlock = $moduleDate[$m];
		$_temperature = 0;
		$_humidity    = 0;
		$_pressure    = 0;
		$_soilMoisture= 0;
		$_luminosity  = 0;
		$_samples = 0;
	} else {
		$moduleTemperature[] = array($dateBlock, null);
		$moduleHumidity[] = array($dateBlock, null);
		$modulePressure[] = array($dateBlock, null);
		$moduleSoilMoisture[] = array($dateBlock, null);
		$moduleLuminosity[] = array($dateBlock, null);
		$m++;
		$dateBlock = $moduleDate[$m];
	}
}

if(count($temperature) > 0){
    $data = array('success'=> true,
    			  'message'=>'',
    			  'dateFrom' => $dateFrom,
    			  'dateTo' => $dateTo,
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
