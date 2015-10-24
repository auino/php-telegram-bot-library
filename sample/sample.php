<?php
// including the library
require("../lib/telegram.php");

// basic configuration
$botname = "myawesomebot";
$token = "...";

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
		return logarray("photo", "[$image] $caption"); // you choose the format you prefer
	}
	catch(Exception $e) { return false; }
}

// instantiating a new bot
$bot = new telegram_bot($token);

// instantiating a new triggers set
$ts = new telegram_trigger_set($botname);

// registering the triggers
$ts->register_trigger("trigger_welcome", ["/start","/welcome","/hi"], 0);
$ts->register_trigger("trigger_help", ["/help"], 0);
$ts->register_trigger("trigger_photo", ["/getphoto","/photo","/picture"], -1); // parameters count is ignore

// receiving data sent from the user
$message = $bot->read_post_message();
$date = $message->message->date;
$chatid = $message->message->chat->id;
$text = $message->message->text;

// running triggers management
$response = $ts->run($bot, $chatid, $text);

// checking triggering results
if(!$response) { // an error occurred
	if($chatid < 0) { // if message has been sent from a member of a Telegram group
		// ignore it and do not reply (to avoid not necessary messages on the group)
		$response = logarray('ignore', null);
	}
	else {
		// reply with an error message
		$answer = "Error...";
		$bot->send_message($chatid, $answer);
		$response = logarray('error', $answer);
	}
}

// log messages exchange on the database
db_log($botname, 'recv', $chatid, 'text', $text, $date);
db_log($botname, 'sent', $chatid, $response['type'], $response['content'], $date);
?>
