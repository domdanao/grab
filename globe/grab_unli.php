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
			$update_or_insert = insert_or_update_unlisub_table( $grabs, $chg_info, $sender, $dblink );
			// Set up the message
			// Item
			$item_uppercase = strtoupper( $update_or_insert['item'] );
			// Time left
			$the_end_time = $update_or_insert['end_time'];
			$time_now = time();
			$time_left = $the_end_time - $time_now;
			$time_left_duration = duration_find( $time_left );
			$time_left_formatted = duration_out_plain($time_left_duration);
			// Message proper
			$intro_msg = 'Unlimited na grabs mo sa';
			$until_msg = 'hanggang';
			if ( $update_or_insert['unlisub'] ) {
				$intro_msg = 'Nadagdagan ng 24hrs unli-grab time mo sa';
				$until_msg = 'na ngayon hanggang';
			}
			$msg = "GRAB: $intro_msg " . $item_uppercase . ", $until_msg " . date( "M j, Y g:i:s A", $update_or_insert['end_time'] ) . ".\n\nYou have " . $time_left_formatted . " left.\n\nTo grab it, txt GRAB " . $item_uppercase . " to $INLA." . $REGMSG;
			$response['response'] = 'OK';
			$response['reason'] = 'Charge success ' . $val . '/';				
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
	/*
	The subscriber sent GRAB UNLI <SOMETHING>
	KEYWORDS_UNLI contains the words for unli-time check,
	and if there is a match in the array, we do balance check.
	Otherwise, we assume the subscriber was buying unli time
	for a particular item, eg, GRAB UNLI IPAD.
	*/
	if ( in_array( $others, $KEYWORDS_UNLI ) ) {
		// Subscriber sent GRAB UNLI CHECK or GRAB UNLI TIME, OR SOME EQUIVALENT
		// Is this inquiry free?
		if ( $unlisub = is_unlisub( $sender ) ) {
			// This block for handling subscribers who have unlimited grabs
			// Time left
			$end_time = strtotime( $unlisub['end_time'] );
			$time_now = time();
			$time_left = $end_time - $time_now;
			$time_left_duration = duration_find( $time_left );
			$time_left_formatted = duration_out_plain($time_left_duration);
			$msg = "GRAB: Unlimited ang grabs mo hanggang " . date( "M j, Y g:i:s A", $end_time ) . ".\n\nYou have " . $time_left_formatted . " left.\n\nPara malaman mo gadgets up for grabs, txt GRAB BAG to $INLA. $BP1" . $REGMSG;
		} else {
			// Sub is not an unli-grabber
			$item = '<item>';
			if ( $bilang == 1 ) {
				foreach( $grabs as $row ) {
					$item = $row['keyword'];
				}
			}
			$msg = "GRAB: Di pa unlimited ang grabs mo! Txt GRAB UNLI " . strtotupper( $item ) . " to $INLA to register for unlimited grabs. (P15 for 24hr validity)" . $REGMSG;
		}
	} else {
		// Subscriber sent GRAB UNLI <potential_item>
		// Check if there is a match, search grabs array
		$match = FALSE;
		$item = '';
		foreach ( $grabs as $row ) {
			if ( $row['keyword'] == $others ) {
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
			if ( $chg_info = charge_request( $SENDCHARGE ) ) {
				// Charging is successful
				$update_or_insert = insert_or_update_unlisub_table( $grabs, $chg_info, $sender, $dblink );
				// Set up the message
				// Item
				$item_uppercase = strtoupper( $update_or_insert['item'] );
				// Time left
				$the_end_time = $update_or_insert['end_time'];
				$time_now = time();
				$time_left = $the_end_time - $time_now;
				$time_left_duration = duration_find( $time_left );
				$time_left_formatted = duration_out_plain($time_left_duration);
				// Message proper
				$intro_msg = 'Unlimited na grabs mo sa';
				$until_msg = 'hanggang';
				if ( $update_or_insert['unlisub'] ) {
					$intro_msg = 'Nadagdagan ng 24hrs unli-grab time mo sa';
					$until_msg = 'na ngayon hanggang';
				}
				$msg = "GRAB: $intro_msg " . $item_uppercase . ", $until_msg " . date( "M j, Y g:i:s A", $update_or_insert['end_time'] ) . ".\n\nYou have " . $time_left_formatted . " left.\n\nTo grab it, txt GRAB " . $item_uppercase . " to $INLA." . $REGMSG;
				$response['response'] = 'OK';
				$response['reason'] = 'Charge success ' . $val . '/';				
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
	$return_this = array(
		'unlisub'	=>	FALSE,
		'item'		=>	'',
		'end_time'	=>	0
	);
	
	$gid = 0;
	foreach ( $grabs as $row ) {
		$return_this['item'] = $row['keyword'];
		$gid = $row['gid'];
	}
	$grab_bag_table = 'grab_' . $return_this['item'] . '_' . $gid;
		
	if ( $unlisub = is_unlisub( $sender) ) {
		// If the sender is an unlisub, add one full day after end_time in unlisub table
		$end_time = date( "Y-m-d H:i:s", strtotime($unlisub['end_time'] . ' + 1 day') );
		$return_this['end_time'] = strtotime( $end_time );
		$return_this['unlisub'] = TRUE;
		$query = "UPDATE `unlisubs` SET `end_time` = '". $end_time ."' WHERE `msisdn` = '" . $sender . "' AND `grab_bag_table` = '" . $grab_bag_table . "'";
	} else {
		// Sender is not unlisub yet, so create a record
		$start_time = date( "Y-m-d H:i:s", $chg_info['time_recd'] );			
		$end_time = date( "Y-m-d H:i:s", strtotime($start_time . ' + 1 day') );
		$return_this['end_time'] = strtotime( $end_time );
		$query = "INSERT INTO `unlisubs` SET `msisdn` = '" . $sender . "', `grab_bag_table` = '" . $grab_bag_table . "', `start_time` = '" . $start_time . "', `end_time` = '" . $end_time . "'";
	}
	$result = mysql_query( $query );
	if ( mysql_affected_rows() == -1 ) {
		return FALSE;
	} else {
		return $return_this;
	}
}
?>