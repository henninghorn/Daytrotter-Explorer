<?php
session_start();
include('inc/engine.php');

$action = $_SERVER['QUERY_STRING'];

switch($action) {
	case 'getSessions':
		$sessions = getSessions();
		$json = array('sessions' => $sessions);
		break;
	
	case 'getSession':
		$session = getSession($_POST['session']);
		$json = $session;
		break;
		
	case 'updateHistory':
		updateHistory($_POST);
		exit();
		break;
}

print json_encode($json);
exit();

?>