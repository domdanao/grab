<?php

##################################################
// GRAB TIME
// Subscriber wants to inquire how much his grab time is on a particular item
// GRAB TIME IPHONE

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
$param = strtolower( $_REQUEST['param'] );
$others = trim( $_REQUEST['others'] );
$timenow = date( "Y-m-d H:i:s", time() );


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
$do_charge = TRUE;


##################################################
// Check the currently running grab games

$running = grab_bag( $timenow, $_REQUEST['operator'], $dblink );

if ( $num = count( $running ) ) {
	$grab_entries = array();
	$grab_item_entry = array();

	foreach ( $running as $row ) {
		// Figure out the table name
		$table = "grab_".$row['keyword']."_".$row['gid'];
		// See if sub has grab
		$grabtime = grab_time( $sender, $table, $dblink );
		if ( $grabtime ) {
			if ( !empty( $_REQUEST['others'] ) ) {
				$grab_entries = array();
				$grab_item_entry[] = strtoupper( $row['keyword'] ) . "|" . duration_out( duration_find( $grabtime ) );
				break;
			} else {
				$grab_entries[] = strtoupper( $row['keyword'] ) . "|" . duration_out( duration_find( $grabtime ) ) . "\n";
			}
		}
	}

	##################################################
	// Go through the arrays
	if ( count( $grab_item_entry ) ) {
		$parts = explode( "|", $grab_item_entry );
		$msg = "GRAB: " . $parts[1] . " mo nang hawak ang " . $parts[0] . "\n\nPwede mo ‘to mbili for P88 only basta ikaw ang pnkamtagal na may hawak nito!\n\nFor more info txt HELP GRAB to $INLA. $BP1";
	} elseif ( count( $grab_entries ) ) {
		$msg = "GRAB: Heto ang grab time record mo for the ff grab items:\n\n";
		foreach( $grab_entries as $bomba ) {
			$parts = explode( "|", $bomba );
			$msg .= $parts[0] . ": " . $parts[1] . "\n";
		}
	} else {
		$msg = "GRAB: Wala ka pang na-grab na item. Text GRAB BAG to $INLA pa makita mo mga grab items. $BP1";
	}
} else {
	$do_charge = FALSE;
	$msg = "GRAB:\nChill ka lang. Check back later to know when we have stuff in the Grab Bag. $BP1";
}


##################################################
// Finish the program, charge/send, or send only

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
?>