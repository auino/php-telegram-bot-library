<?php
class telegram_function_parameters {
	private $bot, $chatid, $state, $msg, $text, $par;
	function __construct($b, $c, $s, $m, $t=null, $p=null) { $this->bot = $b; $this->chatid = $c; $this->state = $s; $this->msg = $m; $this->text = $t; $this->par = $p; }
	function bot() { return $this->bot; }
	function chatid() { return $this->chatid; }
	function state() { return $this->state; }
	function message() { return $this->msg; } // this is a Message object (see https://core.telegram.org/bots/api#message)
	function text() { return $this->text; }
	function parameters() { return $this->par; }
	function fileid() { try { return $this->message()[0]->file_id; } catch(Exception $e) { return null; } } // always the first one is returned
	function type() {
		if($this->msg->photo != '') return 'photo';
		if($this->msg->video != '') return 'video';
		if($this->msg->audio != '') return 'audio';
		if($this->msg->voice != '') return 'voice';
		if($this->msg->document != '') return 'document';
		if($this->msg->sticker != '') return 'sticker';
		if($this->msg->contact != '') return 'contact';
		if($this->msg->location != '') return 'location';
		if($this->text != '') return 'text';
		return 'other';
	}
}

class telegram_event {
	private $name, $count;
	function __construct($n, $c=-1) { $this->name = $n; $this->count = $c; }
	function name() { return $this->name; }
	function count() { return $this->count; }
}

class telegram_trigger {
	private $type, $callback, $events;
	function __construct($t, $c, $e=null) { $this->type = $t; $this->callback = $c; $this->events = $e; }
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
	private $triggers_location = array();
	private $trigger_error = null;
	function __construct($b, $c=null, $st=true) { $this->botname = $b; $this->chatid = $c; $this->singletrigger = $st; if($c!=null) $this->state = new telegram_state($b, $c); }
	public function register_trigger_any($callback, $state="*") {
		if(!$this->state->iscurrent($state)) return;
		$this->trigger_any = $callback;
	}
	private function getmessagetype($m) {
		$t = "unknown";
		$v = @$m->text; if(isset($v) && ($v != null)) $t = "text";
		$v = @$m->photo; if(isset($v) && ($v != null)) $t = "photo";
		$v = @$m->location; if(isset($v) && ($v != null)) $t = "location";
		return $t;
	}
	public function register_trigger_text_command($callback, $names, $count=-1, $state="*") {
		if(!$this->state->iscurrent($state)) return;
		$type = "text";
		$evs = array();
		foreach($names as $name) array_push($evs, new telegram_event($name, $count));
		$t = new telegram_trigger($type, $callback, $evs);
		array_push($this->triggers_text_command, $t);
	}
	public function register_trigger_text_intext($callback, $texts, $state="*") {
		if(!$this->state->iscurrent($state)) return;
		$type = "text";
		$evs = array();
		foreach($texts as $text) array_push($evs, new telegram_event($text, -1));
		$t = new telegram_trigger($type, $callback, $evs);
		array_push($this->triggers_text_intext, $t);
	}
	public function register_trigger_photo($callback, $state="*") {
		if(!$this->state->iscurrent($state)) return;
		$type = "photo";
		$evs = array();
		$t = new telegram_trigger($type, $callback);
		array_push($this->triggers_photo, $t);
	}
	public function register_trigger_location($callback, $state="*") {
		if(!$this->state->iscurrent($state)) return;
		$type = "location";
		$evs = array();
		$t = new telegram_trigger($type, $callback);
		array_push($this->triggers_location, $t);
	}
	public function register_trigger_error($callback, $state="*") {
		if(!$this->state->iscurrent($state)) return;
		$this->trigger_error = $callback;
	}
	public function run($telegrambot, $msg) {
		global $STATES_ENABLED;
		$type = $this->getmessagetype($msg);
		$text = null;
		if($type == "text") $text = @trim(str_ireplace("@".$this->botname, "", $msg->text));
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
		if($type == "text") { // text based triggers
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
		if($type == "photo") { // photo based triggers
			$par = new telegram_function_parameters($telegrambot, $this->chatid, $this->state, $msg);
			foreach($this->triggers_photo as $t) {
				$c = $t->callback();
				echo "Triggering $c...\n";
				$tmpres = call_user_func_array($c, [$par]);
				if($tmpres) array_push($res, $tmpres);
				if($this->singletrigger) return $res;
			}
		}
		if($type == "location") { // location based triggers
			$par = new telegram_function_parameters($telegrambot, $this->chatid, $this->state, $msg);
			foreach($this->triggers_location as $t) {
				$c = $t->callback();
				echo "Triggering $c...\n";
				$tmpres = call_user_func_array($c, [$par]);
				if($tmpres) array_push($res, $tmpres);
				if($this->singletrigger) return $res;
			}
		}
	}
}
?>
