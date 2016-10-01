<?php
// call example:
//  send.php?chatid=$value&message=Hello%20world!
// see https://github.com/auino/php-telegram-bot-library for more information

// including the library
require("lib/telegram.php");

// if already configured on config.php file, delete/comment following lines
$TELEGRAM_BOTNAME = "samplebot";
$TELEGRAM_TOKEN = "...";
$STATUS_ENABLE = false;

// checking input parameters
if(!isset($_GET['message'])) {
	echo "Please specify a message to send."
	exit();
}

// getting input parameters
$chatid = null; // send message to all registered chats
if(isset($_GET['chatid'])) $chatid = $_GET['chatid'];
$message = $_GET['message'];

// checking if logs are enabled (see https://github.com/auino/php-telegram-bot-library for more information)
if(!LOGS_ENABLED) {
	echo "Logs are not enabled for this bot."
	exit();
}

// creating the bot object
$bot = new telegram_bot($TELEGRAM_TOKEN);

try {
	// creating base chat list
	$chatlist = array();
	if($chatid == null) array_push($chatlist, $chatid);
	else $chatlist = db_getchatlist($TELEGRAM_BOTNAME);
	// iterating over the chat list
	foreach($chatlist as $chat) {
		// getting current date
		$date = (string)time()
		// sending the message
		$r = $bot()->send_message($chat, $message);
		// log sent message on the database
		@db_log($TELEGRAM_BOTNAME, 'sent', $chat, 'text', $message, $date);
	}
}
catch(Exception $e) {
	@db_log($TELEGRAM_BOTNAME, 'error', $chatid, 'Error', $date);
}
?>
