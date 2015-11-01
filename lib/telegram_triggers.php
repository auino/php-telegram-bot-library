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
	function __construct($n) { $this->name = $n; $this->count = -1; }
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
	private $onlyoneresponse = true;
	private $botname = null;
	private $triggers_command = array();
	private $triggers_intext = array();
	private $trigger_error = null;
	function __construct($b) { $this->botname = $b; }
	public function register_trigger_command($callback, $names, $count) {
		$evs = array();
		foreach($names as $name) array_push($evs, new telegram_event($name, $count));
		$t = new telegram_trigger($callback, $evs);
		array_push($this->triggers_command, $t);
	}
	public function register_trigger_intext($callback, $texts) {
		$evs = array();
		foreach($texts as $text) array_push($evs, new telegram_event($text));
		$t = new telegram_trigger($callback, $evs);
		array_push($this->triggers_intext, $t);
	}
	public function register_trigger_error($callback) {
		$this->trigger_error = $callback;
	}
	public function run($telegrambot, $chatid, $msg) { // text only messages (at least for now)
		$msg = str_ireplace("@".$this->botname, "", $msg);
		$msgpar = explode(" ", $msg);
		$cmd = array_shift($msgpar);
		$par = new telegram_function_parameters($telegrambot, $chatid, $msgpar);
		$res = array();
		// checking command strings
		foreach($this->triggers_command as $t) {
			$ev = $t->events();
			$c = $t->callback();
			foreach($ev as $e) {
				$name = $e->name();
				$count = $e->count();
				if((strtolower($cmd) == strtolower($name)) && ((intval($count)<0) || (intval($count)==@count($msgpar)))) {
					echo "Triggering $c...\n";
					$tmpres = call_user_func_array($c, [$par]);
					if($tmpres) array_push($res, $tmpres);
					if($this->onlyoneresponse) return $res;
				}
			}
		}
		// checking strings in text
		foreach($this->triggers_intext as $t) {
			$ev = $t->events();
			$c = $t->callback();
			foreach($ev as $e) {
				$name = $e->name();
				if(strtolower($name) in strtolower($msg) {
					echo "Triggering $c...\n";
					$fullpar = new telegram_function_parameters($telegrambot, $chatid, $msg);
					$tmpres = call_user_func_array($c, [$fullpar]);
					if($tmpres) array_push($res, $tmpres);
					if($this->onlyoneresponse) return $res;
				}
			}
		}
		// triggering error, if needed
		if((count($res)<=0) && ($this->trigger_error != null)) array_push($res, call_user_func_array($this->trigger_error, [$par]));
		// returning resulting array
		return $res;
	}
}
?>
