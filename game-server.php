<?php
/* -------------------------------------------------*/ error_log("arrived");
// include the hand-logic
require 'hand-logic.php';
// hand roomid player
$roomid = $_REQUEST['roomid'];
$roomSessId = 'GAMESESSION'.$roomid;
session_name($roomSessId);
setSession("ready", 0);

// get the current user
$db = new PDO('mysql:host=www.shop151.ierg4210.org;dbname=bigtwo', "bigtwoadmin", "csci4140");
$q = $db -> prepare("SELECT turn FROM game WHERE roomid = ?");
$q->execute(array($roomid));
$r = $q->fetch();
$current = $r['turn'];

// client submit his name
$instance = $_REQUEST['player'];

function checking(){
	global $current, $instance, $roomid;
	/* -------------------------------------------------*/ error_log("running checking function");
	/* -------------------------------------------------*/ error_log("sending the hand: " . $_REQUEST["hand"]);
	if ($current != $instance) return 'false';
	$hand = explode(',', $_REQUEST['hand']);

	// Call checkLogic in hand-logic.php
	$validity  = checkLogic($hand);
	/* -------------------------------------------------*/ error_log("checking finished, return: " . $validity);

	// Handle two cases for validity
	if ($validity){
		setSession("done", 1);
		setSession("hand", $_REQUEST["hand"]);
		/* -------------------------------------------------*/ error_log("returning true to call");
		return 'true';
	} else {
		setSession("hand", null);
		/* -------------------------------------------------*/ error_log("returning false to call");
		return 'false';
	}
}

function longpoll(){
	global $current, $instance, $roomid;
	if ($current == $instance) return longpoll_master();
	else return longpoll_slave();
}

function longpoll_master(){
	/* -------------------------------------------------*/ error_log("MASTER: longpoll_master called");
	global $current, $instance, $roomid;

	/* -------------------------------------------------*/ error_log("MASTER: Resetting variables");
	// Save the beginning time of the Master poll & reset the session hand
	$TOflag = 0;
	$startTime = time();
	$curtime = 0;
	setSession("timer", $startTime);
	setSession("hand", null);
	setSession("done", 0);
	setSession("ready", 1);
	$increment = 0;

	/* -------------------------------------------------*/ error_log("MASTER: Start loop to wait for TO or done");
	// Loop to check the 'done' parameter (TOflag = Timeout flag)
	do {
		usleep(100000);
		clearstatcache();

		//testing flag -- display in error log
		if ($curtime != time()){
			error_log("TIME: " . $increment++);
			$curtime = time();
		}
		//testing flag -- display in error log

		if ($startTime+20 <= time()) $TOflag = 1;
		if ($TOflag) setSession("done", 1);
		$e = getSession("done");
	} while ($e != 1) ;

	/* -------------------------------------------------*/ error_log("MASTER: Loop ended, update current user and preparing to end");
	switch ($current) {
		case 'north':
			$current = 'east';
			break;
		case 'east':
			$current = 'south';
			break;
		case 'south':
			$current = 'west';
			break;
		case 'west':
			$current = 'north';
			break;
	}
	$db = new PDO('mysql:host=www.shop151.ierg4210.org;dbname=bigtwo', "bigtwoadmin", "csci4140");
	$q = $db -> prepare("UPDATE game SET turn = ? WHERE roomid = ?");
	$q->execute(array($current, $roomid));

	// Read the session hand before return
	$returnHand = getSession("hand");
	setSession("ready", 0);

	/* -------------------------------------------------*/ error_log("MASTER: Close connection and return hand");
	/* -------------------------------------------------*/ error_log(print_r($returnHand, 1));
	return array('status' => 'proceed', 'hand' => $returnHand);	
}

function longpoll_slave(){
	/* -------------------------------------------------*/ error_log("SLAVE: longpoll_master called");
	global $current, $instance, $roomid;
	$roomSessId = 'GAMESESSION'.$roomid;
	session_name($roomSessId);
	
	/* -------------------------------------------------*/ error_log("SLAVE: Loop to wait for the ready flag");
	do {
		usleep(100000);
		clearstatcache();		
		$e = getSession("ready");
	} while ($e != 1);

	/* -------------------------------------------------*/ error_log("SLAVE: Start loop to wait for done: READY: ".$e);
	do {
		usleep(100000);
		clearstatcache();
		$e = getSession("done");
	} while ($e != 1) ;

	/* -------------------------------------------------*/ error_log("SLAVE: Loop ended, preparing to end");
	// Read the session hand before return
	$returnHand = getSession("hand");

	/* -------------------------------------------------*/ error_log("SLAVE: Close connection and return hand");
	return array('status' => 'proceed', 'hand' => $returnHand);	
}

function getSession($name){
	session_start();
	$s = $_SESSION[$name];
	session_write_close();
	return $s;
}

function setSession($name, $value){
	session_start();
	$_SESSION[$name] = $value;
	session_write_close();
	return true;
}

header('Content-Type: application/json');
echo json_encode(call_user_func($_REQUEST['action']));
