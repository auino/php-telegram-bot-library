<?php
class telegram_state {
	const ANY_STATE = "*";
	private $botname = null;
	private $chat = null;
	function __construct($b, $c) { $this->botname = $b; $this->chat = $c; }
	public function iscurrent($state) {
		global $STATES_ENABLED;
		$s = $this->getstate();
		if($STATES_ENABLED && ($state != $s && $state != self::ANY_STATE)) return false;
		return true;
	}
	public function movetostate($state) {
		global $STATES_ENABLED;
		if(!$STATES_ENABLED) return;
		if($this->chat == null) return;
		$q = "UPDATE States set state='$state' WHERE bot='".$this->botname."' AND chat='".$this->chat."';";
		if($this->getstate() == null) $q = "INSERT INTO States(bot, chat, state) VALUES('".$this->botname."', '".$this->chat."', '$state');";
		db_nonquery($q);
	}
	private function getstate() {
		global $STATES_ENABLED;
		if(!$STATES_ENABLED) return null;
		if($this->chat == null) return; // exception here
		$q = "SELECT state FROM States WHERE bot='".$this->botname."' AND chat='".$this->chat."';";
		$s = db_query($q);
		if(count($s)<=0) return null;
		return $s[0]['state'];
	}
}
?>
