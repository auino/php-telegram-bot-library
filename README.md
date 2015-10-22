# php-telegram-bot-library
A PHP library to write Telegram Bots

This library allows you to easily set up a PHP based Telegram Bot.

### Features ###
 * Callback based
 * Transparent text parsing management
 * Simplified communication through the Telegram protocol (as an extension of [gorebrau/PHP-telegram-bot-API](https://github.com/gorebrau/PHP-telegram-bot-API))
 * Actions support (i.e. the top "Bot is sending a picture..." notification is implicit)
 * Self-signed SSL certificate support
 * Simplified Log managemnet ([MySQL](http://www.mysql.com) based)

### Installation ###
 1. Generate a self-signed SSL certificate, if needed ([instructions by Telegram](https://core.telegram.org/bots/self-signed) may be useful)
 2. Place the public `certificate.pem` certificate in the root directory of `php-telegram-bot-library`
 3. Open the `lib/config.php` file and set up configuration parameters accordingly to your needs
 4. Open the `install.php` file and set `$SSLCERTIFICATEFILE` to point to your local public certificate (put the `@` symbol before the name of the file)
 5. Configure the `$WEBHOOKURL` on `install.php` to point your (HTTPS) webhook
 6. Run `install.php` by opening the relative URL on a browser, or directly from command line: `php install.php`
 7. Remove `install.php` and the public SSL certificate inside of the root directory of `php-telegram-bot-library`

### Instructions ###
First of all, the `lib` directory (as configured after installation, see `lib/config.php`) should be included into your project.

Assuming that, the first step is to include the library: this is possible through a single simple command:

`require('lib/telegram.php');`

Hence, it is needed to instantiate a new bot:

`$bot = new telegram_bot($token);`

where `$token` is the Telegram token of your bot.

It's now possible to set up triggers for specific commands:

`$ts->register_trigger("trigger_welcome", ["/welcome","/hi"], 0);`

where `trigger_welcome` is the name of the triggered/callback function and `0` identifies the number of parameters accepted (considering the remaining of the received text, splitted by spaces; `-1` is used to trigger independently on the number of parameters).

At this point, it is assumed that a `trigger_welcome` function is defined.

`function trigger_welcome($p) {
	$answer = "Welcome...";
	$p->bot()->send_message($p->chatid(), $answer);
	return logarray('text', $answer);
}`

In particular, a single parameter of class `telegram_function_parameters` is always passed to the trigger/callback.

Following functions are available on `telegram_function_parameters` objects:
 * `bot()` returning the instance of the bot
 * `chatid()` returning the identifier of the origin chat/sender
 * `parameters()` returning an array of parameters passed to the function

The `logarray()` function returns an associative array with `type` and `content` keys, used for logging purposes:
in this case, a `text` log (each value is good) containing the `$answer` content is returned.

This bot would simply respond `/welcome` and `/hi` messages with a simple `Welcome...` message.

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

`$response = $ts->run($bot, $chatid, $text);`

where `$response` returns the result of the callback (which should be the result of a `logarray()` call).
If `$response` is `false`, something goes wrong.

At the end, it's possible to log receive and send events:

`db_log($botname, 'recv', $chatid, 'text', $text, $date);

db_log($botname, 'sent', $chatid, $response['type'], $response['content'], $date);`

