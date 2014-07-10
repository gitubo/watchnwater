<?php

	$action = $_POST['action'];
	$username = $_POST['username'];
	$password = $_POST['password'];
	
	if($action = 'login' && $username == 'wnw' && $password == 'wnw'){
		session_start();
		$_SESSION['sessionID'] = session_id();
		$output = array('status' => true, 'message' => '');
	} else {
		$output = array(
			'status' => false,
			'message' => 'Invalid username or password');
	}

	echo json_encode($output);
	
?>