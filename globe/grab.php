<?php

##################################################
// GRAB Director

require_once 'include/config.php';


##################################################
/*
// LIST TEST PHONES HERE
$allowed = array(
	'',
	'',
	''
	);
if (!in_array($_REQUEST['from'], $allowed)) {
	exit;
}

*/

/*
REQUEST PARAMETERS
motxid
msisdn
message
operatorid
*/


##################################################
// See if all parameters exist
if 	(	!array_key_exists( 'motxid', $_REQUEST ) or
		!array_key_exists( 'msisdn', $_REQUEST ) or
		!array_key_exists( 'message', $_REQUEST ) or
		!array_key_exists( 'operatorid', $_REQUEST )
	)
{
	$response = array(
		'response'	=>	'ERROR',
		'reason'	=>	'One or more parameters missing',
		'request'	=>	$_REQUEST	
	);
	print json_encode( $response, JSON_PRETTY_PRINT );
	exit();
}


##################################################
// See if all parameters have value (not empty)
if 	( 	empty ($_REQUEST['motxid']) or
		empty ($_REQUEST['msisdn']) or
		empty ($_REQUEST['message']) or
		empty ($_REQUEST['operatorid'])
	)
{
	$response = array(
		'response'	=>	'ERROR',
		'reason'	=>	'One or more parameters empty',
		'request'	=>	$_REQUEST	
	);
	print json_encode( $response, JSON_PRETTY_PRINT );
	exit();
}


$tran_id = $_REQUEST['motxid'];


##################################################
// Clean up message
$allwords = preg_replace( '/\s+/', ' ', $_REQUEST['message'] );	// Remove two or more white spaces
$word_arr = explode( " ", $allwords );
$main_key = strtolower( $word_arr[0] );
$second_key = '';
$params = '';

// In case the sub just sent GRAB (or alternates), assume GRAB BAG
$total_words = count( $word_arr );
if ( $total_words > 1 ) {
	$second_key = strtolower( $word_arr[1] );
	// This is to handle GRAB REG Mark Sy/25/1 Rizal Ave, Manila
	if ( $total_words > 2 ) {
		$new_arr = array_slice( $word_arr, 2 );
		if ( count( $new_arr ) > 1 ) {
			$params = implode( ' ', $new_arr );
		} else {
			$params = $new_arr[0];
		}
	}
} else {
	$second_key = 'bag';
}

$translated = array();
foreach ( $word_arr as $word ) {
	$word = strtolower( $word );
	foreach ( $KEYWORDS_PARAM as $key => $val ) {
		if ( in_array( $word, $val ) ) {
			$translated[] = $key;
		}
	}
}

if ( in_array( 'grab', $translated ) ) {
	$main_key = 'grab';
	if ( in_array( 'off', $translated ) ) {
		$second_key = 'off';
	}
	if ( in_array( 'on', $translated ) ) {
		$second_key = 'on';
	}
	if ( in_array( 'bag', $translated ) ) {
		$second_key = 'bag';
	}
	if ( in_array( 'time', $translated ) ) {
		$second_key = 'time';
	}
	if ( in_array( 'help', $translated ) ) {
		$second_key = 'help';
	}
	if ( in_array( 'reg', $translated ) ) {
		$second_key = 'reg';
	}
}


##################################################
// Mobtel of subscriber
$mo_from = $_REQUEST['msisdn'];


##################################################
// Var for unique ID in msg_in table (MANDATORY)
$mo_id = null;


##################################################
// Connecting client
if ( array_key_exists( 'came_from', $_REQUEST ) ) {
	$came_from = $_REQUEST['came_from'];
	// Could be SMS, WEB, MOBWEB
} else {
	$came_from = 'SMS';	// default is SMS
}


##################################################
// Telco
$operator = '';
$operatorid = $_REQUEST['operatorid'];
// Identify operators and load messages file
if ( $operatorid == '51502' ) {
	$operator = 'GLOBE';		// Globe identifier
}
if ( $operatorid == '51503' ) {
	$operator = 'SMART';		// Smart identifier
}


##################################################
// Record into messages table
$query = "INSERT INTO `msg_in` SET
	`msisdn` = '" . $mo_from . "',
	`raw_msg` = '" . mysql_real_escape_string( $_REQUEST['message'] ) . "',
	`recipient` = '" . $INLA . "',
	`operator` = '" . $operator . "',
	`msg_id` = '" . $tran_id . "',
	`came_from` = '" . $came_from . "',
	`time_in` = '" . date( "Y-m-d H:i:s" ) . "'";
$result = mysql_query( $query );

// Now let us see what happened to our MySQL query
if ( mysql_affected_rows() == -1 ) {
	// Notify program owner
	$SENDSMS['parameters']['mo_id'] = 0;
	$SENDSMS['parameters']['txid'] = $_REQUEST['motxid'];
	$SENDSMS['parameters']['mobtel'] = $PROGRAM_OWNER;
	$SENDSMS['parameters']['message'] = 'ERROR:globe/grab.php:fail_insert:query:'. $query;
	hit_http_url($SENDSMS['url'], $SENDSMS['data'], 'get');
	exit();
} else {
	$mo_id = mysql_insert_id();
}


##################################################
// First time to send?
if ( first_send( $mo_from, $dblink ) === FALSE ) {
	$SENDSMS['parameters']['mo_id'] = $mo_id;
	$SENDSMS['parameters']['txid'] = $_REQUEST['motxid'];
	$SENDSMS['parameters']['message'] = $WELCOME_MSG;
	$SENDSMS['parameters']['mobtel'] = $mo_from;
	// Send the welcome message
	sms_mt_request( $SENDSMS );
}


##################################################
// PROGRAM PATH
$program_path = '';


##################################################
// HTTP_PARAMETERS for programs (common)
$http_params = array(
	'sender' => $mo_from,
	'tran_id' => $_REQUEST['motxid'],
	'smsc_time' => time(),
	'time_sent' => date( "Y-m-d H:i:s", time() ),
	'mo_id' => $mo_id,
	'keyword' => $main_key,
	'param' => $second_key,
	'others' => '',
	'operator' => $operator
	);


// 2ND KEYWORD: BAG
// GRAB BAG
if ( $second_key == 'bag' ) {
	$program_path = $BAG_PATH;
	$http_params['others'] = $params;
}

elseif

// 2ND KEYWORD: TIME
// GRAB TIME <ITEM>
( $second_key == 'time' ) {
	$program_path = $TIME_PATH;
	$http_params['others'] = $params;
}

elseif

// 2ND KEYWORD: ON or OFF
// GRAB <ON|OFF>
( $second_key == 'on' or $second_key == 'off') {
	$program_path = $SUBSCRIBE_PATH;
}

elseif

// 2ND KEYWORD: HELP
// GRAB HELP
( $second_key == 'help' ) {
	$program_path = $HELP_PATH;
}

elseif

// 2ND KEYWORD: REG
// GRAB REG
( $second_key == 'reg' ) {
	$program_path = $REG_PATH;
	$http_params['others'] = $params;
}

else {
	$program_path = $DEFAULT_PATH;
	$http_params['keyword'] = $main_key;
	$http_params['param'] = $second_key;
	$http_params['others'] = $params;
	$http_params['receiver'] = $INLA;
	$http_params['smsc_time'] = $time_start;
	$http_params['smsc_id'] = $operator;
	$http_params['sms_id'] = $tran_id;
	$http_params['mo_id'] = $mo_id;
}


##################################################
// Hit the URL
$response['http_url'] = $http_url = 'http://' . $PROG_HOST . ':' . $PROG_PORT . $program_path;
$response['http_params'] = $http_params;

$rep = hit_http_url( $http_url, $http_params, 'get' );
$response = array(
	'http_code'		=>	$rep['http_code'],
	'total_time'	=>	$rep['total_time'],
	'body_content'	=>	$rep['body_content']
);

// Parse body_content
$parts = explode("\r\n\r\nHTTP/", $response['body_content']);
$parts = (count($parts) > 1 ? 'HTTP/' : '').array_pop($parts);
list($headers, $body) = explode("\r\n\r\n", $parts, 2);

$body_array = json_decode( $body );

print json_encode( $response, JSON_PRETTY_PRINT );

print_r($body_array);

##################################################
?>