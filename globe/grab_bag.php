<?php

##################################################
// LIST GRAB BAG ITEMS


// Just to be safe, if not via Globe, exit
if ( $_REQUEST['operator'] !== 'GLOBE' ) {
	exit();
}

require_once 'include/config.php';

##################################################
// Charge amount, Php2.50, expressed in centavos
$charge_val = 250;


##################################################
// DEFAULT CHARGING BEHAVIOR (PROD: TRUE)
$do_charge = TRUE;


##################################################
// HTTP Request Variables
$SENDSMS['parameters']['mo_id'] = $mo_id = $_REQUEST['mo_id'];
$SENDSMS['parameters']['mobtel'] = $sender = $_REQUEST['sender'];
$SENDSMS['parameters']['txid'] = $tran_id = $_REQUEST['tran_id'];
$main_key = $_REQUEST['keyword'];
$param = $_REQUEST['param'];
$others = trim( $_REQUEST['others'] );
$smsc_time = date( "Y-m-d H:i:s", time() );


##################################################
// Registration message
$REGMSG = '';
if ( !is_registered( $sender ) ) {
	$REGMSG = $NOT_REG;
}


##################################################
// INITIALIZE MESSAGE
$msg = '';
if ( is_unlisub( $sender ) ) $BP2 = '';


##################################################
// INITIALIZE RESPONSE
$response = array(
	'response'	=>	0,
	'reason'	=>	'',
	'message'	=>	'',
	'charge'	=>	0
);


##################################################
// Inspect the Grab Bag and compose messages
$running = grab_bag( $smsc_time, $_REQUEST['operator'], $dblink );
if ( $num = count( $running ) ) {
	if ( empty( $_REQUEST['others'] ) ) {
		// Sub just texted GRAB BAG
		$msg .= "GRAB-In the Grab Bag ryt now:\n\n";
		$count = 0;
		foreach ( $running as $row ) {
			$count++;
			if ( $count > 1) $msg .= "\n";
			if ( $num > 1 ) $msg .= "Item $count: ";
			$msg .= strtoupper( $row['keyword'] );
			if ( $num == 1 ) $msg .=  " - " . $row['adcopy'];
		}
		$msg .= "\n\nGrab an item you want by texting GRAB <ITEM> to $INLA. $BP2";
		$msg .= "\nFor more item info, txt GRAB BAG <ITEM> to $INLA. $BP1 " . $REGMSG;
	} else {
		// There is a third word in the request
		// GRAB BAG <item>
		$item = strtolower( $_REQUEST['others'] );
		$query = "SELECT * FROM `grab_bag` WHERE `keyword` = '$item' AND '$smsc_time' BETWEEN `grab_start` AND `grab_end`";
		$result = mysql_query($query);
		if ( mysql_num_rows( $result ) ) {
			$row = mysql_fetch_assoc( $result );
			// There is an item
			if ( is_unlisub( $sender ) ) $BP2 = '';
			$msg = "GRAB: " . strtoupper( $row['keyword'] ) . " - " . $row['info'] . "\n";
			$msg .= "\nText GRAB " . strtoupper( $row['keyword'] ) . " to $INLA at baka mabili mo ito for only P88! $BP2" . $REGMSG;
		} else {
			$msg = "GRAB: Walang ganyang item sa Grab Bag ngayon. $item, $smsc_time, $BP1" . $REGMSG;
		}
	}
} else {
	// No current items in grab bag. Must not happen.
	$do_charge = FALSE;
	$msg = "GRAB:\nChill ka lang. Check back later to know when we have stuff in the Grab Bag. $BP1 " . $REGMSG;
}


##################################################
// Finish the program

$SENDSMS['parameters']['message'] = $msg;

if ( $do_charge ) {
	// Set up charging (mandatory variables)
	$SENDCHARGE['parameters']['mo_id']	=	$mo_id;
	$SENDCHARGE['parameters']['txid']	=	$tran_id;
	$SENDCHARGE['parameters']['mobtel']	=	$sender;
	$SENDCHARGE['parameters']['charge']	=	$charge_val;

	// Send charge request
	if ( charge_request( $SENDCHARGE ) ) {
		// Compose response
		$response['charge'] = $charge_val;
		$response['reason'] .= 'Charge success ' . $charge_val . '/';
		// Send the SMS
		if ( sms_mt_request( $SENDSMS ) ) $response['reason'] .= 'SMS sent';
	}
} else {
	// No charging necessary, just send the SMS
	$response['charge'] = 0;
	if ( sms_mt_request( $SENDSMS ) ) $response['reason'] .= 'SMS sent';
}


##################################################
// If we reached here, we're cool, so send response
$response['response'] = 'OK';
$response['message'] = $msg;

print json_encode( $response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

exit();

##################################################
?>