<?php

require '../include/config.php';

$grab_table = $_REQUEST['table'];

$results = array();
$q = "SELECT DISTINCT( msisdn ) AS phone FROM `" . $grab_table . "`";
$r = mysql_query( $q );
while ($row = mysql_fetch_assoc( $r )) {
	$phone = $row['phone'];
	$total_time = total_hold_time($phone, $grab_table);
	$results[$phone] = $total_time;
}

arsort($results);
foreach ($results as $key => $val) {
	print "$key\t$val\n";
}
?>