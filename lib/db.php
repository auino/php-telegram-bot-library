<?php
function db_connect() {
	global $DB_PORT, $DB_HOST, $DB_USER, $DB_PWD;
	$conn = mysql_connect("$DB_HOST:$DB_PORT", $DB_USER, $DB_PWD);
	return $conn;
}

function db_close($conn) {
	mysql_close($conn);
}

// db_connect() and db_close() executions are accomplished inside of this function
function db_nonquery($q) {
	global $DB_NAME;
	$conn = db_connect();
	if(!$conn) return false;
	mysql_select_db($DB_NAME);
	$retval = mysql_query($q, $conn);
	$res = $retval;
	db_close($conn);
	return $res;
}

// db_connect() and db_close() executions are accomplished inside of this function
function db_query($q) {
	global $DB_NAME;
	$conn = db_connect();
	if(!$conn) return false;
	mysql_select_db($DB_NAME);
	$retval = mysql_query($q, $conn);
	if(!$retval) return false;
	$res = array();
	while($row = mysql_fetch_array($retval, MYSQL_ASSOC)) {
		array_push($res, $row);
	}
	db_close($conn);
	return $res;
}

// db_connect() and db_close() executions are required outside of this function
// this function is useful on non performant devices to get a single random element without using the SQL RAND() function, loading results in memory
function db_randomone($table, $filter=null) {
	if($filter != null) $filter = "WHERE $filter";
	else $filter = "";
	$q = "SELECT COUNT(*) as c FROM $table $filter;";
	$r = db_query($q);
	$max = $r[0]['c'];
	$rand = rand(0, $max);
	$q = "SELECT * FROM $table $filter LIMIT $rand,1;";
	$r = db_query($q);
	return $r;
}
?>
