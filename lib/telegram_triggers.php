<?php
class telegram_function_parameters {
	private $bot, $chatid, $par;
	function __construct($b, $c, $p) { $this->bot = $b; $this->chatid = $c; $this->par = $p; }
	function bot() { return $this->bot; }
	function chatid() { return $this->chatid; }
	function parameters() { return $this->par; }
}

class telegram_event {
	private $name, $count;
	function __construct($n, $c) { $this->name = $n; $this->count = $c; }
	function name() { return $this->name; }
	function count() { return $this->count; }
}

class telegram_trigger {
	private $callback, $events;
	function __construct($c, $e) { $this->callback = $c; $this->events = $e; }
	function callback() { return $this->callback; }
	function events() { return $this->events; }
}

class telegram_trigger_set {
	private $triggers;
	private $botname;
	function __construct($b) { $this->triggers = array(); $this->botname = $b; }
	public function register_trigger($callback, $names, $count) {
		$evs = array();
		foreach($names as $name) array_push($evs, new telegram_event($name, $count));
		$t = new telegram_trigger($callback, $evs);
		array_push($this->triggers, $t);
	}
	public function run($telegrambot, $chatid, $msg) { // text only messages (at least for now)
		$msg = str_ireplace("@".$this->botname, "", $msg);
		$msgpar = explode(" ", $msg);
		$cmd = array_shift($msgpar);
		foreach($this->triggers as $t) {
			$ev = $t->events();
			$c = $t->callback();
			foreach($ev as $e) {
				$name = $e->name();
				$count = $e->count();
				if((strtolower($cmd) == strtolower($name)) && ((intval($count)<0) || (intval($count)==@count($par)))) {
					echo "Triggering $c...\n";
					$par = new telegram_function_parameters($telegrambot, $chatid, $msgpar);
					return call_user_func_array($c, [$par]);
				}
			}
		}
		return false;
	}
}
?>
