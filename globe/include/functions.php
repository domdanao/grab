<?php

##################################################
#########      F U N C T I O N S      ############
##################################################



##################################################
// Check if MSISDN has already sent a message
function first_send( $msisdn, $dblink ) {
	$result = mysql_query( "SELECT * FROM `firstsend` WHERE `msisdn` = '" . $msisdn . "'" );
	if ( mysql_num_rows( $result ) ) {
		return TRUE;
	} else {
		// Insert into db
		mysql_query( "INSERT INTO `firstsend` SET `msisdn` = '" . $msisdn . "', `sentwhen` = '" . date( "Y-m-d H:i:s" ) . "'" );
		return FALSE;
	}
}


##################################################
// Check if the MSISDN is listed in members table
function is_registered( $msisdn ) {
	global $dblink;
	$result = mysql_query( "SELECT * FROM `members` WHERE `msisdn` = '" . $msisdn. "'" );
	if ( mysql_num_rows( $result ) ) {
		$row = mysql_fetch_assoc( $result );
		return $row;
	} else {
		return FALSE;
	}
}


##################################################
// Check if sub has unlimiteeed subscription
function is_unlisub( $msisdn ) {
	global $dblink;
	$timenow = date( "Y-m-d H:i:s" );
	if ( $row = is_registered( $msisdn ) ) {
		$result = mysql_query( "SELECT * FROM `unlisubs` WHERE `mo_id` = '" . $row['mo_id'] . "' AND ('" . $timenow . "' BETWEEN `time_start` AND `time_end`)" );
		if ( mysql_num_rows( $result ) ) {
			return mysql_fetch_assoc( $result );
		} else {
			return FALSE;
		}
	}
}


##################################################
// Check if the subscriber is playing a grab game
function has_grab( $msisdn, $smsc_time, $operator, $dblink ) {
	$has_grab = FALSE;
	$current_grab = grab_bag( $smsc_time, $operator, $dblink );
	foreach ( $current_grab as $row ) {
		$query = "SELECT * FROM `grab_" . strtolower( $row['keyword'] ) . "_" . $row['gid'] . "` WHERE `msisdn` = '" . $msisdn . "'";
		$result = mysql_query( $query );
		if ( mysql_num_rows( $result ) ) {
			$has_grab = TRUE;
		}
	}
	return $has_grab;
}


##################################################
// Check if the subscriber has a bid in any particular grab game
function has_grab_in_this_game( $msisdn, $table, $dblink ) {
	$query = "SELECT `msisdn` FROM `" . $table . "` WHERE `msisdn` = '" . $msisdn . "'";
	$result = mysql_query( $query );
	return mysql_num_rows( $result );
}


##################################################
// Check if sub is in alerts table
function is_alert_sub( $msisdn, $dblink ) {
	$query = "SELECT `msisdn`,`alert` FROM `members` WHERE `msisdn` = '" . $msisdn ."' AND `alert` = 1";
	$result = mysql_query( $query );
	if ( mysql_num_rows( $result ) ) {
		return TRUE;
	} else {
		return FALSE;
	}
}


##################################################
// Check grab bag items
function grab_bag( $smsc_time, $operator, $dblink ) {
	$grabs = array();
	$query = "SELECT * FROM `grab_bag` WHERE ('" . $smsc_time . "' BETWEEN `grab_start` AND `grab_end`) AND (`operator` REGEXP '$operator')";
	$result = mysql_query( $query );
	while ( $row = mysql_fetch_assoc( $result ) ) {
		$grabs[] = $row;
	}
	return $grabs;
}


##################################################
// Check user total hold time
function grab_time( $phone, $table, $dblink ) {
	$query = "SELECT SUM( `hold_time` ) AS totaltime FROM `"  . $table . "` WHERE `msisdn` = '" . $phone . "'";
	$result = mysql_query( $query );
	if ( $result !== FALSE ) {
		if ( mysql_num_rows($result) ) {
			$row = mysql_fetch_assoc($result);
			return $row['totaltime'];
		} else {
			return FALSE;
		}
	} else {
		return FALSE;
	}
}


##################################################
// Duration
function duration_find( $seconds ) {
	$seconds = (string)$seconds;
	$parts = array();
	if ( preg_match( "/\./", $seconds ) ) {
		$parts = explode( ".", $seconds );
	} else {
		$parts[0] = $seconds;
		$parts[1] = '';
	}
	$secs = (int)($parts[0]);
	$vals = array(
		'wk' => (int) ($secs / 86400 / 7),
		'day' => $secs / 86400 % 7,
		'hr' => $secs / 3600 % 24,
		'min' => $secs / 60 % 60,
		'sec' => $secs % 60
		);

	$vals['sec'] = $vals['sec']. "." . $parts[1];
	//$vals['s'] = number_format($vals['s'],2,'','');

	return $vals;
}


##################################################
// Print duration properly
function duration_out( $array ) {
	$ret = array();
	$added = FALSE;
	foreach ( $array as $k => $v ) {
		if ( $v > 0 || $added ) {
			$added = TRUE;
			if ( $k == 'sec' ) $v = number_format($v, 4, '.', ',');
			if ( $v > 1 ) $k .= 's';
			$ret[] = $v . $k;
		}
	}
	return join(" ", $ret);
}


##################################################
// Access HTTP URL
function hit_http_url( $url, $data, $method = 'post', $timeout = 15 ) {
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_USERAGENT, 'grabber_via_libcurl/06022009' );
	curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
	curl_setopt( $ch, CURLOPT_HEADER, TRUE );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	$ch_info = FALSE;
	if ( $method == 'get' ) {
		curl_setopt( $ch, CURLOPT_URL, $url . "?" . http_build_query( $data ) );
	} else {
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_POST, count( $data ) );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $data ) );
	}
	$return = curl_exec( $ch );
	if( !curl_errno( $ch ) ) {
		$ch_info = curl_getinfo($ch);
		$ch_info['body_content'] = $return;

	} else {
		$errno = curl_errno( $ch );
		$errtxt = curl_error( $ch );
		$lh = fopen( "hit_errors.log", 'a' );
		fwrite( $lh, date( "Y-m-d H:i:s" ) . " errno: $errno; errtxt: $errtxt\n" );
		fclose( $lh );
	}
	curl_close( $ch );
	return $ch_info;
}


##################################################
// Record Mobile Terminate message
function record_mt_msg( $data ) {
	global $dblink;
	$reqtime = date( "Y-m-d H:i:s", time() );
	$query = "INSERT INTO msg_out SET
		msg_id = '" . $data['msg_id'] . "',
		msgto = '" . $data['to'] . "',
		msgfrom = '" . $data['from'] . "',
		content = '" . mysql_real_escape_string($data['content']) . "',
		reqtime = '" . $reqtime . "',
		charge_code = '" . $data['action'] . "'";
	$result = mysql_query($query);
	if (mysql_affected_rows() == -1) {
		return FALSE;
	} else {
		return mysql_insert_id();
	}
}


##################################################
// Get total holdtime
function total_hold_time( $msisdn, $table, $timenow = 0 ) {
	global $dblink;

	$totalholdtime = 0;

	$query = "SELECT SUM( lost_time-grab_time ) AS totalholdtime FROM `$table` WHERE `msisdn` = '$msisdn' AND lost_time <> 0";
	$result = mysql_query( $query );
	if ( $result !== FALSE ) {
		$row = mysql_fetch_assoc( $result );
		$holder_time = is_holder( $msisdn, $table );

		if ( $holder_time ) {
			$time_so_far = $row['totalholdtime'];
			$inc_time = $timenow-$holder_time;
			// incremental time added to totalholdtime
			$totalholdtime = $time_so_far+$inc_time;
		} else {
			$totalholdtime = $row['totalholdtime'];
		}
	}

	return $totalholdtime;
}


##################################################
// Check if player has reached total grab, as indicated in the grab bag table
// Put a very large number in table if operator wants "no limit"
function grab_total_reached( $msisdn, $table ) {
	global $dblink;

	list( $keyword, $item, $gid ) = explode( "_", $table );

	$qry1 = "SELECT total_grabs_allowed FROM grab_bag WHERE gid = $gid";
	$res1 = mysql_query( $qry1 );
	$row1 = mysql_fetch_assoc( $res1 );

	$total_grabs = $row1['total_grabs_allowed'];

	$qry2 = "SELECT COUNT( msisdn ) AS grab_count FROM `$table` WHERE msisdn = '$msisdn'";
	$res2 = mysql_query( $qry2 );

	$row2 = mysql_fetch_assoc($res2);
	$grab_count = $row2['grab_count'];

	if ( $grab_count >= $total_grabs ) {
		return TRUE;
	} else {
		return FALSE;
	}
}



##################################################
function microtime_float() {
	list($usec, $sec) = explode( " ", microtime() );
	return ( (float)$usec + (float)$sec);

}


##################################################
// Check if the subscriber is the current holder of the item
function is_holder( $msisdn, $table ) {
	global $dblink;
	$query = "SELECT * FROM `$table` ORDER BY `grab_id` DESC LIMIT 1";
	$result = mysql_query($query);
	$row = mysql_fetch_assoc($result);
	if ( $msisdn == $row['msisdn'] ) {
		return $row['grab_time'];
	} else {
		return FALSE;
	}
}


##################################################
// Record HTTP requests and server replies
function http_req_rep( $data ) {
	global $dblink;
	$parse_url = parse_url( $data['url'] );
	if ( !array_key_exists( 'port', $parse_url ) && $parse_url['scheme'] == 'http' ) $parse_url['port'] = 80;
	$query = "INSERT INTO http_req_rep SET
		mo_id = " . $data['mo_id'] . ",
		req_type = '" . $data['req_type'] . "',
		host = '" . $parse_url['host'] . "',
		port = " . $parse_url['port'] . ",
		path = '" . $parse_url['path'] . "',
		query = '" . $parse_url['query'] . "',
		time_start = " . $data['time_start'] . ",
		http_code = " . $data['http_code'] . ",
		time_recd = " . $data['time_recd'] . ",
		total_time = " . $data['total_time'] . ",
		body_content = '" . mysql_real_escape_string( $data['body_content'] ) . "',
		trans_type = '" . $data['trans_type'] . "'";
	//print "\n\n\nQUERY: $query \n\n\n";
	$result = mysql_query( $query );
	if (mysql_affected_rows() == -1) {
		// print "MySQL said: " . mysql_error() . "\n\n";
		return FALSE;
	} else {
		return mysql_insert_id();
	}
}


##################################################
// Send a charge request
function charge_request( $sendcharge ) {
	global $dblink;
	// Check for mo_id and other mandatory parameters for charging
	if	(
		empty( $sendcharge['parameters']['mo_id'] ) or
		empty( $sendcharge['parameters']['txid'] ) or
		empty( $sendcharge['parameters']['mobtel'] ) or
		empty( $sendcharge['parameters']['charge'] )
		)
		{
		return FALSE;
	} else {
		// Proceed with charge request
		$http_start_time = microtime( TRUE );
		$charge_it = hit_http_url( $sendcharge['url'], $sendcharge['parameters'], 'get' );
		$http_end_time = microtime( TRUE );

		$sendcharge_result = array(
			'mo_id'			=> $sendcharge['mo_id'],
			'req_type'		=> 'GET',
			'url'			=> $charge_it['url'],
			'time_start'	=> $http_start_time,
			'http_code'		=> $charge_it['http_code'],
			'time_recd'		=> $http_end_time,
			'total_time'	=> $charge_it['total_time'],
			'body_content'	=> $charge_it['body_content'],
			'trans_type'	=> 'CHG',
			);

		if ( http_req_rep( $sendcharge_result ) ) {
			if ( $charge_it["http_code"] === 200 ) {
				return $sendcharge_result;
			} else {
				// Server return some 4xx error code
				return FALSE;
			}
		} else {
			// Function http_req_rep failed
			// Log this
			return FALSE;
		}
	}
}


##################################################
// Send an MT
function sms_mt_request( $sendsms ) {
	global $dblink;
	if	(
		empty( $sendsms['parameters']['mo_id'] ) or
		empty( $sendsms['parameters']['message'] ) or
		empty( $sendsms['parameters']['txid'] ) or
		empty( $sendsms['parameters']['mobtel'] ) 
		)
		{
		return "PARAMETER/S EMPTY!";
		#return FALSE;
	} else {
		// Proceed with SMS MT request
		$http_start_time = microtime( TRUE );
		$sms_it = hit_http_url( $sendsms['url'], $sendsms['parameters'], 'get' );
		$http_end_time = microtime( TRUE );

		$sendsms_result = array(
			'mo_id'			=> $sendsms['mo_id'],
			'req_type'		=> 'GET',
			'url'			=> $sms_it['url'],
			'time_start'	=> $http_start_time,
			'http_code'		=> $sms_it['http_code'],
			'time_recd'		=> $http_end_time,
			'total_time'	=> $sms_it['total_time'],
			'body_content'	=> $sms_it['body_content'],
			'trans_type'	=> 'MTFREE',
			);

		if ( http_req_rep( $sendsms_result ) ) {
			if ( $sms_it["http_code"] === 200 ) {
				return $sendsms_result;
			} else {
				// Server return some 4xx error code
				return "DID NOT RECEIVE 200 FROM SERVER!";
				#return FALSE;
			}
		} else {
			// Function http_req_rep failed
			// Log this
			return "HTTP_REQ_REP FAIL!";
			#return FALSE;
		}
	}
}


##################################################
?>