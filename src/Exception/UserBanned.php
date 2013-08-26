<?php

class Exception_UserBanned extends Exception_Standard{
	
	public function __construct(){
		parent::__construct("User is banned");
	}
	
}