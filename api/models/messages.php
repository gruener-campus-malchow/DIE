<?php
require_once('model.php');
class messages extends model{
	public function readAll(){
		return array('You are not allowed to read all messages, dope!');
	}
	
	
}
?>
