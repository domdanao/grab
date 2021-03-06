<?php

require '../include/config.php';

// output headers so that the file is downloaded rather than displayed
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=results.csv');

// create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// output the column headings
fputcsv($output, array('MSISDN', 'Total Time', 'Name', 'Address', 'Age'));

$grab_table = $_REQUEST['table'];

$contents = array();

$q = "SELECT DISTINCT( msisdn ) AS phone FROM `" . $grab_table . "`";
$r = mysql_query( $q );
while ($row = mysql_fetch_assoc( $r )) {
	// Array($phone, $total_time, $name, $address, $age)
	$phone = $row['phone'];
	$total_time = results_hold_times($phone, $grab_table);
	$name = '(None)';
	$address = '(None)';
	$age = '(None)';
	
	if (is_registered($phone)) {
		$res = mysql_query("SELECT name, address, age FROM members WHERE msisdn = '" . $phone . "'");
		$roo = mysql_fetch_assoc($res);
		$name = $roo['name'];
		$address = $roo['address'];
		$age = $roo['age'];
	}
	$contents[$total_time] = array('phone' => $phone, 'total_time' => $total_time, 'name' => $name, 'address' => $address, 'age' => $age);
}

krsort($contents);
foreach ($contents as $key => $row) {
	fputcsv($output, $row);
}


##################################################
// Get total holdtime
function results_hold_times( $msisdn, $table, $timenow = 0 ) {
	global $dblink;

	$totalholdtime = 0;

	$parts = preg_split('/_/', $table);
	$gid = $parts[2];
	$q = "SELECT UNIX_TIMESTAMP(grab_end) AS end_time FROM `grab_bag` WHERE `gid` = $gid";
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
			//print "$time_end, $holder_time\n\n";
			$inc_time = $time_end-$holder_time;
			// incremental time added to totalholdtime
			$totalholdtime = $time_so_far+$inc_time;
			//print "$time_so_far, $time_end, $holder_time, $inc_time\n\n";
		} else {
			$totalholdtime = $row['totalholdtime'];
		}
	}

	return $totalholdtime;
}

?>