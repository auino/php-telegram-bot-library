<?php
// This file implements an extension of:
// https://github.com/gorebrau/PHP-telegram-bot-API
// - self-signed certificate are now supported
// - automatic actions are now sent before sending data (the result is, e.g., the "is sending a picture..." message on top of the Telegram client)

class ReplyKeyboardMarkup{
	public $keyboard;
	public $resize_keyboard;
	public $one_time_keyboard;
	public $selective;

	function __construct($resize_keyboard=FALSE, $one_time_keyboard = FALSE, $selective=FALSE){
		$this->keyboard=array();
		$this->keyboard[0]=array();
		$this->resize_keyboard=$resize_keyboard;
		$this->one_time_keyboard=$one_time_keyboard;
		$this->selective=$selective;
	}

	public function add_option($option){
		$this->keyboard = $option;
	}
}

class ReplyKeyboardHide{
	public $hide_keyboard;
	public $selective;

	function __construct($hide_keyboard=TRUE, $selective = FALSE){
		$this->hide_keyboard=$hide_keyboard;
		$this->selective=$selective;
	}
}

class ForceReply{
	public $force_reply;
	public $selective;

	function __construct($force_reply=TRUE, $selective = FALSE){
		$this->force_reply=$force_reply;
		$this->selective=$selective;
	}
}

class telegram_bot {
	private $token;

	private function open_url($url, $method="GET", $data=null){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		if($method==="POST"){
			if(isset($data)){
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			}
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		return curl_exec($ch);
	}

	private function control_api($action, $data=NULL){
		$token = $this->token;
		$response = json_decode($this->open_url("https://api.telegram.org/bot$token$action", "POST", $data));
		return $response;
	}

	function __construct($token){
		$this->token=$token;
	}

	public function status(){
		$response = $this->control_api("/getme");
		return($response);
	}

	public function get_updates(){
		$response = $this->control_api("/getUpdates");
		return($response);
	}

	public function send_action($to, $action){
		$data = array();
		$data["chat_id"]=$to;
		$data["action"]=$action;
		$response = $this->control_api("/sendChatAction", $data);
		return $response;
	}

	public function send_message($to, $msg, $id_msg=null, $reply=null){
		$data = array();
		$data["chat_id"]=$to;
		$data["text"]=$msg;
		$data["disable_web_page_preview"]="true";
		if(isset($id_msg))
			$data["reply_to_message_id"]=$id_msg;
		if(isset($reply))
			$data["reply_markup"]=$reply;
		$response = $this->control_api("/sendMessage", $data);
		return $response;
	}

	public function send_location($to, $lat, $lon, $id_msg=null, $reply=null){
		$data = array();
		$data["chat_id"]=$to;
		$data["latitude"]=$lat;
		$data["longitude"]=$lon;
		if(isset($id_msg))
			$data["reply_to_message_id"]=$id_msg;
		if(isset($reply))
			$data["reply_markup"]=$reply;
		$response = $this->control_api("/sendLocation", $data);
		return $response;
	}

	public function send_sticker($to, $sticker, $id_msg=null, $reply=null){
		$data = array();
		$data["chat_id"]=$to;
		if(file_exists($sticker))$sticker="@".$sticker;
		$data["sticker"]=$sticker;
		if(isset($id_msg))
			$data["reply_to_message_id"]=$id_msg;
		if(isset($reply))
			$data["reply_markup"]=$reply;
		$response = $this->control_api("/sendSticker", $data);
		return $response;
	}

	public function send_video($to, $video, $id_msg=null, $reply=null){
		$this->send_action($to, "upload_video");
		$data = array();
		$data["chat_id"]=$to;
		if(file_exists($video))$video="@".$video;
		$data["video"]=$video;
		if(isset($id_msg))
			$data["reply_to_message_id"]=$id_msg;
		if(isset($reply))
			$data["reply_markup"]=$reply;
		$response = $this->control_api("/sendVideo", $data);
		return $response;
	}

	public function send_photo($to, $photo, $caption=null, $id_msg=null, $reply=null){
		$this->send_action($to, "upload_photo");
		$data = array();
		$data["chat_id"]=$to;
		if(file_exists($photo))$photo="@".$photo;
		$data["photo"]=$photo;
		if(isset($caption)){
			$data["caption"]=$caption;
		}
		if(isset($id_msg)){
			$data["reply_to_message_id"]=$id_msg;
		}
		if(isset($reply))
			$data["reply_markup"]=$reply;
		$response = $this->control_api("/sendPhoto", $data);
		return $response;
	}

	public function send_audio($to, $audio, $id_msg=null, $reply=null){
		$this->send_action($to, "upload_audio");
		$data = array();
		$data["chat_id"]=$to;
		if(file_exists($audio))$audio="@".$audio;
		$data["audio"]=$audio;
		if(isset($id_msg)){
			$data["reply_to_message_id"]=$id_msg;
		}
		if(isset($reply))
			$data["reply_markup"]=$reply;
		$response = $this->control_api("/sendAudio", $data);
		return $response;
	}

	public function send_document($to, $document, $id_msg=null, $reply=null){
		$this->send_action($to, "upload_document");
		$data = array();
		$data["chat_id"]=$to;
		if(file_exists($audio))$document="@".$audio;
		$data["document"]=$document;
		if(isset($id_msg)){
			$data["reply_to_message_id"]=$id_msg;
		}
		if(isset($reply))
			$data["reply_markup"]=$reply;
		$response = $this->control_api("/sendDocument", $data);
		return $response;
	}

	public function forward_message($to, $from, $msg_id){
		$data = array();
		$data["chat_id"]=$to;
		$data["from_chat_id"]=$from;
		$data["message_id"]=$msg_id;
		$response = $this->control_api("/forwardMessage", $data);
		return $response;
	}

	public function set_webhook($url=null, $certificatefile=null){
		$data = array();
		$data["url"]=$url;
		if($certificatefile!=null) {
			//$f = fopen($certificatefile, "r");
			//$certificate = fread($f,filesize($certificatefile));
			//fclose($f);
			//$data["certificate"] = $certificate;
			$data["certificate"] = $certificatefile;
		}
		$response = $this->control_api("/setWebhook", $data);
		return $response;
	}

	public function get_user_profile_photos($id_user, $offset=null, $limit=null){
		$data = array();
		$data["user_id"]=$id_user;
		if(isset($offset)){
			$data["offset"]=$offset;
		}
		if(isset($limit)){
			$data["limit"]=$limit;
		}
		$response = $this->control_api("/getUserProfilePhotos", $data);
		return $response;
	}

	public function read_post_message(){
		return json_decode(file_get_contents('php://input'));
	}

}
?>
