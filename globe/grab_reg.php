<?php

##################################################
// GRAB REG
// Registration handler
// No charge, unless error scenario


// Just to be safe, if not via Globe, exit
if ( $_REQUEST['operator'] !== 'GLOBE' ) {
	exit();
}

require_once 'include/config.php';


##################################################
// Charge amount, Php2.50, expressed in centavos
$charge_val = 250;


##################################################
// DEFAULT CHARGING BEHAVIOR (PROD: FALSE)
$do_charge = FALSE;


##################################################
// HTTP Request Variables
$SENDSMS['parameters']['mo_id'] = $mo_id = $_REQUEST['mo_id'];
$SENDSMS['parameters']['mobtel'] = $sender = $_REQUEST['sender'];
$SENDSMS['parameters']['txid'] = $tran_id = $_REQUEST['tran_id'];
$timestamp = $_REQUEST['smsc_time'];
$main_key = $_REQUEST['keyword'];
$others = trim( $_REQUEST['others'] );

$msg = '';


##################################################
// INITIALIZE RESPONSE
$response = array(
	'response'	=>	'',
	'reason'	=>	'',
	'message'	=>	'',
	'charge'	=>	0
);


##################################################
// Subscriber is already registered
// Send a message informing him/her
// Free message
if ( $reg = is_registered( $sender ) ) {
	$msg = "Registered ka na sa GRAB A GADGET Promo. No need to register again. I-grab mo na ang cool item ngayon for 88 pesos only! Txt GRAB BAG to $INLA para makita ang items up for grab! For more info, txt GRAB HELP to $INLA. $BP1";
	$SENDSMS['parameters']['message'] = $msg;
	// Send message
	sms_mt_request( $SENDSMS );
	// Compose and print reply to request
	$response['response'] = 'NOK';
	$response['reason'] = 'Already registered.'; 
	$response['message'] = $msg;
	print json_encode( $response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	exit();
}


##################################################
// Request parameters others have content
// Maybe valid, but let us test
if ( $others ) {
	$parts = explode( "/", $others );
	if ( count( $parts ) !== 3 ) {
		// Must always have 3 parts
		// Valid pattern: MARK SY/25/8 Apo St, QC
		$msg = "GRAB: Sorry, incomplete ang registration mo. I-check mabuti ang format.Text GRAB REG <name/age/address> send to $INLA for free. For more info, txt HELP GRAB to $INLA. $BP1";
		$SENDSMS['parameters']['message'] = $msg;
		// Send message
		sms_mt_request( $SENDSMS );
		// Compose and print reply to request
		$response['response'] = 'NOK';
		$response['reason'] = 'Invalid format'; 
		$response['message'] = $msg;
		print json_encode( $response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		exit();
	}

	// The name to be registered
	$name = trim( $parts[0] );
	// The claimed age of member
	$age = trim( $parts[1] );
	settype( $age, "integer" );
	if ( is_int( $age ) === FALSE ) $age = null;
	// The address
	$address = trim( $parts[2] );

	// If one of the variables is empty
	if ( !$name or !$age or !$address ) {
		$msg = "GRAB: Sorry, incomplete ang registration mo. I-check mabuti ang format.Text GRAB REG <name/age/address> send to $INLA for free. For more info, txt HELP GRAB to $INLA. $BP1";
		$SENDSMS['parameters']['message'] = $msg;
		// Send message
		sms_mt_request( $SENDSMS );
		// Compose and print reply to request
		$response['response'] = 'NOK';
		$response['reason'] = 'Parameters missing or invalid: $others'; 
		$response['message'] = $msg;
		print json_encode( $response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		exit();
	}

	##################################################
	// Note: script has not exited yet at this point
	// The registration message is thus valid
	// So we proceess it
	// Insert into members table
	$today = date( "Y-m-d H:i:s" );
	$query = "INSERT INTO `members` ( `msisdn`, `name`, `address`, `age`, `alert`, `joinedwhen` )
		VALUES
		( '$sender', '$name', '$address', '$age', '0', '$today' )";
	$result = mysql_query( $query, $dblink );
	if ( mysql_affected_rows() !== -1 ) {
		// Success
		$msg = "Member ka na ng GRAB A GADGET PROMO! Start grabbing cool items that u may buy for P88 only! $BP2\n\nFor more info, txt HELP GRAB to $INLA.\n\nThis msg is free.";
		$SENDSMS['parameters']['message'] = $msg;
		// Send message
		sms_mt_request( $SENDSMS );
		// Compose and print reply to request
		$response['response'] = 'OK';
		$response['reason'] = 'Successful registration'; 
		$response['message'] = $msg;
		print json_encode( $response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}
} else {
	// Just sent GRAB REG
	$msg = "GRAB: Sorry, incomplete ang registration mo. I-check mabuti ang format.Text GRAB REG <name/age/address> send to $INLA for free. For more info, txt HELP GRAB to $INLA. $BP1";
	$SENDSMS['parameters']['message'] = $msg;
	// Send message
	sms_mt_request( $SENDSMS );
	// Compose and print reply to request
	$response['response'] = 'NOK';
	$response['reason'] = 'Parameters missing'; 
	$response['message'] = $msg;
	print json_encode( $response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
}

exit();
##################################################
?>