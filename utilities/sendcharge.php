<?php

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
	'250'	=>	'CLUBZHELP',	// Chargecode for P2.50
	'2000'	=>	'CLUBZHELP'		// Replace with value for P20 chargecode
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
		$ch_info['errno'] = curl_errno( $ch );
		$ch_info['errtxt'] = curl_error( $ch );
	}
	curl_close( $ch );
	return $ch_info;
}

?>
