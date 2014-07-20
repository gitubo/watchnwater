<?php

/**
 * Establish a database connection
 *
 */
 $dir = 'sqlite:/mnt/sda1/wnw/wnwdb.sqlite';
 $db = new PDO($dir) or die("cannot open database");

$stmt = $db->prepare("SELECT a.id as id, a.output as output, b.description as description, strftime('%H:%M', a.[from]) as startTime, a.duration as duration, a.weekdays_bitmask as weekdays, a.is_oneshot as isOneShot, a.is_forced as isForced FROM watering_plan a LEFT OUTER JOIN outputs b ON a.output=b.id WHERE a.is_valid = 1 ORDER BY a.output, a.[from]");
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

//Send output
if( !count($events) ) {
    $data = array('success'=> false,
    			  'message'=> 'no data retrieved');
    echo json_encode($data);
}	
$items = array();
foreach( $events as $event ) {
	$items[] = array('id'=> $event['id'],
					 'output'=> $event['output'],
					 'description'=> $event['description'],
					 'startTime'=> $event['startTime'],
    		  	  	 'duration' => $event['duration'],
    		  	  	 'weekdays' => $event['weekdays'],
			  	  	 'isOneShot' => $event['isOneShot'],
			  	  	 'isForced' => $event['isForced']);
}
$data = array('success'=> true,
   			  'message'=> '',
   			  'itemsNumber' => count($items),
   			  'wateringPlan' => $items);
echo json_encode($data);

?>
