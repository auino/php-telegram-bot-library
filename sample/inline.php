<?php
// Acknowledgments: thanks Daniele for your support and study of Telegram inline mode

// including the library
require("lib/telegram.php");

// basic configuration
$botname = "inlinebot";
$token = "...";
$singletrigger = true; // if true, it tells the library to trigger at most a single callback function for each received message

// custom inline results configuration related to this particular bot
$results_count = 5;
$inline_thumbs_colors = ["faa","afa","aaf"];

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
        $answer = "Try to use inline mode...";
        $p->bot()->send_message($p->chatid(), $answer);
        return logarray('text', $answer);
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
	$answer = "".print_r($p->message(), true);
        $p->bot()->send_message($p->chatid(), $answer);
        $response = logarray('error', $answer);
    }
    return $response;
}

// instantiating a new bot
$bot = new telegram_bot($token);

// receiving data sent from the user
$data = $bot->read_post_message();

// reading standard message data
$message = $data->message;
$date = $message->date;
$chatid = $message->chat->id;
$text = @$message->text;

// reading inline query basic data
$inline_query = $data->inline_query;
$inline_query_id = $inline_query->id;

// managing inline query results
if($inline_query_id != "") {
	// getting additional inline data
	$inline_query_msg = $inline_query->query;
	$inline_chatid = $inline_query->from->id;
	$inline_username = $inline_query->from->username;
	// building a list of results (of type 'article'); for further information, see https://core.telegram.org/bots/api#inlinequeryresult
	$results = array();
	for($i=1;$i<=$results_count;$i++) {
		$id = "id_$i"; // unique identifier of the content
		$title = "Title of result #$i"; // inline title
		$description = "Description of inline results #$i"; // inline description
		$message_text = "Thanks for your query '$inline_query_msg'. You have selected results #$i."; // returned message, if this result is chosen by the user
		$url = "http://dummyimage.com/100x100/".($inline_thumbs_colors[$i%count($inline_thumbs_colors)])."/000.png&text=$i"; // thumbnail url; using the external dummyimage.com service
		array_push($results, Array("type" => "article", "id" => "$id", "title" => $title, "description" => $description, "message_text" => $message_text, "parse_mode" => "HTML", "thumb_url" => $url)); // for other content types, see https://core.telegram.org/bots/api#inlinequeryresult
	}
	// sending the results
	$results = json_encode($results);
	$bot->send_inline($inline_query_id, $results); // for further information, see https://core.telegram.org/bots/api#inline-mode
	// logging sent results into database
	@db_log($botname, 'inline', $inline_query_id, 'inline', $date);
	// terminating the program
	exit();
}

// managing standard/not inline messages

// instantiating a new triggers set
$ts = new telegram_trigger_set($botname, $chatid, $singletrigger);

// registering the triggers
$ts->register_trigger_text_command("trigger_welcome", ["/start","/welcome","/hi"], 0); // state parameter is ignored
$ts->register_trigger_text_command("trigger_help", ["/help"], 0); // state parameter is ignored
// error trigger
$ts->register_trigger_error("trigger_err"); // state parameter is ignored

// running triggers management
$response = $ts->run($bot, $message); // returns an array of triggered events
// log messages exchange on the database
@db_log($botname, 'recv', $chatid, 'text', $text, $date);
if(count($response)>0) foreach($response as $r) @db_log($botname, 'sent', $chatid, $r['type'], $r['content'], $date);
else @db_log($botname, 'error', $chatid, 'Error', $date);
?>
