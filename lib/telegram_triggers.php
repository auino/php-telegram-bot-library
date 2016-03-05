<?php
abstract class telegram_message_type {
	const unknown = 0;
	const text = 1;
	const photo = 2;
	const video = 3;
	const location = 4;
	const file = 5;
}

class telegram_function_parameters {
	private $bot, $chatid, $state, $msg, $text, $par;
	function __construct($b, $c, $s, $m, $t=null, $p=null) { $this->bot = $b; $this->chatid = $c; $this->state = $s; $this->msg = $m; $this->text = $t; $this->par = $p; }
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
	private $type, $callback, $events;
	function __construct($t, c, $e=null) { $this->type = $t; $this->callback = $c; $this->events = $e; }
	function type() { return $this->type; }
	function callback() { return $this->callback; }
	function events() { return $this->events; }
}

class telegram_trigger_set {
	private $singletrigger = true;
	private $botname = null;
	private $chatid = null;
	private $state = null;
	private $trigger_any = null;
	private $triggers_text_command = array();
	private $triggers_text_intext = array();
	private $triggers_photo = array();
	private $trigger_error = null;
	function __construct($b, $c=null, $st=true) { $this->botname = $b; $this->chatid = $c; $this->singletrigger = $st; if($c!=null) $this->state = new telegram_state($b, $c); }
	public function register_trigger_any($callback, $state="*") {
		if(!$this->state->iscurrent($state)) return;
		$this->trigger_any = $callback;
	}
	private function getmessagetype($m) {
		$t = telegram_message_type::unknown;
		if($m->photo != null) return telegram_message_type::photo;
		if($m->text != null) return telegram_message_type::text;
	}
	public function register_trigger_text_command($callback, $names, $count=-1, $state="*") {
		if(!$this->state->iscurrent($state)) return;
		$type = telegram_message_type::text;
		$evs = array();
		foreach($names as $name) array_push($evs, new telegram_event($type, $name, $count));
		$t = new telegram_trigger($callback, $evs);
		array_push($this->triggers_text_command, $t);
	}
	public function register_trigger_text_intext($callback, $texts, $state="*") {
		if(!$this->state->iscurrent($state)) return;
		$type = telegram_message_type::text;
		$evs = array();
		foreach($texts as $text) array_push($evs, new telegram_event($type, $text, -1));
		$t = new telegram_trigger($callback, $evs);
		array_push($this->triggers_text_intext, $t);
	}
	public function register_trigger_photo($callback, $state="*") {
		if(!$this->state->iscurrent($state)) return;
		$type = telegram_message_type::photo;
		$evs = array();
		array_push($evs, new telegram_event($type));
		$t = new telegram_trigger($callback, $evs);
		array_push($this->triggers_photo, $t);
	}
	public function register_trigger_error($callback, $state="*") {
		if(!$this->state->iscurrent($state)) return;
		$this->trigger_error = $callback;
	}
	public function run($telegrambot, $msg) {
		global $STATES_ENABLED;
		$type = getmessagetype($msg);
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
		if($type == telegram_message_type::text) { // text based triggers
			// checking command strings
			$textpar = explode(" ", $text);
			$cmd = array_shift($textpar);
			$par = new telegram_function_parameters($telegrambot, $this->chatid, $this->state, $msg, $text, $textpar);
			foreach($this->triggers_text_command as $t) {
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
			foreach($this->triggers_text_intext as $t) {
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
		if($type == telegram_message_type::photo) { // photo based triggers
			$par = new telegram_function_parameters($telegrambot, $this->chatid, $this->state, $msg);
			foreach($this->triggers_photo as $t) {
				$c = $t->callback();
				echo "Triggering $c...\n";
				$tmpres = call_user_func_array($c);
				if($tmpres) array_push($res, $tmpres);
				if($this->singletrigger) return $res;
			}
		}
	}
}
?>
