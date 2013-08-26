<?php

class Exception_UserNotFound extends Exception_Standard{
	
	public function __construct(){
		parent::__construct("User not found");
	}
	
}
