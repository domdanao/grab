<?php

##################################################
// LIST GRAB BAG ITEMS


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


##################################################
// DEFAULT CHARGING BEHAVIOR
$do_charge = FALSE;


##################################################
// Inspect the Grab Bag and compose messages
$running = grab_bag( $smsc_time, $_REQUEST['operator'], $dblink );
if ( $num = count( $running ) ) {
	if ( !empty( $_REQUEST['others'] ) ) {
		// There is a third word in the request
		// GRAB BAG <item>
		$item = strtolower( $_REQUEST['others'] );
		$item_details = check_item( $item, $smsc_time, $dblink );
		if ( $item_details !== FALSE ) {
			// There is an item
			$msg = "GRAB: " . strtoupper( $item_details['keyword'] ) . " - " . $item_details['info'] . "\n";
			$msg .= "\nText GRAB " . strtoupper( $item_details['keyword'] ) . " to $INLA at baka mabili mo ito for only P88! $BP2" . $REGMSG;
		} else {
			$msg = "GRAB: Walang ganyang item sa Grab Bag ngayon. $BP1" . $REGMSG;
		}
	} else {
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
	}
} else {
	// No current items in grab bag. Must not happen.
	$do_charge = FALSE;
	$msg = "GRAB:\nChill ka lang. Check back later to know when we have stuff in the Grab Bag. $BP1 " . $REGMSG;
}


##################################################
// Finish the program

$SENDSMS['parameters']['SMS_MsgTxt'] = $msg;

if ( $do_charge ) {
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
} else {
	// No charging necessary, just send the SMS
	sms_mt_request( $SENDSMS );
}


print "\n\n\n$msg\n\n\n";


exit();


##################################################
// Check item in GRAB BAG
function check_item( $item, $smsc_time, $dblink ) {
	$item = strtolower($item);
	$query = "SELECT * FROM `grab`.`grab_bag`
		WHERE `grab`.`grab_bag`.`keyword` = '$item'
		AND '$smsc_time' BETWEEN `grab_start` AND `grab_end`";
	$result = mysql_query( $query );
	if ( mysql_num_rows( $result ) ) {
		return mysql_fetch_assoc( $result );
	} else {
		return FALSE;
	}
}
?>