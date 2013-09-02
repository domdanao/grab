<?php

##################################################
// GRAB UNLI
// Subscriber is buying unli grabs for 24 hrs
// Possible messages:
// GRAB UNLI			:	buy unli; if one running game only, do charging; if more than one, send advise to GRAB UNLI <ITEM>
// GRAB UNLI <ITEM>		:	buy unli, do charge if item matches
// GRAB UNLI <CHECK>	:	check how much unli time is left


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
$val = '250';


##################################################
// DEFAULT CHARGING BEHAVIOR
$do_charge = TRUE;


##################################################
// Charging
$SENDCHARGE['mo_id'] = $_REQUEST['mo_id'];
$SENDCHARGE['parameters']['CSP_Txid'] = $tran_id;
$SENDCHARGE['parameters']['SUB_C_Mobtel'] = $sender;
$SENDCHARGE['parameters']['CSP_A_Keyword'] = $CHG_VALS[$val];	// This statement makes default charge 2.50


##################################################
// Let's check grab bag
$grabs = grab_bag( $timenow, $_REQUEST['operator'], $dblink );
$bilang = count( $grabs );


##################################################
if ( empty( $_REQUEST['others'] ) ) 	{
	// Subscriber did not send item with message
	// GRAB UNLI
	if ( $bilang > 1 ) {
		// There is one more item in the grab bag
		// Set up the message
		$msg = "GRAB: May $bilang items ngayon sa Grab Bag.\n\n";
		$item = '';
		while ( $grabs ) {
			foreach ( $grabs as $k => $v ) {
				if ( $k == 'keyword') $item = strtoupper( $v );
				$msg .= "To unli-grab " . $item . ", send GRAB UNLI " . $item . " to $INLA.\n";
			}
		}
		$msg .= $REGMSG;
	} elseif ( $bilang == 1 ) {
		// Only one item in grab bag
		// Register subscriber for grab
		// Set up charging, P20
		$val = '2000';
		$SENDCHARGE['parameters']['CSP_A_Keyword'] = $CHG_VALS[$val];
		// Send the charge
		if ( $chg = charge_request( $SENDCHARGE ) ) {
			// Charge success
			// Get item
			$item = '';
			while ( $grabs ) {
				foreach ( $grabs as $k => $v ) {
					if ( $k == 'keyword' ) $item = strtoupper( $v );
				}
			}
			
			// End time of unli grab
			$unli_time_end = date( "M j, Y H:i:s", $chg['time_recd'] );
			
			// Set up the message
			$msg = "GRAB: Unli na grabs mo sa $item, hanggang $unli_time_end.\n\nTo grab it, txt GRAB <item> to $INLA." . $REGMSG;
		} else {
			// Charge failure
			$msg = "GRAB: Sorry, kulang balance mo sa iyong account.";
		}
	} else {
		// No item in grab bag
		$msg = "GRAB:\nChill ka lang. Check back later to know when we have stuff in the Grab Bag. $BP1";
	}
} else {
	// Subscriber sent GRAB UNLI ITEM
	// Check if there is a match, search grabs array
	$match = FALSE;
	while ( $grabs ) {
		foreach ( $grabs as $k => $v ) {
			if ( $k == 'keyword' ) {
				// Match
				if ( $v == $param ) $match = TRUE;
				// End loop if already TRUE
				break 2;
			}
		}	
	}
	
	if ( $match ) {
		// Valid request
		// Register subscriber for grab
		// Set up charging, P20
		$val = '2000';
		$SENDCHARGE['parameters']['CSP_A_Keyword'] = $CHG_VALS[$val];
		// Send the charge
		if ( $chg = charge_request( $SENDCHARGE ) ) {
			// Charge success
			// Get item
			$item = '';
			while ( $grabs ) {
				foreach ( $grabs as $k => $v ) {
					if ( $k == 'keyword' ) $item = strtoupper( $v );
				}
			}
			
			// End time of unli grab
			$unli_time_end = date( "M j, Y H:i:s", $chg['time_recd'] );
			
			// Set up the message
			$msg = "GRAB: Unli na grabs mo sa $item, hanggang $unli_time_end.\n\nTo grab it, txt GRAB <item> to $INLA." . $REGMSG;
		} else {
			// Charge failure
			$msg = "GRAB: Sorry, kulang balance mo sa iyong account.";
		}
		
	} else {
		// Invalid request
		// Send charge
		if ( charge_request( $SENDCHARGE ) ) {
			$msg = "Sorry, u sent an invalid request.\n\nPara unli grabs mo for 24hrs, send GRAB UNLI <item> to $INLA. $BP1";
		}
	}
}


##################################################
// Send the SMS MT
$SENDSMS['parameters']['SMS_MsgTxt'] = $msg;
sms_mt_request( $SENDSMS );


print "\n\n\n---------------\nMESSAGE SENT:\n$msg---------------\n\n\n";


##################################################
exit();
?>