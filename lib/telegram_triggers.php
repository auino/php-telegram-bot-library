<?php
class telegram_function_parameters {
	private $bot, $chatid, $state, $msg, $text, $par;
	function __construct($b, $c, $s, $m, $t, $p) { $this->bot = $b; $this->chatid = $c; $this->state = $s; $this->msg = $m; $this->text = $t; $this->par = $p; }
	function bot() { return $this->bot; }
	function chatid() { return $this->chatid; }
	function state() { return $this->state; }
	function message() { return $this->msg; } // this is a Message object (see https://core.telegram.org/bots/api#message)
	function text() { return $this->text; }
	function parameters() { return $this->par; }
}

class telegram_event {
	private $name, $count;
	function __construct($n, $c=-1) { $this->name = $n; $this->count = $c; }
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
	private $singletrigger = true;
	private $botname = null;
	private $chatid = null;
	private $state = null;
	private $trigger_any = null;
	private $triggers_command = array();
	private $triggers_intext = array();
	private $trigger_error = null;
	function __construct($b, $c=null, $st=true) { $this->botname = $b; $this->chatid = $c; $this->singletrigger = $st; if($c!=null) $this->state = new telegram_state($b, $c); }
	public function register_trigger_any($callback, $state="*") {
		if(!$this->state->iscurrent($state)) return;
		$this->trigger_any = $callback;
	}
	public function register_trigger_command($callback, $names, $count=-1, $state="*") {
		if(!$this->state->iscurrent($state)) return;
		$evs = array();
		foreach($names as $name) array_push($evs, new telegram_event($name, $count));
		$t = new telegram_trigger($callback, $evs);
		array_push($this->triggers_command, $t);
	}
	public function register_trigger_intext($callback, $texts, $state="*") {
		if(!$this->state->iscurrent($state)) return;
		$evs = array();
		foreach($texts as $text) array_push($evs, new telegram_event($text, -1));
		$t = new telegram_trigger($callback, $evs);
		array_push($this->triggers_intext, $t);
	}
	public function register_trigger_error($callback, $state="*") {
		if(!$this->state->iscurrent($state)) return;
		$this->trigger_error = $callback;
	}
	public function run($telegrambot, $msg) {
		global $STATES_ENABLED;
		$text = $msg->text;
		$text = trim(str_ireplace("@".$this->botname, "", $text));
		$fullpar = new telegram_function_parameters($telegrambot, $this->chatid, $this->state, $msg, $text, [$text]);
		$res = array();
		// triggering general trigger (one for all)
		if($this->trigger_any != null) {
			$c = $this->trigger_any;
			echo "Triggering $c...\n";
			$tmpres = call_user_func_array($c, [$fullpar]);
			if($tmpres) array_push($res, $tmpres);
			if($this->singletrigger) return $res;
		}
		// checking command strings
		$textpar = explode(" ", $text);
		$cmd = array_shift($textpar);
		$par = new telegram_function_parameters($telegrambot, $this->chatid, $this->state, $msg, $text, $textpar);
		foreach($this->triggers_command as $t) {
			$ev = $t->events();
			$c = $t->callback();
			foreach($ev as $e) {
				$name = $e->name();
				$count = $e->count();
				if(((strtolower($text) == strtolower($name)) && (intval($count) == 0)) || ((strtolower($cmd) == strtolower($name)) && ((intval($count)<0) || (intval($count)==@count($textpar))))) {
					echo "Triggering $c...\n";
					$tmpres = call_user_func_array($c, [$par]);
					if($tmpres) array_push($res, $tmpres);
					if($this->singletrigger) return $res;
				}
			}
		}
		// checking strings in text
		foreach($this->triggers_intext as $t) {
			$ev = $t->events();
			$c = $t->callback();
			foreach($ev as $e) {
				$name = $e->name();
				if(strpos(strtolower($text), strtolower($name)) !== false) {
					echo "Triggering $c...\n";
					$tmpres = call_user_func_array($c, [$fullpar]);
					if($tmpres) array_push($res, $tmpres);
					if($this->singletrigger) return $res;
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
