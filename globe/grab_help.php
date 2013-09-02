<?php

##################################################
// GRAB HELP
// Charge: 2.50

// AS OF August 21, 2013


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
$smsc_time = $_REQUEST['smsc_time'];
$main_key = $_REQUEST['keyword'];

// Reg message
$REGMSG = '';
if ( !is_registered( $sender ) ) {
	$REGMSG = $NOT_REG;
}


##################################################
// The HELP file
$SENDSMS['parameters']['SMS_MsgTxt'] = "Welcome to GRAB A GADGET PROMO! Available commands:\n" .
	"GRAB REG name/age/address – to be a member and buy items for P88 only\n" .
	"GRAB UNLI - get 24hrs unlimited grabs [P20/day]\n" .
	"GRAB <item> - grab an item\n" .
	"GRAB BAG - list the current items up for grabs all for 88 pesos!\n" .
	"ON GRAB - to subscribe to daily updates\n" .
	"GRAB TIME <item>  - know how much time u are holding the item\n" .
	"DTI XXXX Promo Pd 08/26/13-09/25/13\nCall 7065278 $BP1" .
	$REGMSG;


##################################################
// Set up charging (mandatory variables)
$SENDCHARGE['mo_id'] = $mo_id;
$SENDCHARGE['parameters']['CSP_Txid'] = $tran_id;
$SENDCHARGE['parameters']['SUB_C_Mobtel'] = $sender;
$SENDCHARGE['parameters']['CSP_A_Keyword'] = $CHG_VALS['250'];

// Send charge request
if ( charge_request( $SENDCHARGE ) ) {
	// Send the SMS
	sms_mt_request( $SENDSMS );
}

exit();

##################################################
?>