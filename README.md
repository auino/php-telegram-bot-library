# php-telegram-bot-library
A PHP library to easily write Telegram Bots.

Original project is [auino/php-telegram-bot-library](https://github.com/auino/php-telegram-bot-library).

This library allows you to easily set up a PHP based Telegram Bot.

### Features ###

 * Callback based
 * State machines support
 * Transparent text parsing management
 * Simplified communication through the Telegram protocol (as an extension of [gorebrau/PHP-telegram-bot-API](https://github.com/gorebrau/PHP-telegram-bot-API))
 * Actions support (i.e. the top "Bot is sending a picture..." notification is implicit)
 * Self-signed SSL certificate support
 * Simplified Log management ([MySQL](http://www.mysql.com) based)

### Installation ###

 1. Create a new Telegram bot by contacting [@BotFather](http://telegram.me/botfather) and following [official Telegram instructions](https://core.telegram.org/bots#botfather)
 2. Clone the repository on your server:

    ```
    git clone https://github.com/auino/php-telegram-bot-library.git
    ```

 3. Generate a self-signed SSL certificate, if needed ([instructions by Telegram](https://core.telegram.org/bots/self-signed) may be useful)
 4. Place the public `certificate.pem` certificate in the root directory of `php-telegram-bot-library`
 5. Open the `lib/config.php` file and set up configuration parameters accordingly to your needs
 6. Open the `install.php` file:
   1. Set the `$SSLCERTIFICATEFILE` parameter to point to your local public certificate (put the `@` symbol before the name of the file)
   2. Set the `$WEBHOOKURL` parameter to point your (HTTPS) webhook
   3. Set the `$TOKEN` parameter accordingly to the Telegram token of your bot
 7. Run `install.php` by opening the relative URL on a browser, or directly from command line: `php install.php`
 8. Optionally, you can now remove `install.php` and the public SSL certificate inside of the root directory of `php-telegram-bot-library`

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

The next step is to instantiate a new trigger set:

```
$ts = new telegram_trigger_set($botname, $chatid, $singletrigger);
```

where `$chatid` (optional, needed only if state machine functionality is enabled) identifies the chat identifier of the current message.
Instead, `$singletrigger` (optional, equal to `true` or `false`) identifies the support to multiple triggers.
Specifically, if `$singletrigger=true`, at most, a single trigger will be called.
Otherwise, multiple triggers may be called (e.g. it can be useful to support multiple answers for a single received message).

#### Triggers ####

It's now possible to set up triggers for specific commands:

```
$ts->register_trigger_command("trigger_welcome", ["/start","/welcome","/hi"], 0, $state);
```

where `trigger_welcome` is the name of the triggered/callback function and `0` identifies the number of parameters accepted (considering the remaining of the received text, splitted by spaces; `-1`, or no parameter, is used to trigger the function independently on the number of parameters).

If `0` parameters are considered, you can also set up a phrase command to use as a trigger.
For instance, a `"Contact the staff"` command may be executed through a [custom keyboard](https://core.telegram.org/bots#keyboards).
For commands not starting with a `/` symbol, or not directly sent to the bot, [privacy mode](https://core.telegram.org/bots#privacy-mode) changes may be required.

The last `$state` parameter (optional, needed only if state machine functionality is enabled) identifies that the trigger is registered on the specified state, where `null` identifies the initial state and `"*"` identifies that the trigger has to be registered on each considered state.
Alternatively, you can define a custom string identifying the prefered state.
More information are given in the State Machines section.

##### Triggers Definition #####

At this point, it is assumed that a `trigger_welcome` function is defined:

```
// function declaration
function trigger_welcome($p) {
	// the reply string
	$answer = "Welcome...";
	// send the reply message
	$p->bot()->send_message($p->chatid(), $answer);
	// return an array log object with type text and answer as content
	return logarray('text', $answer);
}
```

In particular, a single parameter of class `telegram_function_parameters` is always passed to the trigger/callback.

Following functions are available on `telegram_function_parameters` objects:
 * `bot()` returning the instance of the bot
 * `chatid()` returning the identifier of the origin chat/sender
 * `state()` returning a `trigger_state` object representing the state of the current chat
 * `parameters()` returning an array of parameters passed to the function (a [Message](https://core.telegram.org/bots/api#message) object of official Telegram API)

The `logarray()` function returns an associative array with `type` and `content` keys, used for logging purposes:
in this case, a `text` log (each value is good) containing the `$answer` content is returned.

This bot would simply respond `/start`, `/welcome`, and `/hi` messages with a simple `Welcome...` message.

Similarly, it's possible to register a trigger to use when a message includes specific text (case insensitive check):

```
$ts->register_trigger_intext("trigger_hello", ["hello"], $state);
```

where `trigger_hello` identifies the triggered/callback function and `["hello"]` identifies the texts triggering that function.
For instance, in this case, if the message `Hello World!` is received, the `trigger_hello` function is called.
Note that in this case the [privacy mode](https://core.telegram.org/bots#privacy-mode) of your Telegram bot should be configured accordingly to your needs.

The last `$state` parameter (optional, needed only if state machine functionality is enabled) identifies that the trigger is registered on the specified state, where `null` identifies the initial state and `"*"` identifies that the trigger has to be registered on each considered state.
Alternatively, you can define a custom string identifying the prefered state.
More information are given in the State Machines section.

Also, it's possible to register a single generic trigger to use for each received command:

```
$ts->register_trigger_any("one_trigger_for_all", $state);
```

where `one_trigger_for_all` is the name of the triggered/callback function.

The last `$state` parameter (optional, needed only if state machine functionality is enabled) identifies that the trigger is registered on the specified state, where `null` identifies the initial state and `"*"` identifies that the trigger has to be registered on each considered state.
Alternatively, you can define a custom string identifying the prefered state.
More information are given in the State Machines section.

Finally, it's possible to register a trigger to use if anything goes wrong:

```
$ts->register_trigger_error("trigger_err", $state);
```

where `trigger_err` is the name of the triggered/callback function.

The last `$state` parameter (optional, needed only if state machine functionality is enabled) identifies that the trigger is registered on the specified state, where `null` identifies the initial state and `"*"` identifies that the trigger has to be registered on each considered state.
Alternatively, you can define a custom string identifying the prefered state.
More information are given in the State Machines section.

If `$singletrigger=true` (see description above), accordingly to registration function names, the order of triggering is the following one: trigger_any, trigger_command, trigger_intext, trigger_error.

##### State Machines #####

This library supports automatic states management.

In order to switch state inside of a trigger function, you can use the following command:

```
$p->state()->movetostate($newstate);
```

where `$newstate` identifies the new considered state.

#### Supported Telegram Actions ####

Relatively to sending instructions, accordingly to [gorebrau/PHP-telegram-bot-API](https://github.com/gorebrau/PHP-telegram-bot-API) and [official Telegram Bot API](https://core.telegram.org/bots/api#sendchataction), following methods are supported:
 * `send_action($to, $action)`
 * `send_message($to, $msg, $id_msg=null, $reply=null, $type=null, $disable_preview=true)`
 * `send_location($to, $lat, $lon, $id_msg=null, $reply=null)`
 * `send_sticker($to, $sticker, $id_msg=null, $reply=null)`
 * `send_video($to, $video, $id_msg=null, $reply=null)`
 * `send_photo($to, $photo, $caption=null, $id_msg=null, $reply=null)`
 * `send_audio($to, $audio, $id_msg=null, $reply=null)`
 * `send_document($to, $document, $id_msg=null, $reply=null)`

Concerning the `send_message` function, accordingly to [formatting options](https://core.telegram.org/bots/api#formatting-options) provided by Telegram API, `"Markdown"` or `"HTML"` values of the `$type` parameter can be provided.

#### Automated Triggering ####

After the triggers have been configured (it's possible to set up multiple triggers/callbacks: in case of multiple triggers associated to the same message/text, each callback is triggered), the triggering process have to be executed:

```
$response = $ts->run($bot, $text);
```

where `$response` returns an array of resulting values for the executed callbacks (which should be the result of a `logarray()` call).
If `$response` is an empty array, nothing has been triggered.

In case state machine functionality is enabled, if the `run()` function is called more than a single time, it will be bounded to the initial state.
It is possible to bind it to the new state (different from initial one) by re-instantiating a new trigger_set object and re-registering the callbacks.

#### Logging ####

At the end, it's possible to log receive and send events:

```
db_log($botname, 'recv', $chatid, 'text', $text, $date);
db_log($botname, 'sent', $chatid, $response['type'], $response['content'], $date);
```

#### Database Utilities ####

Following functions are available on the configured database:
 * `db_connect()` to connect to the database, returns a `$connection` object
 * `db_close($connection)` to interrupt the connection with the database; returns nothing
 * `db_nonquery($query)` to run a "non query" (i.e. operations such as `UPDATE`, `INSERT`, etc.) on the database (connection and closure are automatically executed); returns a boolean value for success/failure
 * `db_query($query)` to run a query (i.e. `SELECT`) on the database (connection and closure are automatically executed); returns an array of records
 * `db_randomone($table, $filter=null)`: this function is useful on non performant devices (i.e. a single-core Raspberry PI) to get a single random element from a `$table` without using the SQL `RAND()` function, loading results in memory; `$filter` may be, e.g., equal to `Orders.total > 1000`; returns the pointer to the results of the query

### Notes for who's upgrading ###

Unlike previous versions of the library, the `$chatid` parameter is no more required in the `$ts->run()` function.

```
$response = $ts->run($bot, $text);
```

It is instead required by the `telegram_triggers_set` constructor.

```
$ts = new telegram_trigger_set($botname, $chatid, $singletrigger);
```

### Sample Bots ###

Here is the PHP code of two sample bots (check the `sample` directory and configure the `$token` variable before running the bots).

#### Lena Bot ####

This bot does not make use of states functionality offered by the library and simply returns a picture of [Lena](https://en.wikipedia.org/wiki/Lenna).

Check [sample/lena.php](https://github.com/auino/php-telegram-bot-library/blob/master/sample/lena.php) file for the commented source code.

#### Write-to-Developer Bot ####

This bot makes use of states functionality offered by the library and simply provides a way to write to the developer.

Considered states are:
 1. `null` initial state (e.g. `/start` messages are triggered here)
 2. `"in_chat"` state, entered after `/start` command is executed; in this state, the bot is waiting for a command (`/help` and `/write` commands are accepted here)
 3. `"waiting_for_input"` state, accepting any input from the user, hence registering the message to a local file and entering back to the `"in_chat"` state

Check [sample/writetodev.php](https://github.com/auino/php-telegram-bot-library/blob/master/sample/writetodev.php) file for the commented source code.

### Contacts ###

You can find me on Twitter as [@auino](https://twitter.com/auino).
