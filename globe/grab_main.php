<?php

##################################################
// Grab MAIN prog
// This is the program that processes valid GRAB <ITEM> messages


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
$do_charge = FALSE;


##################################################
// HTTP request variables
$SENDSMS['parameters']['mo_id'] = $mo_id = $_REQUEST['mo_id'];
$SENDSMS['parameters']['mobtel'] = $sender = $_REQUEST['sender'];
$SENDSMS['parameters']['txid'] = $tran_id = $_REQUEST['tran_id'];

$mainkey = strtolower( $_REQUEST['keyword'] );
$param = strtolower( trim( $_REQUEST['param'] ) ); # Must be item keyword
$others = trim( $_REQUEST['others'] );

$operator = $_REQUEST['operator'];

$timenow = microtime_float();	// Settle which time to use
$date_mysql = date( "Y-m-d H:i:s", $timenow );
$date_friendly = date( "M j, Y H:i:s", $timenow );

$the_item = strtoupper( $param );


##################################################
// Register message
$REGMSG = '';
if ( !is_registered( $sender, $dblink ) ) {
	$REGMSG = $NOT_REG;
}


/*
##################################################
// Alert message
$ALERTMSG = '';
// Alert on message
if ( !is_alert_sub( $sender, $dblink ) ) {
	$ALERTMSG = $NOT_ALERT_SUB;
}
*/

##################################################
// Notification message (start)
$message = "";


##################################################
$query = "SELECT * FROM `grab_bag` WHERE `keyword` = '" . $param . "' AND ( '" . $date_mysql . "' BETWEEN `grab_start` AND `grab_end` ) AND ( `operator` REGEXP '" . $operator . "')";
$result = mysql_query( $query );
if ( $numrow = mysql_num_rows( $result ) ) {
	// We've a match
	// Good to go
	$row = mysql_fetch_array( $result );

	$grab_action_table = "grab_" . $row['keyword'] . "_" . $row['gid'];

	$query = "SHOW TABLES FROM `grab` LIKE '" . $grab_action_table . "'";
	$result = mysql_query( $query );

	if ( !mysql_num_rows( $result ) ) {
		$sql = "CREATE TABLE IF NOT EXISTS `$grab_action_table` (
				`grab_id` bigint(12) NOT NULL auto_increment,
				`msisdn` varchar(16) NOT NULL,
				`grab_time` decimal(16,6) NOT NULL,
				`lost_time` decimal(16,6) NOT NULL,
				PRIMARY KEY  (`grab_id`),
				KEY `msisdn` (`msisdn`),
				KEY `hold_time` (`lost_time`),
				KEY `grab_time` (`grab_time`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Grab table for " . $row['keyword'] . "'";
		mysql_query( $sql );
	}

	$has_reached_total_grabs_allowed = grab_total_reached( $sender, $grab_action_table );
	
	$an_unli_sub = is_unlisub( $sender );

	if ( $has_reached_total_grabs_allowed ) {
		$message = "GRAB: You've reached the maximum number of grabs for this item (" . strtoupper( $param ) . "). You cannot grab the item any longer.";
	} else {
		/*
		$message = "GRAB:\nNatanggap namin ang Grab attempt mo. Pls wait for confirmation that you've grabbed the item (" . strtoupper($param) . "). $BP1 $REGMSG";
		$SENDSMS['parameters']['SMS_MsgTxt'] = $message;
		sms_mt_request( $SENDSMS );
		*/

		$CHARGE_IT = FALSE;
		
		if ( $an_unli_sub == FALSE ) {
			// Set up charging (mandatory variables)
			$SENDCHARGE['parameters']['mo_id'] = $mo_id;
			$SENDCHARGE['parameters']['txid'] = $tran_id;
			$SENDCHARGE['parameters']['mobtel'] = $sender;
			$SENDCHARGE['parameters']['charge'] = $charge_val;		

			//  Send charge request
			$CHARGE_IT = charge_request( $SENDCHARGE );
		}

		// NEW TIME VARIABLES
		// Time that charging service returned a reply
		// This will be the time that will be entered as start_time
		$charge_time = $CHARGE_IT['time_recd'];
		// Format: Dec 25, 2013 12:30:12
		$charge_time_friendly = date( "M j, Y H:i:s", $charge_time );

		if ( $CHARGE_IT['http_code'] == 200 or $an_unli_sub ) {
			// All right! We got charge confirmation, or we have an unlisub
			$count = "SELECT COUNT(*) AS bilang FROM `$grab_action_table`";
			$res = mysql_query( $count );
			$row = mysql_fetch_assoc( $res );
			$bilang = $row['bilang'];

			if ( $bilang ) {
				// Is he the current holder?
				$query = "SELECT * FROM `$grab_action_table` ORDER BY `grab_id` DESC LIMIT 1";
				$result = mysql_query( $query );
				$row = array();
				$cur_msisdn = '';

				if ( mysql_num_rows( $result ) ) {
					$row = mysql_fetch_assoc($result);
					
					$cur_msisdn = $row['msisdn'];

					if ($row['msisdn'] == $sender) {
						// Charge here!
						// Sub holds the item, so let's just tell him he still holds it and not insert his grab in the DB
						$totalholdtime = total_hold_time( $row['msisdn'], $grab_action_table, $charge_time );
						$duration = duration_find( $totalholdtime );
						$duration_out = duration_out( $duration );
						$message = "GRAB: Hawak mo pa rin ang " . $the_item . " kaya di mo na kailangang i-grab.\n\nTotal hold time mo so far para sa " . $the_item . ": " . $duration_out . ".\n\nWag pabayaang ma-grab ng iba. Pag natanggap mo alert na naagaw ito, grab mo lang uli!";
						$SENDSMS['parameters']['message'] = $message;
						
						$SENDCHARGE['parameters']['mo_id'] = $mo_id;
						$SENDCHARGE['parameters']['txid'] = $tran_id;
						$SENDCHARGE['parameters']['mobtel'] = $sender;
						$SENDCHARGE['parameters']['charge'] = $charge_val;
						
						if ( charge_request( $SENDCHARGE ) ) {
							sms_mt_request( $SENDSMS );							
						}

						exit();
					} else {
						// Subscriber is not the holder, so give him the item
						mysql_query("BEGIN");

						// Update last record
						$update_last = "UPDATE `" . $grab_action_table . "` SET `lost_time` = $charge_time WHERE `grab_id` = " . $row['grab_id'];
						$update_result = mysql_query( $update_last );

						// Insert new grab
						$insert = "INSERT INTO `" . $grab_action_table . "` ( `msisdn`, `grab_time` ) VALUES ( '" . $sender . "', $charge_time )";
						$insert_result = mysql_query( $insert );

						mysql_query("COMMIT");

						// Let's alert the previous holder
						$totalholdtime = total_hold_time( $row['msisdn'], $grab_action_table );
						$duration = duration_find( $totalholdtime );
						$duration_out = duration_out( $duration );
						$SENDSMS_FOR_PREVIOUS_HOLDER['parameters']['message'] = "[GRAB A GADGET Free Alert]\n\nNaku! may nakaagaw na ng $the_item. Total hold time as of $charge_time_friendly: $duration_out.\n\nWag ka pumayag dude! I-grab mo uli, txt GRAB $the_item sa $INLA. $BP1";
						$SENDSMS_FOR_PREVIOUS_HOLDER['parameters']['mobtel'] = $cur_msisdn;
						$SENDSMS_FOR_PREVIOUS_HOLDER['parameters']['txid'] = $tran_id;
						$SENDSMS_FOR_PREVIOUS_HOLDER['parameters']['mo_id'] = $mo_id;
						sms_mt_request( $SENDSMS_FOR_PREVIOUS_HOLDER );
					}
				
			} else {
				
				$insert = "INSERT INTO `" . $grab_action_table . "` ( `msisdn`, `grab_time` ) VALUES ( '$sender', $charge_time )";
				$insert_result = mysql_query( $insert );

			}
			
			$message = "GRAB: Ikaw na ngayon may hawak ng $the_item (as of $charge_time_friendly).\n\nMagsubscribe sa GRAB A GADGET alerts para updated ka daily on your grab time, grab tips o upcoming items. Txt ON GRAB to $INLA. P2.50/alert";

		} else {

			// Subscriber probably has no load, so send him notice
			$message = "GRAB: Sorry, you do not have enough balance or not subscribed UNLIGRAB.";
			
		}

	}
	
} else {
	// There is no such item in the Grab Bag
	$message = "GRAB: Sorry, walang ganyang item (" . strtoupper( $param ) . ") sa Grab Bag ngayon.\nText GRAB BAG to $INLA para makita mo kung ano laman ng Grab Bag. $BP1";
}

$SENDSMS['parameters']['message'] = $message;
sms_mt_request( $SENDSMS );

exit();
?>