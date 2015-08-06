<?php

class Conversation extends Doc {

	public static $messagesPerPage = 10;

	public $template = Array(
		"users"			=> Array(),
		"messages"		=> Array(),
		"created"		=> ""
	);
	
	public function __construct($id = NULL) {
		parent::__construct($id);
		$this->_p["type"] = "conversation";
		if($id !== NULL) {		
			$this->_p["id"] = $id;
			$result = $this->retrieve();

			if(isset($result['_source'])) {
				$this->_source = $result["_source"];
				$this->_found = true;
			} else {
				$this->_found = false;
			}
		} 
		else {
			$this->_source = $this->template;
			$this->created = time();
		}

	}

	public function save() {
		if(sizeof($this->users) == 0) {
			meh("Attempted to save conversation with no users.");
		}

		if($this->_mode = MODE_EXISTING) {
			$res = $this->index();
		} else {
			$res = $this->create();
			echo "existing: " . $this->id;
		}

		$res = $this->index();
		
		return $res;
	}
	
	public function &__get($key) {
		switch($key) {
			case 'id':
				return $this->_p['id'];

			default;
				return parent::__get($key);
		}
	}

	public function __set($key, $val) {
		parent::__set($key, $val);
	}

	private function _addUser($user) {
		if(!in_array($user->username, $this->users)) {
			$this->users []= $user->username;
		}
	}

	public function messages($page = 0) {
		return array_slice($this->messages, $page * Conversation::$messagesPerPage, Conversation::$messagesPerPage);
	}

	public function removeUser($user) {
		$this->_clearFromUser($user);
		$user->save();

		for($i = 0; $i < sizeof($this->users); $i++) {
			if($this->users[$i] == $user->username) {
				unset($this->users[$i]);
			}
		}

		$this->users = array_values($this->users);
	}

	public function _clearFromUser($user) {
		foreach(array_keys($user->mailboxes) as $type) {
			for($i = sizeof($user->mailboxes[$type])-1; $i >= 0 ; $i--) {
				if($user->mailboxes[$type][$i]['id'] == $this->id) {
					unset($user->mailboxes[$type][$i]);
				}
			}

			$user->mailboxes[$type] = array_values($user->mailboxes[$type]);
		}
	}

	public function toUserBox($user, $mailbox) {
		if(!in_array($user->username, $this->users)) {
			meh("Attempted push to users mailbox when user is not part of conversation.");
		}
		
		$this->_clearFromUser($user);

		$user->mailboxes[$mailbox] []= array(
			"id"			=> $this->id,
			"last_updated"		=> time(),
			"unread"		=> $mailbox == 'inbox' ? 1 : 0
		);

		$user->save();
	}

	public function newMessage($sender, $content, $recipients = []) {
		if(!is_array($recipients)) {
			$recipients = [$recipients];
		}
	
		if(!isset($this->_p['id'])) {
			$this->_p['id'] = hash('md5', $sender->id . time());
		}

		if(is_object($sender)) {
			$this->_addUser($sender);
			$this->toUserBox($sender, 'sent');
		}
		
		foreach($this->users as $user) {
			if($user != $sender->username) {
				$recipients []= new \Profile(strtolower($user));
			}
		}
		foreach($recipients as $recipient) {
			$this->_addUser($recipient);
			$this->toUserBox($recipient, 'inbox');
		}

		$message = [
			"time"		=> time(),
			"sender" 	=> (is_object($sender) ? $sender->username : $sender),
			"avatar"	=> (is_object($sender) ? $sender->photos['avatar'] : DEFAULT_AVATAR),
			"content"	=> htmlspecialchars($content),
			"i"		=> sizeof($this->messages)
		];
		$this->messages []= $message;

		return $message;
	}
}

?>
