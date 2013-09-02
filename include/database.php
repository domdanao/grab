<?php
// GRAB Database Login

if (array_key_exists("RDS_HOSTNAME", $_SERVER) and array_key_exists("RDS_PORT", $_SERVER) and array_key_exists("RDS_DBNAME", $_SERVER)) {
	
	$dblink = mysql_connect(
		$_SERVER["RDS_HOSTNAME"] . ":" . $_SERVER["RDS_PORT"],
		$_SERVER["RDS_USERNAME"],
		$_SERVER["RDS_PASSWORD"])
		or
		die ("ERROR:" . mysql_error());
	mysql_select_db($_SERVER["RDS_DB_NAME"]);
	print "CONNECTED REMOTE: " . $_SERVER["RDS_DB_NAME"];

} else {

	$dblink = mysql_connect(
		'localhost',
		'grab',
		'9BXem7KewJwUOXgBx1bAG4ODG5QIWITg' )
		or
		die( "ERROR: " . mysql_error() );

	mysql_select_db( 'grab' );	
	print "CONNECTED LOCAL";
}
?>