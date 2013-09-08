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
if ( empty( $_REQUEST['others'] ) ) {
	// Subscriber did not send item with message
	// GRAB UNLI
	if ( $bilang > 1 ) {
		// There is more than one item in the grab bag
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
		// Do sanity check first
		if ( $no_buy_item = sanity_check( $sender, $grabs ) ) {
			// Do not allow buying for this grab item
			list( $the_no_buy_item_gid, $the_no_buy_item_keyword, $the_buy_item_grab_end ) = explode( "/", $no_buy_item[0] );
			$formatted_grab_end = date( "M j, Y g:i:s A", strtotime( $the_buy_item_grab_end ) );
			$msg = "GRAB: Covered ka na hanggang matapos grabs for " . strtoupper( $the_no_buy_item_keyword ) . " until " . $formatted_grab_end . " so no need to buy more unli-grabs.\n\nTxt GRAB " . strtoupper( $the_no_buy_item_keyword ) . " to $INLA para mabili mo ito nang P88 only!";
			// Not the most elegant, but will work
			$response['charge'] = '0';
			$response['message'] = $SENDSMS['parameters']['message'] = $msg;
			if ( sms_mt_request( $SENDSMS ) ) $response['reason'] .= 'SMS sent';
			print json_encode( $response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
			exit();
		}
		
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
		$val = '0';
		$response['charge'] = $val;
		if ( $unlisub = is_unlisub( $sender ) ) {
			// This block for handling subscribers who have unlimited grabs
			// Time left
			if ( $bilang > 1 ) {
				$msg = "GRAB: U have unlimited grabs sa:\n\n";
				foreach ( $unlisub as $row ) {
					// Parse grab bag table
					list( $grabword, $keyword, $gid ) = explode( "_", $row['grab_bag_table'] );
					$end_time_stamp = strtotime( $row['end_time'] );
					$end_time_formatted = date( "n/j/Y h:i:s A", $end_time_stamp );
					$msg .=  strtotupper( $keyword ) . "(until $end_time_formatted)\n";
				}
				$msg .= $REGMSG;
			} elseif ( $bilang == 1 ) {
				list( $grabword, $keyword, $gid ) = explode( "_", $unlisub['grab_bag_table'] );
				
				$fin_end_time = 0;
				if ( $sanity = sanity_check( $sender, $grabs ) ) {
					list( $gid, $item, $grab_end ) = explode( "/", $sanity[0]);
					$fin_end_time = strtotime( $grab_end );
				} else {
					$fin_end_time = strtotime( $unlisub['end_time'] );
				}
				
				$time_now = time();
				$time_left = $fin_end_time - $time_now;
				$time_left_duration = duration_find( $time_left );
				$time_left_formatted = duration_out_plain($time_left_duration);
				$msg = "GRAB: Unlimited grabs mo sa " . strtoupper( $keyword ) . " until " . date( "M j, Y g:i:s A", $fin_end_time ) . ".\n\nYou have " . $time_left_formatted . " left.\n\nPara malaman mo gadgets up for grabs, txt GRAB BAG to $INLA. $BP1" . $REGMSG;
			}
		} else {
			// Sub is not an unli-grabber
			$item = '<item>';
			if ( $bilang == 1 ) {
				foreach( $grabs as $row ) {
					$item = $row['keyword'];
				}
			}
			$msg = "GRAB: Di pa unlimited grabs mo! Txt GRAB UNLI " . strtoupper( $item ) . " to $INLA to register for unlimited grabs. (P15 for 24hr validity)" . $REGMSG;
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
	$grab_end_time = '';
	$grab_end_time_stamp = 0;
	foreach ( $grabs as $row ) {
		$return_this['item'] = $row['keyword'];
		$gid = $row['gid'];
		$grab_end_time = $row['grab_end'];	# Datetime format
		$grab_end_time_stamp = strtotime( $grab_end_time );
	}
	$grab_bag_table = 'grab_' . $return_this['item'] . '_' . $gid;
		
	if ( $unlisub = is_unlisub( $sender) ) {
		// If the sender is an unlisub, add one full day after end_time in unlisub table
		$end_time = date( "Y-m-d H:i:s", strtotime($unlisub['end_time'] . ' + 1 day') );	# Add one day to current end time
		$return_this['end_time'] = $end_time_stamp = strtotime( $end_time );	# Put end_time as timestamp in return array
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


function sanity_check( $sender, $grabs ) {
	/*
	Subscribers may buy unli time only until the
	end of the grab time. So if a subscriber is still
	trying to buy unli time for a grab item even if
	his unli-grab time stamp for that item already is
	beyond the grab item's end time, do not allow the buy.
	*/
	$no_more_buys = array();
	$unlisub = is_unlisub( $sender );
	foreach( $grabs as $row ) {
		$item = $row['keyword'];
		$gid = $row['gid'];
		$grab_end = $row['grab_end'];
		$grab_end_timestamp = strtotime($row['grab_end']);
		$unlisub_end_timestamp = strtotime($unlisub['end_time']);
		if ( $unlisub_end_timestamp > $grab_end_timestamp ) {
			$no_more_buys[] = $gid.'/'.$item.'/'.$grab_end;
		}
	}
	return $no_more_buys;
}
?>