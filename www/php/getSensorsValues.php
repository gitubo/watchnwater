<?php

$dir = 'sqlite:/mnt/sda1/wnw/wnwdb.sqlite';
$db = new PDO($dir) or die("cannot open database");

//Fetch events from database as associative array
$query = 'SELECT [date], temperature, humidity, pressure, soil_moisture, luminosity  FROM sensors_log WHERE [date] = (SELECT MAX([date]) from sensors_log)';
 
// Iterate through the results and pass into JSON encoder //
foreach ($db->query($query) as $row) {
    $data = array('success'=> true,
    			  'message'=>'',
    			  'date' => $row[0],
    			  'temperature' => $row[1],
    			  'humidity' => $row[2],
    			  'pressure' => $row[3],
    			  'soilMoisture' => $row[4],
    			  'luminosity' => $row[5]);
    echo json_encode($data);
}


?>
