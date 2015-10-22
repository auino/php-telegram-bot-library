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

### Bot Example ###
TODO
