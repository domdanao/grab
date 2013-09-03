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
// HTTP Request Variables
$SENDSMS['mo_id'] = $mo_id = $_REQUEST['mo_id'];
$SENDSMS['parameters']['SUB_C_Mobtel'] = $sender = $_REQUEST['sender'];
$SENDSMS['parameters']['SUB_R_Mobtel'] = $sender = $_REQUEST['sender'];
$SENDSMS['parameters']['CSP_Txid'] = $tran_id = $_REQUEST['tran_id'];
$timestamp = $_REQUEST['smsc_time'];
$main_key = $_REQUEST['keyword'];
$others = trim( $_REQUEST['others'] );

$msg = '';

##################################################
// Subscriber is already registered
// Send a message informing him/her
// Free message
if ( $reg = is_registered( $sender ) ) {
	$msg = "Registered ka na sa GRAB A GADGET Promo. No need to register again. I-grab mo na ang cool item ngayon for 88 pesos only! Txt GRAB BAG to $INLA para makita ang items up for grab! For more info, txt GRAB HELP to $INLA. $BP1";
	$SENDSMS['parameters']['SMS_MsgTxt'] = $msg;
	// Send message
	sms_mt_request( $SENDSMS );
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
		$SENDSMS['parameters']['SMS_MsgTxt'] = $msg;
		// Send message
		sms_mt_request( $SENDSMS );
		exit();
	}

	// The name to be registered
	$name = trim( $parts[0] );
	// The claimed age of member
	$age = trim( $parts[1] );
	// The address
	$address = trim( $parts[2] );

	// If one of the variables is empty
	if ( !$name or !$age or !$address ) {
		$msg = "GRAB: Sorry, incomplete ang registration mo. I-check mabuti ang format.Text GRAB REG <name/age/address> send to $INLA for free. For more info, txt HELP GRAB to $INLA. $BP1";
		$SENDSMS['parameters']['SMS_MsgTxt'] = $msg;
		// Send message
		sms_mt_request( $SENDSMS );
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
		$SENDSMS['parameters']['SMS_MsgTxt'] = $msg;
		// Send message
		sms_mt_request( $SENDSMS );
	}
} else {
	// Just sent GRAB REG
	$msg = "GRAB: Sorry, incomplete ang registration mo. I-check mabuti ang format.Text GRAB REG <name/age/address> send to $INLA for free. For more info, txt HELP GRAB to $INLA. $BP1";
	$SENDSMS['parameters']['SMS_MsgTxt'] = $msg;
	// Send message
	sms_mt_request( $SENDSMS );
}

exit();
##################################################
?>