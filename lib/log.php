<?php
function db_log($botname, $action, $chat, $type, $content, $date) {
	global $LOGS_ENABLED;
	if(!$LOGS_ENABLED) return;
	if($content!=null) $content="'$content'"; else $content="NULL";
	$q = "INSERT INTO Logs(bot, action, chat, type, content, date) VALUES('$botname', '$action', '$chat', '$type', $content, '$date');";
	db_nonquery($q);
}

function db_getchatlist($botname) {
	global $LOGS_ENABLED, $ISLOWPERFORMANCEHOST;
	if(!$LOGS_ENABLED) return;
	$f = '';
	if(!$ISLOWPERFORMANCEHOST) $f = " DISTINCT";
	$q = "SELECT$f chat FROM Logs WHERE bot='$botname';";
	$s = db_query($q);
	$r = array();
	foreach($s as $el) array_push($r, $el['chat']);
	return $r;
}

function logarray($type, $content) {
	return array('type'=>$type, 'content'=>addslashes($content));
}
?>
