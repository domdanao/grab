<?php

include '../include/config.php';

$grab_table = $_REQUEST['table'];

$q = "SELECT DISTINCT( msisdn ) FROM `" . $grab_table . "`";
$r = mysql_query( $q );
$row = mysql_fetch_assoc( $r );

print_r( $row );


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
?>