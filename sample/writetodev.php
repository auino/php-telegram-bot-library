<?
// including the library
require("lib/telegram.php");

// if already configured on config.php file, delete/comment following lines
$TELEGRAM_BOTNAME = "writetodevbot";
$TELEGRAM_TOKEN = "...";
$STATUS_ENABLE = true;

// basic configuration
$singletrigger = true; // if true, it tells the library to trigger at most a single callback function for each received message

// callbacks definition

function trigger_welcome($p) {
	try {
		$answer = "Welcome...";
		$p->state()->movetostate("in_chat"); // moving to state "in_chat"
		$p->bot()->send_message($p->chatid(), $answer);
		return logarray('text', $answer);
	}
	catch(Exception $e) { return false; } // you can also return what you prefer
}

function trigger_help($p) {
	try {
		$answer = "Try /write to send a message to developers...";
		$p->bot()->send_message($p->chatid(), $answer);
		return logarray('text', $answer);
	}
	catch(Exception $e) { return false; }
}

function trigger_write($p) {
	try {
		$answer = "Write your message and press enter...";
		$p->state()->movetostate("waiting_for_input"); // moving to state "waiting_for_input"
		$p->bot()->send_message($p->chatid(), $answer);
		return logarray('text', $answer);
	}
	catch(Exception $e) { return false; } // you can also return what you prefer
}

function trigger_input($p) {
	try {
		$answer = "Received, thanks!";
		file_put_contents("/tmp/writetodev.txt", $p->chatid().": ".$p->parameters()."\n", FILE_APPEND | LOCK_EX); // storing message to local disk
		$p->state()->movetostate("in_chat"); // moving to state "in_chat"
		$p->bot()->send_message($p->chatid(), $answer);
		return logarray('text', $answer);
	}
	catch(Exception $e) { return false; } // you can also return what you prefer
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
		$p->bot()->send_message($p->chatid(), $answer);
		$response = logarray('error', $answer);
	}
	$p->state()->movetostate("in_chat"); // moving to state "in_chat"
	return $response;
}

// instantiating a new bot
$bot = new telegram_bot($TELEGRAM_TOKEN);

// receiving data sent from the user
$data = $bot->read_post_message();
$message = $data->message;
$date = $message->date;
$chatid = $message->chat->id;
$text = $message->text;

// instantiating a new triggers set
$ts = new telegram_trigger_set($TELEGRAM_BOTNAME, $chatid, $singletrigger);

// registering the triggers
$ts->register_trigger_text_command("trigger_welcome", ["/start","/welcome","/hi"], 0, null); // initial state
$ts->register_trigger_text_command("trigger_help", ["/help"], 0, "in_chat"); // /help command is accepted only when state is "in_chat"
$ts->register_trigger_text_command("trigger_write", ["/write"], "in_chat"); // /write command is accepted only when state is "in_chat"
$ts->register_trigger_any("trigger_input", "waiting_for_input"); // each input retrieved will trigger the trigger_input function when state is "waiting_for_input"
// error trigger
$ts->register_trigger_error("trigger_err", "*"); // this trigger is registered indipendently on the state

// running triggers management
$response = $ts->run($bot, $message); // returns an array of triggered events

// log messages exchange on the database
db_log($TELEGRAM_BOTNAME, 'recv', $chatid, 'text', $text, $date);
if(count($response)>0) foreach($response as $r) db_log($TELEGRAM_BOTNAME, 'sent', $chatid, $r['type'], $r['content'], $date);
else db_log($TELEGRAM_BOTNAME, 'error', $chatid, 'Error', $date);
?>
