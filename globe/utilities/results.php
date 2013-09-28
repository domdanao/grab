<?php

require '../include/config.php';

$grab_table = $_REQUEST['table'];

$results = array();
$q = "SELECT DISTINCT( msisdn ) AS phone FROM `" . $grab_table . "`";
$r = mysql_query( $q );
while ($row = mysql_fetch_assoc( $r )) {
	$phone = $row['phone'];
	$total_time = results_hold_times($phone, $grab_table);
	$results[$phone] = $total_time;
}

arsort($results);
foreach ($results as $key => $val) {
	print "$key\t$val\n";
}

##################################################
// Get total holdtime
function results_hold_times( $msisdn, $table, $timenow = 0 ) {
	global $dblink;

	$totalholdtime = 0;

	$parts = preg_split('/_/', $table);
	$gid = $parts[2];
	$q = "SELECT UNIX_TIMESTAMP(grab_end) AS end_time FROM `$table` WHERE `gid` = $gid";
	$rs = mysql_query($q);
	$rw = mysql_fetch_assoc($rs);
	$time_end = $rw['end_time'];
	
	$query = "SELECT SUM( lost_time-grab_time ) AS totalholdtime FROM `$table` WHERE `msisdn` = '$msisdn' AND lost_time <> 0";
	$result = mysql_query( $query );
	if ( $result !== FALSE ) {
		$row = mysql_fetch_assoc( $result );
		$holder_time = is_holder( $msisdn, $table );

		if ( $holder_time ) {
			$time_so_far = $row['totalholdtime'];
			print "$time_end, $holder_time";
			$inc_time = $time_end-$holder_time;
			// incremental time added to totalholdtime
			$totalholdtime = $time_so_far+$inc_time;
		} else {
			$totalholdtime = $row['totalholdtime'];
		}
	}

	return $totalholdtime;
}
?>