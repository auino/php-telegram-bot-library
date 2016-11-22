<?php
// If needed, check the official project on GitHub:
// https://github.com/auino/php-telegram-bot-library

$DELETEEXISTENTWBHOOK = false;
$REGISTERWEBHOOK = true;
$WEBHOOKURL = "https://www.yourwebsite.org/webhook.php";
$SSLCERTIFICATEFILENAME = "certificate.pem";

$SETUPDB = true;
$SETUPDBQUERIES = [
	/* if needed, insert your queries here */
];

// if needed, disable $STATES_ENABLED in lib/config.php
$LOGSDBQUERIES = [
	"CREATE TABLE `Logs` (`id` bigint(20) NOT NULL AUTO_INCREMENT, `bot` varchar(100) NOT NULL, `action` varchar(100) NOT NULL, `chat` int(14) NOT NULL, `type` varchar(30) NOT NULL, `content` varchar(250) NOT NULL, `date` varchar(30) NOT NULL, PRIMARY KEY (`id`), UNIQUE KEY `bot` (`bot`,`action`,`chat`,`date`));"
];

// if needed, enable $STATES_ENABLED in lib/config.php
$STATESDBQUERIES = [
	"CREATE TABLE `States` (`id` bigint(20) NOT NULL AUTO_INCREMENT, `bot` varchar(100) NOT NULL, `chat` int(14) NOT NULL, `state` varchar(100) DEFAULT NULL, PRIMARY KEY (`id`), UNIQUE KEY `bot` (`bot`,`chat`));"
];

include_once("lib/telegram.php");

if($DELETEEXISTENTWBHOOK) {
	echo "Deleting registered webhook...\n";
	$bot = new telegram_bot($TELEGRAM_TOKEN);
	$bot->set_webhook();
	echo "Deleted!\n";
}
else { // you can register a new webhook only if you're not deleting existent webhook
	if($REGISTERWEBHOOK) {
		echo "Registering webhook...\n";
		if(class_exists('CurlFile', false)) $SSLCERTIFICATEFILE = new CURLFile(realpath($SSLCERTIFICATEFILENAME));
		else $SSLCERTIFICATEFILE = "@$SSLCERTIFICATEFILENAME";
		$bot = new telegram_bot($TELEGRAM_TOKEN);
		//$bot->set_webhook();
		$bot->set_webhook($WEBHOOKURL, $SSLCERTIFICATEFILE);
		echo "Registered!\n";
	}
}

if($SETUPDB) {
	echo "Configuring Logs database...\n";
	foreach($SETUPDBQUERIES as $q) {
		db_nonquery($q);
	}
	echo "Configured!\n";
	if($LOGS_ENABLED) {
		echo "Configuring Logs database...\n";
		foreach($LOGSDBQUERIES as $q) {
			db_nonquery($q);
		}
		echo "Configured!\n";
	}
	if($STATES_ENABLED) {
		echo "Configuring States database...\n";
		foreach($STATESDBQUERIES as $q) {
			db_nonquery($q);
		}
		echo "Configured!\n";
	}
}

echo "Installation completed!\n";
?>
