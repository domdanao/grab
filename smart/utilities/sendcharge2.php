<?php

error_reporting(0);

##################################################
### CHARGING URL and PARAMETERS
### This is for Globe RT Billing service

/*
http://203.177.154.215/2889ZEDCHARGE?CSP_Txid=12121212
&CP_Id=zed
&CP_UserId=zed
&CP_Password=zed12345
&SUB_C_Mobtel=639175132107
&CSP_A_Keyword=CLUBZHELP
*/

// VALUES FOR GLOBE SILENT BILLING
$CHG_VALS = array(
	'100'	=>	'GRABPULL1',	// Chargecode for P1.00
	'250'	=>	'CLUBZHELP',	// Chargecode for P2.50
	'500'	=>	'GRABPULL5'		// Chargecode for P5.00
	);

$SENDCHARGE = array(
	'url'	=>	'http://203.177.154.215/2889ZEDCHARGE',
	'mo_id'	=>	'',							// MO_ID, MANDATORY
	'parameters' =>	array(
		'CSP_Txid'		=>	'',				// MOTXID, MANDATORY
		'CP_Id'			=>	'zed',
		'CP_UserId'		=>	'zed',
		'CP_Password'	=>	'zed12345',
		'SUB_C_Mobtel'	=>	'',				// MSISDN, MANDATORY
		'CSP_A_Keyword'	=>	''				// CHARGECODE, MANDATORY
		)
	);


##################################################
// Request parameters
$SENDCHARGE['mo_id'] = $mo_id = $_REQUEST['mo_id'];
$SENDCHARGE['parameters']['CSP_Txid'] = $txid = $_REQUEST['txid'];
$SENDCHARGE['parameters']['SUB_C_Mobtel'] = $mobtel = $_REQUEST['mobtel'];
$charge = $_REQUEST['charge'];
$SENDCHARGE['parameters']['CSP_A_Keyword'] = $keyword = $CHG_VALS[$charge];

##################################################
// Response variables
$response = array(
	'response'	=>	'',
	'reason'	=>	'',
	'request'	=> array(
		'mo_id'		=> $mo_id,
		'mobtel'	=> $mobtel,
		'txid'		=> $txid,
		'charge'	=> $charge,
		'keyword'	=> $keyword,
		'ipaddr'	=> $_SERVER['REMOTE_ADDR']
	),
	'headers'	=> array()
);

##################################################
// Parameters tests
if (!$mo_id or !$keyword or !$txid or !$mobtel) {
	// Incomplete parameters, error
	$response['response'] = 'ERROR';
	$response['reason']	= 'Incomplete parameters';
} else {
	$reply = hit_http_url($SENDCHARGE['url'], $SENDCHARGE['parameters'], 'get');

	if (isset($reply['errno']) or isset($reply['errtxt'])) {
		// Curl error
		$response['response'] = 'ERROR';
		$response['reason'] = 'CURL error ' . $reply['errno'] . '/' . $reply['errtxt'];
	} else {
		$response['headers'] = $reply['headers'];
		if ($reply['http_code'] == 200) {
			// We were able to charge
			$response['response'] = 'OK';
			$response['reason'] = $reply['http_code'];
		} else {
			// Charge not done
			$response['response'] = 'NOK';
			$response['reason'] = $reply['http_code'];
		}
	}
	
	$reply2 = hit_http_url($SENDCHARGE['url'], $SENDCHARGE['parameters'], 'get');

	if (isset($reply2['errno']) or isset($reply2['errtxt'])) {
		// Curl error
		$response['response'] = '/ERROR';
		$response['reason'] = '/CURL error ' . $reply['errno'] . '/' . $reply['errtxt'];
	} else {
		$response['headers'] = $reply2['headers'];
		if ($reply2['http_code'] == 200) {
			// We were able to charge
			$response['response'] = '/OK';
			$response['reason'] = '/'.$reply2['http_code'];
		} else {
			// Charge not done
			$response['response'] = '/NOK';
			$response['reason'] = '/'.$reply2['http_code'];
		}
	}

}

print json_encode( $response, JSON_PRETTY_PRINT );


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