<?php
// including the library
require("lib/telegram.php");

// if already configured on config.php file, delete/comment following lines
$TELEGRAM_BOTNAME = "lenabot";
$TELEGRAM_TOKEN = "...";
$STATUS_ENABLE = false;

// basic configuration
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
        $p->bot()->send_photo($p->chatid(), "$pic", $caption);
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
        $p->bot()->send_message($p->chatid(), $answer);
        $response = logarray('error', $answer);
    }
    return $response;
}

// instantiating a new bot
$bot = new telegram_bot($TELEGRAM_TOKEN);

// receiving data sent from the user
$data = $bot->read_post_message();
$message = $data->message;
$date = $message->date;
$chatid = $message->chat->id;
$text = @$message->text;

// instantiating a new triggers set
$ts = new telegram_trigger_set($TELEGRAM_BOTNAME, $chatid, $singletrigger);

// registering the triggers
$ts->register_trigger_text_command("trigger_welcome", ["/start","/welcome","/hi"], 0); // state parameter is ignored
$ts->register_trigger_text_command("trigger_help", ["/help"], 0); // state parameter is ignored
$ts->register_trigger_text_command("trigger_photo", ["/getphoto","/photo","/picture"]); // state and count parameters are ignored
// error trigger
$ts->register_trigger_error("trigger_err"); // state parameter is ignored

// running triggers management
$response = $ts->run($bot, $message); // returns an array of triggered events

// log messages exchange on the database
@db_log($TELEGRAM_BOTNAME, 'recv', $chatid, 'text', $text, $date);
if(count($response)>0) foreach($response as $r) @db_log($TELEGRAM_BOTNAME, 'sent', $chatid, $r['type'], $r['content'], $date);
else @db_log($TELEGRAM_BOTNAME, 'error', $chatid, 'Error', $date);
?>
