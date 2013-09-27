<?php

require '../include/config.php';

print "OK...";

$grab_table = $_REQUEST['table'];

$q = "SELECT DISTINCT( msisdn ) AS phone FROM `" . $grab_table . "`";
$r = mysql_query( $q );
while ($row = mysql_fetch_array( $r )) {
	echo $row['phone'] . "\n";
}

?>