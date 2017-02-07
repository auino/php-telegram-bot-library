# php-telegram-bot-library
A PHP library to easily write Telegram Bots.

Original project is [auino/php-telegram-bot-library](https://github.com/auino/php-telegram-bot-library).

This library allows you to easily set up a PHP based Telegram Bot.

### Features ###

 * Callback based
 * State machines support
 * Transparent text parsing management
 * Non-text messages support (i.e. photo, video, location, etc.)
 * Simplified communication through the Telegram protocol (as an extension of [gorebrau/PHP-telegram-bot-API](https://github.com/gorebrau/PHP-telegram-bot-API))
 * Actions support (i.e. the top "Bot is sending a picture..." notification is implicit)
 * Inline mode support (thanks Daniele for your support)
 * Self-signed SSL certificate support
 * Simplified log management ([MySQL](http://www.mysql.com) based)
 * Support to programmed messages send (both broadcast or targeted to a specific chat)

### Installation ###

 1. Create a new Telegram bot by contacting [@BotFather](http://telegram.me/botfather) and following [official Telegram instructions](https://core.telegram.org/bots#botfather)
 2. Clone the repository on your server:

    ```sh
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

For manual webhook registration from command line, use the following command (e.g., `$SSLCERTIFICATEFILE = "@/etc/apache2/certs/telegram/telegram.pem"`):
```sh
curl -F "url=$WEBHOOKURL" -F "certificate=$SSLCERTIFICATEFILE" https://api.telegram.org/bot$T/setWebhook
```

If you have an already valid SSL certificate, ignore the `$SSLCERTIFICATEFILE` parameter and set `$REGISTERSELFSIGNEDCERTIFICATE = false`.

If you need to delete the registed webhook, you can set `$DELETEEXISTENTWBHOOK = true` inside of the `install.php` file and run it.

### Instructions ###

First of all, if you're not familiar with it, consult [official Telegram Bot API](https://core.telegram.org/bots).

Then, the `lib` directory (as configured after installation, see `lib/config.php`) should be included into your project.

Assuming that, the first step is to include the library: this is possible through a single simple command:

```php
require("lib/telegram.php");
```

Hence, it is needed to instantiate a new bot:

```php
$bot = new telegram_bot($token);
```

where `$token` is the Telegram token of your bot.

Accordingly to [gorebrau/PHP-telegram-bot-API](https://github.com/gorebrau/PHP-telegram-bot-API), it's possible to get received data through the `$bot` object:

```php
$data = $bot->read_post_message();
$message = $data->message;
$date = $message->date;
$chatid = $message->chat->id;
$text = @$message->text;
```

The next step is to instantiate a new trigger set:

```php
$ts = new telegram_trigger_set($botname, $chatid, $singletrigger);
```

where `$chatid` (optional, needed only if state machine functionality is enabled) identifies the chat identifier of the current message.
Instead, `$singletrigger` (optional, equal to `true` or `false`) identifies the support to multiple triggers.
Specifically, if `$singletrigger=true`, at most, a single trigger will be called.
Otherwise, multiple triggers may be called (e.g. it can be useful to support multiple answers for a single received message).

#### State Machines ####

This library supports automatic states management.

In order to switch state inside of a trigger function, you can use the following command:

```php
$p->state()->movetostate($newstate);
```

where `$p` identifies the `telegram_function_parameters` object passed to the trigger and `$newstate` identifies the new considered state.

#### Triggers ####

It's now possible to set up triggers for specific commands:

```php
$ts->register_trigger_text_command("trigger_welcome", ["/start","/welcome","/hi"], 0, $state);
```

where `trigger_welcome` is the name of the triggered/callback function and `0` identifies the number of parameters accepted (considering the remaining of the received text, splitted by spaces; `-1`, or no parameter, is used to trigger the function independently on the number of parameters).

If `0` parameters are considered, you can also set up a phrase command to use as a trigger.
For instance, a `"Contact the staff"` command may be executed through a [custom keyboard](https://core.telegram.org/bots#keyboards).
For commands not starting with a `/` symbol, or not directly sent to the bot, [privacy mode](https://core.telegram.org/bots#privacy-mode) changes may be required.

The last `$state` parameter (optional, needed only if state machine functionality is enabled) identifies that the trigger is registered on the specified state, where `null` identifies the initial state and `"*"` identifies that the trigger has to be registered on each considered state.
Alternatively, you can define a custom string identifying the prefered state.
More information are given in the [State Machines section](https://github.com/auino/php-telegram-bot-library#state-machines).

##### Triggers Definition #####

At this point, it is assumed that a `trigger_welcome` function is defined:

```php
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
 * `text()` returning received text (as string)
 * `parameters()` returning an array of parameters (represented as strings) passed to the function
 * `message()` returning a Telegram [Message object](https://core.telegram.org/bots/api#message) representing the received message
 * `fileid()` returning the file identifier, if a file is embedded in the message, `null` otherwise
 * `type()` returning the message type (as string; following values are available: `'photo'`, `'video'`, `'audio'`, `'voice'`, `'document'`, `'sticker'`, `'contact'`, `'location'`, `'text'`, `'other'`)

The `logarray()` function returns an associative array with `type` and `content` keys, used for logging purposes:
in this case, a `text` log (each value is good) containing the `$answer` content is returned.

This bot would simply respond `/start`, `/welcome`, and `/hi` messages with a simple `Welcome...` message.

Similarly, it's possible to register a trigger to use when a message includes specific text (case insensitive check):

```php
$ts->register_trigger_text_intext("trigger_hello", ["hello"], $state);
```

where the `$state` parameter is optional and `trigger_hello` identifies the triggered/callback function and `["hello"]` identifies the texts triggering that function.
For instance, in this case, if the message `Hello World!` is received, the `trigger_hello` function is called.
Note that in this case the [privacy mode](https://core.telegram.org/bots#privacy-mode) of your Telegram bot should be configured accordingly to your needs.

The last `$state` parameter (optional, needed only if state machine functionality is enabled) identifies that the trigger is registered on the specified state, where `null` identifies the initial state and `"*"` identifies that the trigger has to be registered on each considered state.
Alternatively, you can define a custom string identifying the prefered state.
More information are given in the [State Machines section](https://github.com/auino/php-telegram-bot-library#state-machines).

Also, it's possible to register a single generic trigger to use for each received command:

```php
$ts->register_trigger_any("one_trigger_for_all", $state);
```

where the `$state` parameter is optional and `one_trigger_for_all` is the name of the triggered/callback function.

The last `$state` parameter (optional, needed only if state machine functionality is enabled) identifies that the trigger is registered on the specified state, where `null` identifies the initial state and `"*"` identifies that the trigger has to be registered on each considered state.
Alternatively, you can define a custom string identifying the prefered state.
More information are given in the [State Machines section](https://github.com/auino/php-telegram-bot-library#state-machines).

Finally, it's possible to register a trigger to use if anything goes wrong:

```php
$ts->register_trigger_error("trigger_err", $state);
```

where the `$state` parameter is optional and `trigger_err` is the name of the triggered/callback function.

The last `$state` parameter (optional, needed only if state machine functionality is enabled) identifies that the trigger is registered on the specified state, where `null` identifies the initial state and `"*"` identifies that the trigger has to be registered on each considered state.
Alternatively, you can define a custom string identifying the prefered state.
More information are given in the [State Machines section](https://github.com/auino/php-telegram-bot-library#state-machines).

If `$singletrigger=true` (see description in the [Instructions section](https://github.com/auino/php-telegram-bot-library#instructions)), accordingly to registration function names, the order of triggering is the following one: trigger_any, trigger_text_command, trigger_text_intext, trigger_error.

##### Non-Text Triggers #####

Following triggers are currently supported, relatively to non-text messages.
In general, consider the `$state` parameter optional.

 * Photo trigger:

   ```php
   $ts->register_trigger_photo("trigger_photo", $state);
   ```

 * Video trigger:

   ```php
   $ts->register_trigger_video("trigger_video", $state);
   ```

 * Audio trigger:

   ```php
   $ts->register_trigger_audio("trigger_audio", $state);
   ```

 * Voice trigger:

   ```php
   $ts->register_trigger_voice("trigger_voice", $state);
   ```

 * Document trigger:

   ```php
   $ts->register_trigger_document("trigger_document", $state);
   ```

 * Sticker trigger:

   ```php
   $ts->register_trigger_sticker("trigger_sticker", $state);
   ```

 * Contact trigger:

   ```php
   $ts->register_trigger_contact("trigger_sticker", $state);
   ```

 * Location trigger:

   ```php
   $ts->register_trigger_location("trigger_location", $state);
   ```

#### Supported Telegram Actions ####

Relatively to messages sending instructions, accordingly to [gorebrau/PHP-telegram-bot-API](https://github.com/gorebrau/PHP-telegram-bot-API) and [official Telegram Bot API](https://core.telegram.org/bots/api#sendchataction), following methods are supported:
 * `send_action($to, $action)`
 * `send_message($to, $msg, $id_msg=null, $reply=null, $type=null, $disable_preview=true)`
 * `send_photo($to, $photo, $caption=null, $id_msg=null, $reply=null)`
 * `send_video($to, $video, $caption=null, $id_msg=null, $reply=null)`
 * `send_audio($to, $audio, $id_msg=null, $reply=null)`
 * `send_voice($to, $voice, $id_msg=null, $reply=null)`
 * `send_document($to, $document, $caption=null, $id_msg=null, $reply=null)`
 * `send_sticker($to, $sticker, $id_msg=null, $reply=null)`
 * `send_location($to, $lat, $lon, $id_msg=null, $reply=null)`

Concerning the `send_message` function, accordingly to [formatting options](https://core.telegram.org/bots/api#formatting-options) provided by Telegram API, `"Markdown"` or `"HTML"` values of the `$type` parameter can be provided.

Moreover, accordingly to [official Telegram Bot API regarding messages updates](https://core.telegram.org/bots/api#updating-messages), following methods are instead supported:
 * `edit_message($chatid=null, $message_id=null, $text, $inline_message_id=null, $parse_mode=null, $disable_web_page_preview=null, $reply_markup=null)`
 * `edit_caption($chatid=null, $message_id=null, $inline_message_id=null, $caption=null, $reply_markup=null)`
 * `edit_replymarkup($chatid=null, $message_id=null, $inline_message_id=null, $reply_markup=null)`

It is also possible to retrive a file from `$file_id` and store it to `$output_file` through the following function:

```php
$filename = get_file($file_id, $output_file)
```

where `$file_id` is retrieved through `telegram_function_parameters` class methods (see [Triggers Definition](https://github.com/auino/php-telegram-bot-library#triggers-definition) section).
Returned value is the full `$filename` including file extension (computed by appending the extension to the `$output_file` parameter), `null` if something goes wrong.

#### Automated Triggering ####

After the triggers have been configured (it's possible to set up multiple triggers/callbacks: in case of multiple triggers associated to the same message/text, each callback is triggered), the triggering process have to be executed:

```php
$response = $ts->run($bot, $message);
```

where `$message` is a Telegram [Message object](https://core.telegram.org/bots/api#message) (in the sample reported in [Instructions section](https://github.com/auino/php-telegram-bot-library#instructions), `$message = $data->message`).
Returned `$response` object is an array of resulting values for the executed callbacks (which should be the result of a `logarray()` call).
If `$response` is an empty array, nothing has been triggered.

In case state machine functionality is enabled, if the `run()` function is called more than a single time, it will be bounded to the initial state.
It is possible to bind it to the new state (different from initial one) by re-instantiating a new trigger_set object and re-registering the callbacks.

#### Inline mode ####

This library supports [Telegram inline bots mode](https://core.telegram.org/bots/inline) defined in [official Telegram API](https://core.telegram.org/bots/api#inline-mode).

In order to support inline mode, you have first to enable it by contacting [@BotFather](http://telegram.me/botfather).

Hence, you have to implement inline support in your main PHP script: first of all, you have to retrieve an `$inline_query` object and relative identifier.

```php
// reading inline query basic data
$inline_query = $data->inline_query;
$inline_query_id = $inline_query->id;
```

In case inline mode is used, we expect `$inline_query_id != ""`.
Otherwise, the standard/common messaging method is used.

Therefore, in order to support inline mode, it's possible to set up an `if` statement such as the following one.

```php
// managing inline query results
if($inline_query_id != "") {
	// inline data management
	// ...
	exit();
}
```

It's possible to obtain inline query data as a InlineQuery object defined in the [official Telegram API](https://core.telegram.org/bots/api#inlinequery).
Results shown to the user are represented as an array of InlineQueryResult objects (see [official Telegram API](https://core.telegram.org/bots/api#inlinequeryresult)).

Finally it's possible to send inline content to the user:

```php
$results = json_encode($results_array);
$bot->send_inline($inline_query_id, $results);
```

For a sample of usage of inline mode, see the [Inline Bot sample](https://github.com/auino/php-telegram-bot-library#inline-bot) below.

#### Logging ####

At the end, it's possible to log receive and send events to database:

```php
@db_log($botname, 'recv', $chatid, 'text', $text, $date);
@db_log($botname, 'sent', $chatid, $response['type'], $response['content'], $date);
```

where the initial `@` character prevents logging errors (i.e. in case of unsupported message types) to (Apache2) `error.log` file.

#### Database Utilities ####

Following functions are available on the configured database:
 * `db_connect()` to connect to the database, returns a `$connection` object
 * `db_close($connection)` to interrupt the connection with the database; returns nothing
 * `db_nonquery($query)` to run a "non query" (i.e. operations such as `UPDATE`, `INSERT`, etc.) on the database (connection and closure are automatically executed); returns a boolean value for success/failure
 * `db_query($query)` to run a query (i.e. `SELECT`) on the database (connection and closure are automatically executed); returns an array of records
 * `db_randomone($table, $filter=null)`: this function is useful on non performant devices (i.e. a single-core Raspberry PI) to get a single random element from a `$table` without using the SQL `RAND()` function, loading results in memory; `$filter` may be, e.g., equal to `Orders.total > 1000`; returns the pointer to the results of the query

### Programmed messages send ###

This library allows the programmer to send targeted messages to specific users or broadcast messages to all registered users (useful, for instance, when coupled with `cron`).

This is possible by opening from a browser the `send.php` file and specifying the following `GET` parameters:
 * `chatid` representing the chat identifier of the recipient
 * `message` representing the message to send to the recipient (only text messages are supported, at least for now)
If the `chatid` parameter is empty, the message will be sent to each user.

Access to `send.php` file should be protected, e.g., through basic authentication (see [Apache2 help on ServerFault](http://serverfault.com/a/151305)).

This functionality makes use of the logging capabilities of the library.
Therefore, if [logging](https://github.com/auino/php-telegram-bot-library#logging) is not enabled/supported, programmed message send will not work.

#### Retrieve chat identifier ####

It could be done, for instance, by looking at the `Logs` table in the database.

Otherwise, it is possible to create a simple trigger, as reported in the following sample code.

```php
// trigger registration
$ts->register_trigger_text_command("trigger_chatid", ["/chatid"], 0);

// callback definition
function trigger_chatid($p) {
	try {
		$answer = "Your chat identifier is ".$p->chatid();
		$p->bot()->send_message($p->chatid(), $answer);
		return logarray("text", $answer);
	}
	catch(Exception $e) { return false; }
}
```

#### Usage sample ####

Usage sample is reported in the following.

```
send.php?chatid=$value&message=Hello%20world!
```

Please consider that the `message` parameter should be [urlencoded](http://php.net/manual/en/function.urlencode.php).

### Notes for who's upgrading ###

Unlike previous versions of the library, the `$chatid` parameter is no more required in the `$ts->run()` function.
Moreover, the `$text` parameter is replaced by a `$message` parameter, retrieved through `$data->message` (see [Instructions section](https://github.com/auino/php-telegram-bot-library#instructions)).

```php
$response = $ts->run($bot, $message);
```

It is instead required by the `telegram_triggers_set` constructor.

```php
$ts = new telegram_trigger_set($botname, $chatid, $singletrigger);
```

Relatively to the previous `$ts->register_trigger_command` and `$ts->register_trigger_intext` functions, since we now support non-text messages (i.e. photos, location, etc.), method names have been respectively changed to `$ts->register_trigger_text_command` and `$ts->register_trigger_text_intext`.

### Sample Bots ###

Here is the PHP code of two sample bots (check the `sample` directory and configure the `$token` variable before running the bots).

#### Lena Bot ####

This is a trivial bot simply returning a picture of [Lena](https://en.wikipedia.org/wiki/Lenna) when the `/lena` command is triggered.

Check [sample/lena.php](https://github.com/auino/php-telegram-bot-library/blob/master/sample/lena.php) file for the commented source code.

#### Write-to-Developer Bot ####

This bot makes use of states functionality offered by the library and simply provides a way to write to the developer.

Considered states are:
 1. `null` initial state (e.g. `/start` messages are triggered here)
 2. `"in_chat"` state, entered after `/start` command is executed; in this state, the bot is waiting for a command (`/help` and `/write` commands are accepted here)
 3. `"waiting_for_input"` state, accepting any input from the user, hence registering the message to a local file and entering back to the `"in_chat"` state

Check [sample/writetodev.php](https://github.com/auino/php-telegram-bot-library/blob/master/sample/writetodev.php) file for the commented source code.

#### Inline Bot ####

This is a simple bot supporting inline mode.

When using the bot in inline mode, it will provide three possible choices/results.
After the choice is accomplished, the bot will send a customized message to the user.

Check [sample/inline.php](https://github.com/auino/php-telegram-bot-library/blob/master/sample/inline.php) file for the commented source code.

### Real bots ###

Following bots have been implemented through this library.

* [@programmablebot](https://telegram.me/programmablebot)
* [@italiawebcambot](https://telegram.me/italiawebcambot)
* [@tabbythebot](https://telegram.me/tabbythebot) (channel reporting Daniele's bots; see [acknowledgements](https://github.com/auino/php-telegram-bot-library#acknowledgements))

Contact me to add your bot/channel to the list.

### Acknowledgements ###

* Thanks Daniele for your support and study of [Telegram inline mode](https://core.telegram.org/bots/inline).
* Thanks [ByteRam](https://github.com/ByteRam) for `send_voice` suggestion.
* Thanks [CheatCoder](https://github.com/CheatCoder) for your suggestions concerning `mysqli` library and [Telegram messages updates](https://core.telegram.org/bots/api#updating-messages) support.

### Contacts ###

You can find me on Twitter as [@auino](https://twitter.com/auino).
