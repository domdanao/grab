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
$val = '250';


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
$SENDCHARGE['parameters']['mo_id'] = $_REQUEST['mo_id'];
$SENDCHARGE['parameters']['txid'] = $tran_id;
$SENDCHARGE['parameters']['mobtel'] = $sender;
$SENDCHARGE['parameters']['charge'] = $val;


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
		foreach ( $grabs as $row ) {
			$item = strtoupper( $row['keyword'] );
			$msg .= "To unli-grab " . strtoupper( $item ) . ", send GRAB UNLI " . $item . " to $INLA.\n";
		}
		$msg .= $REGMSG;
	} elseif ( $bilang == 1 ) {
		// Only one item in grab bag
		// Register subscriber for grab
		// Set up charging
		$val = '250';	// SET TO PROPER PRICE!!!
		$SENDCHARGE['parameters']['charge'] = $val;
		
		// Send the charge
		if ( $chg_info = charge_request( $SENDCHARGE ) ) {
			// Charging is successful
			if ( $update_or_insert = insert_or_update_unlisub_table( $grabs, $chg_info, $sender, $dblink ) ) {
				// Set up the message
				$upper_item = strtoupper( $item );
				$msg = "GRAB: Unli na grabs mo sa $upper_item, hanggang $unli_time_end.\n\nTo grab it, txt GRAB <item> to $INLA." . $REGMSG;
				$response['response'] = 'OK';
				$response['reason'] = 'Charge success ' . $val . '/';				
			} else {
				// This should never happen, but handle
			}
		} else {
			// Charge failure
			$msg = "GRAB: Sorry, kulang balance mo sa iyong account.";
			$response['response'] = 'NOK';
			$response['reason'] = 'Charge failed';
		}
	} else {
		// No item in grab bag
		$msg = "GRAB:\nChill ka lang. Check back later to know when we have stuff in the Grab Bag.";
	}
} else {
	// Subscriber sent GRAB UNLI ITEM
	// Check if there is a match, search grabs array
	$match = FALSE;
	$item = '';
	foreach ( $grabs as $row ) {
		if ( $row['keyword'] == $param ) {
			$match = TRUE;
			$item = $row['keyword'];
			break;	// End loop if already true
		}
	}
	
	if ( $match ) {
		// Valid request because there is a matching keyword
		// Register subscriber for grab
		// Set up charging, P15 (1500) or P20 (2000)
		$val = '250';	//// SET PROPER PRICE!!!
		$SENDCHARGE['parameters']['charge'] = $val;
		// Send the charge
		if ( $chg = charge_request( $SENDCHARGE ) ) {
			// Charge success
			if ( $update_or_insert = insert_or_update_unlisub_table( $grabs, $chg_info, $sender, $dblink ) ) {
				// Set up the message
				$msg = "GRAB: Unli na grabs mo sa " . strtoupper( $item ) . ", hanggang $unli_time_end.\n\nTo grab it, txt GRAB <item> to $INLA." . $REGMSG;
				$response['response'] = 'OK';
				$response['reason'] = 'Charge success ' . $val . '/';
			}
		} else {
			// Charge failure
			$msg = "GRAB: Sorry, kulang balance mo sa iyong account.";
			$response['response'] = 'NOK';
			$response['reason'] = 'Charge fail/';
		}	
	} else {
		// Invalid request
		$val = '250';
		if ( charge_request( $SENDCHARGE ) ) {
			$msg = "Sorry, u sent an invalid request.\n\nPara unli grabs mo for 24hrs, send GRAB UNLI <item> to $INLA.\n\nPara malaman kung ano pwede mo i-grab, send GRAB BAG to $INLA. $BP1";
			$response['response'] = 'NOK';
			$response['reason'] = 'Invalid request';
		}
	}
}


##################################################
// Send the SMS MT
$response['message'] = $SENDSMS['parameters']['message'] = $msg;
if ( sms_mt_request( $SENDSMS ) ) $response['reason'] .= 'SMS sent';

print json_encode( $response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

##################################################
exit();


##################################################
function insert_or_update_unlisub_table( $grabs, $chg_info, $sender, $dblink ) {
	$item = '';
	$gid = 0;
	foreach ( $grabs as $row ) {
		$item = $row['keyword'];
		$gid = $row['gid'];
	}
	$grab_bag_table = 'grab_' . $item . '_' . $gid;
	
	$end_time = null;
	
	if ( $unlisub = is_unlisub( $sender) ) {
		// If the sender is an unlisub, add one full day after end_time in unlisub table
		$end_time = date( "Y-m-d H:i:s", strtotime($unlisub['end_time'] . ' + 1 day') );
		$query = "UPDATE `unlisubs` SET `end_time` = '". $end_time ."' WHERE `msisdn` = '" . $sender . "' AND `grab_bag_table` = '" . $grab_bag_table . "'";
	} else {
		// Sender is not unlisub yet, so create a record
		$start_time = date( "Y-m-d H:i:s", $chg_info['time_recd'] );			
		$end_time = date( "Y-m-d H:i:s", strtotime($start_time . ' + 1 day') );
		$query = "INSERT INTO `unlisubs` SET `msisdn` = '" . $sender . "', `grab_bag_table` = '" . $grab_bag_table . "', `start_time` = '" . $start_time . "', `end_time` = '" . $end_time . "'";
	}
	$result = mysql_query( $query );
	if ( mysql_affected_rows() == -1 ) {
		return FALSE;
	} else {
		return array(
			'item'		=>	$item,
			'end_time'	=>	$end_time
		);
	}
}
?>