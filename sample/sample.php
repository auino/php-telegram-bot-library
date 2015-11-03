<?php
// including the library
require("../lib/telegram.php");

// basic configuration
$botname = "myawesomebot";
$token = "...";
$singletrigger = true; // if true, it tells the library to trigger at most a single callback function for each received message

// callbacks definition

function trigger_welcome($p) {
	try {
		$answer = "Welcome...";
		$p->bot()->send_message($p->chatid(), $answer);
		return logarray('text', $answer);
	}
	catch(Exception $e) { return false; } // you can also return what you prefer
}

function trigger_help($p) {
	try {
		$answer = "Try /photo to get a photo...";
		$p->bot()->send_message($p->chatid(), $answer);
		return logarray('text', $answer);
	}
	catch(Exception $e) { return false; }
}

function trigger_photo($p) {
	try {
		$pic = "lena.jpg";
		$caption = "Look at this picture!";
		$p->bot()->send_photo($p->chatid(), "@$pic", $caption);
		return logarray("photo", "[$pic] $caption"); // you choose the format you prefer
	}
	catch(Exception $e) { return false; }
}

// callback to use if anything goes wrong
function trigger_err($p) {
	if($p->chatid() < 0) { // if message has been sent from a member of a Telegram group
		// ignore it and do not reply (to avoid not necessary messages on the group)
		$response = logarray('ignore', null);
	}
	else {
		// reply with an error message
		$answer = "Error...";
		$bot->send_message($p->chatid(), $answer);
		$response = logarray('error', $answer);
	}
	return $response;
}

// instantiating a new bot
$bot = new telegram_bot($token);

// instantiating a new triggers set
$ts = new telegram_trigger_set($botname, $singletrigger);

// registering the triggers
$ts->register_trigger_command("trigger_welcome", ["/start","/welcome","/hi"], 0);
$ts->register_trigger_command("trigger_help", ["/help"], 0);
$ts->register_trigger_command("trigger_photo", ["/getphoto","/photo","/picture"], -1); // parameters count is ignore
// error trigger
$ts->register_trigger_error("trigger_err");

// receiving data sent from the user
$message = $bot->read_post_message();
$date = $message->message->date;
$chatid = $message->message->chat->id;
$text = $message->message->text;

// running triggers management
$response = $ts->run($bot, $chatid, $text); // returns an array of triggered events
// log messages exchange on the database
db_log($botname, 'recv', $chatid, 'text', $text, $date);
if(count($response)>0) foreach($response as $r) db_log($botname, 'sent', $chatid, $r['type'], $r['content'], $date);
else db_log($botname, 'error', $chatid, 'Error', $date);
?>
