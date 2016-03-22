<?php
class telegram_function_parameters {
	private $bot, $chatid, $state, $msg, $text, $par;
	public static $type_text='text', $type_photo='photo', $type_video='video', $type_audio='audio', $type_voice='voice', $type_document='document', $type_sticker='sticker', $type_contact='contact', $type_location='location', $type_other='other';
	function __construct($b, $c, $s, $m, $t=null, $p=null) { $this->bot = $b; $this->chatid = $c; $this->state = $s; $this->msg = $m; $this->text = $t; $this->par = $p; }
	function set_text($t) { $this->text = $t; }
	function set_parameters($p) { $this->par = $p; }
	function bot() { return $this->bot; }
	function chatid() { return $this->chatid; }
	function state() { return $this->state; }
	function message() { return $this->msg; } // this is a Message object (see https://core.telegram.org/bots/api#message)
	function text() { return $this->text; }
	function parameters() { return $this->par; }
	function fileid() {
		try {
			$obj = $this->message();
			if($this->type() == telegram_function_parameters::$type_text) return null;
			if($this->type() == telegram_function_parameters::$type_other) return null;
			if($this->type() == telegram_function_parameters::$type_photo) $obj = $obj->photo[count($this->message()->photo)-1]; // for photos, an array of photos with different sizes is returned; we always consider the last element with higher resolution
			if($this->type() == telegram_function_parameters::$type_video) $obj = $obj->video;
			if($this->type() == telegram_function_parameters::$type_audio) $obj = $obj->audio;
			if($this->type() == telegram_function_parameters::$type_voice) $obj = $obj->voice;
			if($this->type() == telegram_function_parameters::$type_document) $obj = $obj->document;
			if($this->type() == telegram_function_parameters::$type_sticker) $obj = $obj->sticker;
			if($this->type() == telegram_function_parameters::$type_contact) $obj = $obj->contact;
			if($this->type() == telegram_function_parameters::$type_location) $obj = $obj->location;
			return $obj->file_id;
		} catch(Exception $e) { return null; }
	}
	function type() {
		if(@$this->msg->photo != '') return telegram_function_parameters::$type_photo;
		if(@$this->msg->video != '') return telegram_function_parameters::$type_video;
		if(@$this->msg->audio != '') return telegram_function_parameters::$type_audio;
		if(@$this->msg->voice != '') return telegram_function_parameters::$type_voice;
		if(@$this->msg->document != '') return telegram_function_parameters::$type_document;
		if(@$this->msg->sticker != '') return telegram_function_parameters::$type_sticker;
		if(@$this->msg->contact != '') return telegram_function_parameters::$type_contact;
		if(@$this->msg->location != '') return telegram_function_parameters::$type_location;
		if(@$this->msg->text != '') return telegram_function_parameters::$type_text;
		return telegram_function_parameters::$type_other;
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
	// non text message based triggers support
	private $triggers_photo = array();
	private $triggers_video = array();
	private $triggers_audio = array();
	private $triggers_voice = array();
	private $triggers_document = array();
	private $triggers_sticker = array();
	private $triggers_contact = array();
	private $triggers_location = array();
	// error trigger support
	private $trigger_error = null;
	function __construct($b, $c=null, $st=true) { $this->botname = $b; $this->chatid = $c; $this->singletrigger = $st; if($c!=null) $this->state = new telegram_state($b, $c); }
	public function register_trigger_any($callback, $state="*") {
		if(!$this->state->iscurrent($state)) return;
		$this->trigger_any = $callback;
	}
	public function register_trigger_text_command($callback, $names, $count=-1, $state="*") {
		if(!$this->state->iscurrent($state)) return;
		$type = telegram_function_parameters::$type_text;
		$evs = array();
		foreach($names as $name) array_push($evs, new telegram_event($name, $count));
		$t = new telegram_trigger($type, $callback, $evs);
		array_push($this->triggers_text_command, $t);
	}
	public function register_trigger_text_intext($callback, $texts, $state="*") {
		if(!$this->state->iscurrent($state)) return;
		$type = telegram_function_parameters::$type_text;
		$evs = array();
		foreach($texts as $text) array_push($evs, new telegram_event($text, -1));
		$t = new telegram_trigger($type, $callback, $evs);
		array_push($this->triggers_text_intext, $t);
	}
	public function register_trigger_photo($callback, $state="*") {
		if(!$this->state->iscurrent($state)) return;
		$type = telegram_function_parameters::$type_photo;
		$evs = array();
		$t = new telegram_trigger($type, $callback);
		array_push($this->triggers_photo, $t);
	}
	public function register_trigger_video($callback, $state="*") {
		if(!$this->state->iscurrent($state)) return;
		$type = telegram_function_parameters::$type_video;
		$evs = array();
		$t = new telegram_trigger($type, $callback);
		array_push($this->triggers_video, $t);
	}
	public function register_trigger_audio($callback, $state="*") {
		if(!$this->state->iscurrent($state)) return;
		$type = telegram_function_parameters::$type_audio;
		$evs = array();
		$t = new telegram_trigger($type, $callback);
		array_push($this->triggers_audio, $t);
	}
	public function register_trigger_voice($callback, $state="*") {
		if(!$this->state->iscurrent($state)) return;
		$type = telegram_function_parameters::$type_voice;
		$evs = array();
		$t = new telegram_trigger($type, $callback);
		array_push($this->triggers_voice, $t);
	}
	public function register_trigger_document($callback, $state="*") {
		if(!$this->state->iscurrent($state)) return;
		$type = telegram_function_parameters::$type_document;
		$evs = array();
		$t = new telegram_trigger($type, $callback);
		array_push($this->triggers_document, $t);
	}
	public function register_trigger_sticker($callback, $state="*") {
		if(!$this->state->iscurrent($state)) return;
		$type = telegram_function_parameters::$type_sticker;
		$evs = array();
		$t = new telegram_trigger($type, $callback);
		array_push($this->triggers_sticker, $t);
	}
	public function register_trigger_contact($callback, $state="*") {
		if(!$this->state->iscurrent($state)) return;
		$type = telegram_function_parameters::$type_contact;
		$evs = array();
		$t = new telegram_trigger($type, $callback);
		array_push($this->triggers_contact, $t);
	}
	public function register_trigger_location($callback, $state="*") {
		if(!$this->state->iscurrent($state)) return;
		$type = telegram_function_parameters::$type_location;
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
		$fullpar = new telegram_function_parameters($telegrambot, $this->chatid, $this->state, $msg);
		$type = $fullpar->type();
		$text = null; if($type == telegram_function_parameters::$type_text) $text = @trim(str_ireplace("@".$this->botname, "", $msg->text));
		$fullpar->set_text($text); $fullpar->set_parameters([$text]);
		$res = array();
		// triggering general trigger (one for all)
		if($this->trigger_any != null) {
			$c = $this->trigger_any;
			echo "Triggering $c...\n";
			$tmpres = call_user_func_array($c, [$fullpar]);
			if($tmpres) array_push($res, $tmpres);
			if($this->singletrigger) return $res;
		}
		if($type == telegram_function_parameters::$type_text) { // text based triggers
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
		// support to non text triggers
		$nontext_triggers = null;
		if($type == telegram_function_parameters::$type_photo) $nontext_triggers = $this->triggers_photo;
		if($type == telegram_function_parameters::$type_video) $nontext_triggers = $this->triggers_video;
		if($type == telegram_function_parameters::$type_audio) $nontext_triggers = $this->triggers_audio;
		if($type == telegram_function_parameters::$type_voice) $nontext_triggers = $this->triggers_voice;
		if($type == telegram_function_parameters::$type_document) $nontext_triggers = $this->triggers_document;
		if($type == telegram_function_parameters::$type_sticker) $nontext_triggers = $this->triggers_sticker;
		if($type == telegram_function_parameters::$type_contact) $nontext_triggers = $this->triggers_contact;
		if($type == telegram_function_parameters::$type_location) $nontext_triggers = $this->triggers_location;
		if($nontext_triggers != null) {
			$par = new telegram_function_parameters($telegrambot, $this->chatid, $this->state, $msg);
			foreach($nontext_triggers as $t) {
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
