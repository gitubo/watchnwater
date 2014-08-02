<?php
$dir = 'sqlite:/mnt/sda1/wnw/wnwdb.sqlite';
$db = new PDO($dir) or die("cannot open database");

date_default_timezone_set('Europe/Rome');

$dateTo = date('Y-m-d H:i:s');
$dateFrom = date('Y-m-d H:i:s', strtotime($dateTo .' -1 day'));

//Fetch events from database as associative array
$query =  "select [date], output from outputs_log where [date] > '".date('Y-m-d H:i:s', strtotime($dateFrom))."' order by [date]";

$date = array();
$output0 = array();
$output1 = array();
$output2 = array();
$output3 = array();

foreach ($db->query($query) as $row) {
	$date[] = $row[0];
	$output0[] = substr($row[1],0,1);
	$output1[] = substr($row[1],1,1);
	$output2[] = substr($row[1],2,1);
	$output3[] = substr($row[1],3,1);
}

$moduleDate = array();
$moduleOutput0 = array(); $moduleOutput0[] = array($date[0], (int)($output0[0]));
$moduleOutput1 = array(); $moduleOutput1[] = array($date[0], (int)($output1[0]));
$moduleOutput2 = array(); $moduleOutput2[] = array($date[0], (int)($output2[0]));
$moduleOutput3 = array(); $moduleOutput3[] = array($date[0], (int)($output3[0]));
for ($i=1; $i<count($date); $i++){
	if($output0[$i] != $output0[$i-1]){
		$moduleOutput0[] = array(date('Y-m-d H:i:s', strtotime($date[$i] .' -1 second')), (int)($output0[$i-1]));
		$moduleOutput0[] = array(date('Y-m-d H:i:s',strtotime($date[$i])), (int)($output0[$i]));
	} else {
		$moduleOutput0[] = array(date('Y-m-d H:i:s',strtotime($date[$i])), (int)($output0[$i]));
	}
	if($output1[$i] != $output1[$i-1]){
		$moduleOutput1[] = array(date('Y-m-d H:i:s', strtotime($date[$i] .' -1 second')), (int)($output1[$i-1]));
		$moduleOutput1[] = array(date('Y-m-d H:i:s',strtotime($date[$i])), (int)($output1[$i]));
	} else {
		$moduleOutput1[] = array(date('Y-m-d H:i:s',strtotime($date[$i])), (int)($output1[$i]));
	}
	if($output2[$i] != $output2[$i-1]){
		$moduleOutput2[] = array(date('Y-m-d H:i:s', strtotime($date[$i] .' -1 second')), (int)($output2[$i-1]));
		$moduleOutput2[] = array(date('Y-m-d H:i:s',strtotime($date[$i])), (int)($output2[$i]));
	} else {
		$moduleOutput2[] = array(date('Y-m-d H:i:s',strtotime($date[$i])), (int)($output2[$i]));
	}
	if($output3[$i] != $output3[$i-1]){
		$moduleOutput3[] = array(date('Y-m-d H:i:s', strtotime($date[$i] .' -1 second')), (int)($output3[$i-1]));
		$moduleOutput3[] = array(date('Y-m-d H:i:s',strtotime($date[$i])), (int)($output3[$i]));
	} else {
		$moduleOutput3[] = array(date('Y-m-d H:i:s',strtotime($date[$i])), (int)($output3[$i]));
	}
}

if(count($date) > 0){
    $data = array('success'=> true,
    			  'message'=>'',
    			  'dateFrom' => $dateFrom,
    			  'dateTo'   => $dateTo,
    			  'output0'  => $moduleOutput0,
    			  'output1'  => $moduleOutput1,
    			  'output2'  => $moduleOutput2,
    			  'output3'  => $moduleOutput3
    			  );
    echo json_encode($data);
} else {
    $data = array('success'=> false,
    			  'message'=>'No data found with QUERY <<'.$query.' >>');
    echo json_encode($data);
}

?>
