<?php

require '../include/config.php';

print "OK...";

$grab_table = $_REQUEST['table'];

$q = "SELECT DISTINCT( msisdn ) FROM `" . $grab_table . "`";
$r = mysql_query( $q );
$row = mysql_fetch_assoc( $r );

print_r( $row );

?>