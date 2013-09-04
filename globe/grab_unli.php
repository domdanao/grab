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
		// Set up charging
		$val = '250';	// SET TO PROPER PRICE!!!
		$SENDCHARGE['parameters']['charge'] = $val;
		// Send the charge
		if ( $chg = charge_request( $SENDCHARGE ) ) {
			// Charge success
			$grab_bag_table = 'grab_' . $grabs['keyword'] . '_' . $grabs['gid'];
			$start_time = date( "Y-m-d H:i:s", $chg['time_recd'] );
			$end_time = date( "Y-m-d H:i:s", strtotime($start_time . ' + 1 day') );
			
			$query = "INSERT INTO `unlisubs` SET `grab_bag_table` = '" . $grab_bag_table . "', `start_time` = '" . $start_time . "', `end_time` = '" . $end_time . "'";
			mysql_query( $query );
			
			// Get item
			$item = '';
			while ( $grabs ) {
				foreach ( $grabs as $k => $v ) {
					if ( $k == 'keyword' ) $item = strtoupper( $v );
				}
			}
			
			// End time of unli grab
			$unli_time_end = date( "M j, Y H:i:s", strtotime($start_time . ' + 1 day') );
			
			// Set up the message
			$msg = "GRAB: Unli na grabs mo sa $item, hanggang $unli_time_end.\n\nTo grab it, txt GRAB <item> to $INLA." . $REGMSG;
			$response['response'] = 'OK';
			$response['reason'] = 'Charge success';
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
		$val = '250';	//// SET PROPER PRICE!!!
		$SENDCHARGE['parameters']['charge'] = $val;
		// Send the charge
		if ( $chg = charge_request( $SENDCHARGE ) ) {
			// Charge success
			$grab_bag_table = 'grab_' . $grabs['keyword'] . '_' . $grabs['gid'];
			$start_time = date( "Y-m-d H:i:s", $chg['time_recd'] );
			$end_time = date( "Y-m-d H:i:s", strtotime($start_time . ' + 1 day') );
			
			$query = "INSERT INTO `unlisubs` SET `grab_bag_table` = '" . $grab_bag_table . "', `start_time` = '" . $start_time . "', `end_time` = '" . $end_time . "'";
			mysql_query( $query );
			
			// Get item
			$item = '';
			while ( $grabs ) {
				foreach ( $grabs as $k => $v ) {
					if ( $k == 'keyword' ) $item = strtoupper( $v );
				}
			}
			
			// End time of unli grab
			$unli_time_end = date( "M j, Y H:i:s", strtotime($start_time . ' + 1 day') );
			
			// Set up the message
			$msg = "GRAB: Unli na grabs mo sa $item, hanggang $unli_time_end.\n\nTo grab it, txt GRAB <item> to $INLA." . $REGMSG;
			$response['response'] = 'OK';
			$response['reason'] = 'Charge success';
		} else {
			// Charge failure
			$msg = "GRAB: Sorry, kulang balance mo sa iyong account.";
			$response['response'] = 'NOK';
			$response['reason'] = 'Charge fail';
		}
		
	} else {
		// Invalid request
		// Send charge
		if ( charge_request( $SENDCHARGE ) ) {
			$msg = "Sorry, u sent an invalid request.\n\nPara unli grabs mo for 24hrs, send GRAB UNLI <item> to $INLA. $BP1";
			$response['response'] = 'NOK';
			$response['reason'] = 'Invalid request';	
		}
	}
}


##################################################
// Send the SMS MT
$response['message'] = $SENDSMS['parameters']['message'] = $msg;
sms_mt_request( $SENDSMS );

print json_encode( $response, JSON_PRETTY_PRINT );

##################################################
exit();
?>