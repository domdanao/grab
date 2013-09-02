<?php
##################################################
// SIMPLE SENDSMS SERVICE

error_reporting(0);

##################################################
// Initialize SMS parameters
$SENDSMS = array(
	'url'            => 'http://203.177.154.215/sam/cpvas',
	'mo_id'			 => '',					// MO_ID, MANDATORY
	'parameters'     => array(
		'CP_Id'				=> 'zed',
		'CP_UserId'			=> 'zed',
		'CP_Password'		=> 'zed12345',
		'CSP_ContentType'	=> 'TM',
		'SMS_MsgTxt'		=> '',			// MSG, MANDATORY
		'SMS_Msgdata'		=> '',
		'SMS_SourceAddr'	=> '28891',
		'CSP_A_Keyword'		=> 'INFOPULL',
		'CSP_S_Keyword'		=> 'CHECK',
		'CSP_Txid'			=> '',			// MOTXID, MANDATORY
		'CSP_Remarks'       => '',
		'CSP_ChargeIndicator'	=> '0',
		'SUB_C_Mobtel'		=> '',			// MSISDN, MANDATORY
		'SUB_R_Mobtel'		=> '',			// MSISDN, MANDATORY
		'SUB_Device_Details'	=> '',
		'SUB_DeviceType'	=> ''
		)
	);

##################################################
// Request parameters, mandatory
$SENDSMS['mo_id'] = $mo_id = $_REQUEST['mo_id'];
$SENDSMS['parameters']['SMS_MsgTxt'] = $message = $_REQUEST['message'];
$SENDSMS['parameters']['CSP_Txid'] = $txid = $_REQUEST['txid'];
$SENDSMS['parameters']['SUB_C_Mobtel'] = $SENDSMS['parameters']['SUB_R_Mobtel'] = $mobtel = $_REQUEST['mobtel'];

##################################################
// Response variables
$response = array(
	'response'	=>	'',
	'reason'	=>	'',
	'request'	=> array(
		'mo_id'		=> $mo_id,
		'mobtel'	=> $mobtel,
		'txid'		=> $txid,
		'message'	=> $message,
		'ipaddr'	=> $_SERVER['REMOTE_ADDR'] ),
	'headers'	=> array()
);

$reply = false;

##################################################
// Parameters tests
if (!$mo_id or !$message or !$txid or !$mobtel) {
	// Incomplete parameters, error
	$response['response'] = 'ERROR';
	$response['reason']	= 'Incomplete parameters';
} else {
	$reply = hit_http_url($SENDSMS['url'], $SENDSMS['parameters'], 'get');

	if (isset($reply['errno']) or isset($reply['errtxt'])) {
		// Curl error
		$response['response'] = 'ERROR';
		$response['reason'] = 'CURL error ' . $reply['errno'] . '/' . $reply['errtxt'];
	} else {
		$response['response'] = 'OK';
		$response['reason'] = $reply['http_code'];
		$response['headers'] = $reply['headers'];
	}
}

print json_encode($response,JSON_PRETTY_PRINT);


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

	$headers = get_headers_from_curl_response($return);

	if( !curl_errno( $ch ) ) {
		$ch_info = curl_getinfo($ch);
		$ch_info['body_content'] = $return;
		$ch_info['headers'] = $headers;
	} else {
		$ch_info['errno'] = curl_errno( $ch );
		$ch_info['errtxt'] = curl_error( $ch );
	}
	curl_close( $ch );
	return $ch_info;
}

function get_headers_from_curl_response($response) {
	$headers = array();

	$header_text = substr($response, 0, strpos($response, "\r\n\r\n"));

	foreach (explode("\r\n", $header_text) as $i => $line) {
		if ($i === 0) {
			$headers['http_code'] = $line;
		} else {
			list ($key, $value) = explode(': ', $line);
			$headers[$key] = $value;
		}
	}
    return $headers;
}
?>