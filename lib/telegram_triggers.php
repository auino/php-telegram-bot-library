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
	private $onlyoneresponse = true;
	private $botname = null;
	private $triggers_command = null;
	private $trigger_error = null;
	function __construct($b) { $this->triggers_command = array(); $this->botname = $b; }
	public function register_trigger_command($callback, $names, $count) {
		$evs = array();
		foreach($names as $name) array_push($evs, new telegram_event($name, $count));
		$t = new telegram_trigger($callback, $evs);
		array_push($this->triggers_command, $t);
	}
	public function register_trigger_error($callback) {
		$this->trigger_error = $callback;
	}
	public function run($telegrambot, $chatid, $msg) { // text only messages (at least for now)
		$msg = str_ireplace("@".$this->botname, "", $msg);
		$msgpar = explode(" ", $msg);
		$cmd = array_shift($msgpar);
		$par = new telegram_function_parameters($telegrambot, $chatid, $msgpar);
		$res = false;
		foreach($this->triggers_command as $t) {
			$ev = $t->events();
			$c = $t->callback();
			foreach($ev as $e) {
				$name = $e->name();
				$count = $e->count();
				if((strtolower($cmd) == strtolower($name)) && ((intval($count)<0) || (intval($count)==@count($msgpar)))) {
					echo "Triggering $c...\n";
					$tmpres = call_user_func_array($c, [$par]);
					if($tmpres) {
						if(!$res) $res = array();
						array_push($res, $tmpres);
					}
					if($this->onlyoneresponse) return $res;
				}
			}
		}
		if($res) return $res;
		if($this->trigger_error != null) return array(call_user_func_array($this->trigger_error, [$par]));
		return $res;
	}
}
?>
