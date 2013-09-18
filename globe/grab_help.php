<?php

##################################################
// GRAB HELP
// Charge: 2.50


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
$smsc_time = $_REQUEST['smsc_time'];
$main_key = $_REQUEST['keyword'];

// Reg message
$REGMSG = '';
if ( !is_registered( $sender ) ) {
	$REGMSG = $NOT_REG;
}

##################################################
// INITIALIZE RESPONSE
$response = array(
	'response'	=>	0,
	'reason'	=>	'',
	'message'	=>	'',
	'charge'	=>	0
);


##################################################
// The HELP file
$message = $SENDSMS['parameters']['message'] = "Welcome to GRAB A GADGET PROMO! Available commands:\n" .
	"GRAB REG name/age/address - to be a member and buy items for P88 only\n" .
	"GRAB UNLI - get 24hrs unlimited grabs (P20/day)\n" .
	"GRAB UNLI CHECK - check your unlimited grab time\n" .
	"GRAB <item> - grab an item\n" .
	"GRAB BAG - list the current items up for grabs all for 88 pesos!\n" .
	"ON GRAB - to subscribe to daily updates\n" .
	"GRAB TIME <item>  - know how much time u are holding the item\n" .
	"DTI XXXX Promo Period 08/26/13-09/25/13\nCall 7065278 $BP1" .
	$REGMSG;


##################################################
// Finish the program

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
$response['message'] = $message;

print json_encode( $response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

exit();

##################################################
?>