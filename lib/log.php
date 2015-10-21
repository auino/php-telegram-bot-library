<?php
function db_log($botname, $action, $chat, $type, $content, $date) {
	global $LOGS_ENABLED;
	if(!$LOGS_ENABLED) return;
	if($content!=null) $content="'$content'"; else $content="NULL";
	$q = "INSERT INTO Logs(bot, action, chat, type, content, date) VALUES('$botname', '$action', '$chat', '$type', $content, '$date');";
	db_nonquery($q);
}
?>
