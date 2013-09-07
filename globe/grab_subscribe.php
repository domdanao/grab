<?php

##################################################
// GRAB ON or GRAB OFF



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
// INITIALIZE RESPONSE
$response = array(
	'response'	=>	0,
	'reason'	=>	'',
	'message'	=>	'',
	'charge'	=>	0
);


##################################################
// HTTP Request Variables
$SENDSMS['parameters']['mo_id'] = $mo_id = $_REQUEST['mo_id'];
$SENDSMS['parameters']['mobtel'] = $sender = $_REQUEST['sender'];
$SENDSMS['parameters']['txid'] = $tran_id = $_REQUEST['tran_id'];
$smsc_time = $_REQUEST['smsc_time'];
$main_key = $_REQUEST['keyword'];
$other_param = $_REQUEST['param'];


##################################################
// SendSMS message
$msg = '';


##################################################
// Subscriber may not un/subscribe if not registered
if ( !is_registered( $sender ) ) {
	// This is an error scenario, so charge
	// Set up charging (mandatory variables)
	$SENDCHARGE['parameters']['mo_id'] = $mo_id;
	$SENDCHARGE['parameters']['txid'] = $tran_id;
	$SENDCHARGE['parameters']['mobtel'] = $sender;
	$SENDCHARGE['parameters']['charge'] = $charge_val;	

	// Send charge request
	if ( charge_request( $SENDCHARGE ) ) {
		// Send the SMS
		$response['response'] = 'OK';
		$response['reason']	= 'Charge successful ' . $charge_val . '/';
		$response['charge'] = $charge_val;
		$msg = $SENDSMS['parameters']['message'] = "GRAB: Ooops! Register ka muna for free para makasali. Txt GRAB REG NAME/AGE/ADDRESS to 2889. Ex. GRAB REG MARK SY/25/8 Apo St, QC";
		$response['message'] = $msg;
		if ( sms_mt_request( $SENDSMS ) ) $response['reason'] .= 'SMS sent';
	}
	print json_encode( $response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	exit();
}


##################################################
// UNSUBSCRIBE
if ( $other_param == 'off' ) {
	// Subscriber wants to stop subscription
	// He explicitly sent OFF

	if ( is_alert_sub( $sender, $dblink ) ) {
		// Yes he is subscribed to alerts
		// Deactivate
		$remove = mysql_query( "UPDATE `members` SET `alert` = 0 WHERE `msisdn` = '" . $sender . "'" );
		if ( mysql_affected_rows() !== -1 ) {
			// Success removing alert flag
			$msg = "Di ka na subscribed sa GRAB UPDATES. Para mag-subscribe at makareceive ng daily updates tungkol sa longest time grabber ng item, text ON GRAB to $INLA. $BP1";
		} else {
			// Did not succeed removing
			// Must not happen
			//$msg = "We're sorry. A system error occurred. Please report this to your mobile operator. [DB1]";
			// $logthis .= $runtime . "[ERROR] Failed removing subscriber <$sender> from auction_alerts.\n";
		}
	} else {
		// No, he is not subscribed to alerts
		$msg = "Di ka pa subscribed sa GRAB UPDATES. Para mag-subscribe at makareceive ng daily updates tungkol sa longest time grabber ng item, text ON GRAB to $INLA. $BP1";
	}
}


##################################################
// SUBSCRIBE
elseif ( $other_param == 'on' ) {
	// Subscriber explicitly sent ON

	// Is the subscriber already in the auction_alerts table?
	if ( is_alert_sub( $sender, $dblink ) ) {
		// Sub is already subscribed
		// Charge 2.50 as this is an error scenario
		$msg = "No need to subscribe again kasi subscribed ka na sa daily updates ng Grab. For more info txt GRAB HELP to $INLA. $BP1 ";
		$do_charge = TRUE;
	} else {
		// Not subscribed
		// Then we subscribe him to the alerts service
		$insert = mysql_query( "UPDATE members SET alert = 1 WHERE msisdn = '" . $sender . "'" );
		if ( mysql_affected_rows() !== -1 ) {
			// Success inserting subscriber in auction_alerts table
			$msg = "Ayos! Subscribed ka na sa GRAB UPDATES! Makaka-receive ka na ng daily updates on new grab items, ur grab time, grab tips and other Club news! Para mag-unsubscribe,txt STOP GRAB to $INLA for free.";
		} else {
			// $logthis .= $prepend . " Failed inserting subscriber <$sender>.\n";
		}
	}
}



##################################################
// Finish the program, charge/send, or send only

$SENDSMS['parameters']['message'] = $msg;

if ( $do_charge ) {
	// Set up charging (mandatory variables)
	$SENDCHARGE['parameters']['mo_id'] = $mo_id;
	$SENDCHARGE['parameters']['txid'] = $tran_id;
	$SENDCHARGE['parameters']['mobtel'] = $sender;
	$SENDCHARGE['parameters']['charge'] = $charge_val;

	// Send charge request
	if ( charge_request( $SENDCHARGE ) ) {
		$response['response'] = 'OK';
		$response['reason'] = 'Charge successful ' . $charge_val . '/';
		$response['message'] = $msg;
		$response['charge'] = $charge_val;
		// Send the SMS
		if ( sms_mt_request( $SENDSMS ) ) $response['reason'] .= 'SMS sent';
	}
} else {
	// No charging necessary, just send the SMS
	$response['response'] = 'OK';
	$response['message'] = $msg;
	$response['charge'] = 0;
	if ( sms_mt_request( $SENDSMS ) ) $response['reason'] .= 'SMS sent';
}

print json_encode( $response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

##################################################
exit();
?>