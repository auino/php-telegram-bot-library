# php-telegram-bot-library
A PHP library to easily write Telegram Bots

This library allows you to easily set up a PHP based Telegram Bot.

### Features ###
 * Callback based
 * Transparent text parsing management
 * Simplified communication through the Telegram protocol (as an extension of [gorebrau/PHP-telegram-bot-API](https://github.com/gorebrau/PHP-telegram-bot-API))
 * Actions support (i.e. the top "Bot is sending a picture..." notification is implicit)
 * Self-signed SSL certificate support
 * Simplified Log managemnet ([MySQL](http://www.mysql.com) based)

### Installation ###
 0. Clone the repository on your server:
    ```
    git clone https://github.com/auino/php-telegram-bot-library.git
    ```
 1. Generate a self-signed SSL certificate, if needed ([instructions by Telegram](https://core.telegram.org/bots/self-signed) may be useful)
 2. Place the public `certificate.pem` certificate in the root directory of `php-telegram-bot-library`
 3. Open the `lib/config.php` file and set up configuration parameters accordingly to your needs
 4. Open the `install.php` file:
   1. Set the `$SSLCERTIFICATEFILE` parameter to point to your local public certificate (put the `@` symbol before the name of the file)
   2. Set the `$WEBHOOKURL` parameter to point your (HTTPS) webhook
   3. Set the `$TOKEN` parameter accordingly to the Telegram token of your bot
 6. Run `install.php` by opening the relative URL on a browser, or directly from command line: `php install.php`
 7. Remove `install.php` and the public SSL certificate inside of the root directory of `php-telegram-bot-library`

If you need to delete the registed webhook, you can set `$DELETEEXISTENTWBHOOK = true` inside of the `install.php` file and run it.

### Instructions ###
First of all, if you're not familiar with it, consult [official Telegram Bot API](https://core.telegram.org/bots).

Then, the `lib` directory (as configured after installation, see `lib/config.php`) should be included into your project.

Assuming that, the first step is to include the library: this is possible through a single simple command:

```
require("lib/telegram.php");
```

Hence, it is needed to instantiate a new bot:

```
$bot = new telegram_bot($token);
```

where `$token` is the Telegram token of your bot.

Accordingly to [gorebrau/PHP-telegram-bot-API](https://github.com/gorebrau/PHP-telegram-bot-API), it's possible to get received data through the `$bot` object:

```
$message = $bot->read_post_message();
$date = $message->message->date;
$chatid = $message->message->chat->id;
$text = $message->message->text;
```

It's now possible to set up triggers for specific commands:

```
$ts->register_trigger("trigger_welcome", ["/start","/welcome","/hi"], 0);
```

where `trigger_welcome` is the name of the triggered/callback function and `0` identifies the number of parameters accepted (considering the remaining of the received text, splitted by spaces; `-1` is used to trigger independently on the number of parameters).

At this point, it is assumed that a `trigger_welcome` function is defined:

```
function trigger_welcome($p) {
	$answer = "Welcome...";
	$p->bot()->send_message($p->chatid(), $answer);
	return logarray('text', $answer);
}
```

In particular, a single parameter of class `telegram_function_parameters` is always passed to the trigger/callback.

Following functions are available on `telegram_function_parameters` objects:
 * `bot()` returning the instance of the bot
 * `chatid()` returning the identifier of the origin chat/sender
 * `parameters()` returning an array of parameters passed to the function

The `logarray()` function returns an associative array with `type` and `content` keys, used for logging purposes:
in this case, a `text` log (each value is good) containing the `$answer` content is returned.

This bot would simply respond `/start`, `/welcome`, and `/hi` messages with a simple `Welcome...` message.

Relatively to sending instructions, accordingly to [gorebrau/PHP-telegram-bot-API](https://github.com/gorebrau/PHP-telegram-bot-API), following methods are supported:
 * `send_action($to, $action)`
 * `send_message($to, $msg, $id_msg=null, $reply=null)`
 * `send_location($to, $lat, $lon, $id_msg=null, $reply=null)`
 * `send_sticker($to, $sticker, $id_msg=null, $reply=null)`
 * `send_video($to, $video, $id_msg=null, $reply=null)`
 * `send_photo($to, $photo, $caption=null, $id_msg=null, $reply=null)`
 * `send_audio($to, $audio, $id_msg=null, $reply=null)`
 * `send_document($to, $document, $id_msg=null, $reply=null)`

After the triggers have been configured (it's possible to set up multiple triggers/callbacks: in case of multiple triggers associated to the same message/text, each callback is triggered), the triggering process have to be executed:

```
$response = $ts->run($bot, $chatid, $text);
```

where `$response` returns the result of the callback (which should be the result of a `logarray()` call).
If `$response` is `false`, something goes wrong.

At the end, it's possible to log receive and send events:

```
db_log($botname, 'recv', $chatid, 'text', $text, $date);
db_log($botname, 'sent', $chatid, $response['type'], $response['content'], $date);
```

### Sample Bot ###
Here is the PHP code of a sample bot (check the `sample/sample.php` file and configure the `$token` variable before running it).

```
// including the library
require("lib/telegram.php");

// basic configuration
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

// registering the triggers
$ts->register_trigger("trigger_welcome", ["/start","/welcome","/hi"], 0);
$ts->register_trigger("trigger_help", ["/help"], 0);
$ts->register_trigger("trigger_help", ["/getphoto","/photo","/picture"], -1); // parameters count is ignore

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
```
