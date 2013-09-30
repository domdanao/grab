<?php

// Test to see if P10 charging works

if ( $_REQUEST['operator'] !== 'GLOBE' ) {
	exit();
}

require_once 'include/config.php';


##################################################
// HTTP Request Variables

$SENDSMS['parameters']['mo_id'] = $mo_id = $_REQUEST['mo_id'];
$SENDSMS['parameters']['mobtel'] = $sender = $_REQUEST['sender'];
$SENDSMS['parameters']['txid'] = $tran_id = $_REQUEST['tran_id'];
$main_key = $_REQUEST['keyword'];
$param = strtolower( $_REQUEST['param'] );
$others = trim( $_REQUEST['others'] );
$timenow = date( "Y-m-d H:i:s" );


##################################################
// Registration message
$REGMSG = '';
if ( !is_registered( $sender ) ) {
	$REGMSG = $NOT_REG;
}


##################################################
// INITIALIZE MESSAGE, CHARGE VALUE
$msg = '';
$val = '500';


##################################################
// DEFAULT CHARGING BEHAVIOR
$do_charge = TRUE;


##################################################
// INITIALIZE RESPONSE
$response = array(
	'response'	=>	'',
	'reason'	=>	'',
	'message'	=>	'',
	'charge'	=>	$val
);


##################################################
// Charging
$SENDCHARGE['url'] = 'http://180.87.143.49:8888/grab/globe/sendcharge2.php';
$SENDCHARGE['parameters']['mo_id'] = $_REQUEST['mo_id'];
$SENDCHARGE['parameters']['txid'] = $tran_id;
$SENDCHARGE['parameters']['mobtel'] = $sender;
$SENDCHARGE['parameters']['charge'] = $val;


##################################################
// Content to send
$SENDCONTENT['url'] = $SENDSMS['url'];
$SENDCONTENT['parameters']['mobtel'] = $sender;
$SENDCONTENT['parameters']['mo_id'] = $mo_id;
$SENDCONTENT['parameters']['txid'] = $tran_id;
$SENDCONTENT['parameters']['message'] = get_content();

$chg_info = charge_request( $SENDCHARGE );


?>