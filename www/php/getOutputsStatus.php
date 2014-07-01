<?php

$dir = 'sqlite:/mnt/sda1/wnw/wnwdb.sqlite';
$db = new PDO($dir) or die("cannot open database");

//Fetch events from database as associative array
$query = 'SELECT [date], output  FROM outputs_log WHERE [date] = (SELECT MAX([date]) from outputs_log)';
 
$data = array('success'=> false,
    		  'message'=> 'No data found');
$output = array();
foreach ($db->query($query) as $row) {
	for ($i = 0; $i < strlen($row[1]); $i++)
		$output[$i] = $row[1][$i];	
}
if (sizeof($output) > 0) {
	$data = array('success'=> true,
   				  'message'=>'',
   				  'outputsNumber'=>sizeof($output),
  				  'output' => $output);
}
echo json_encode($data);

?>
