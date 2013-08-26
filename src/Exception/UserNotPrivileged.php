<?php
class Exception_UserNotPrivileged extends Exception_Standard{
	
	public function __construct(){
		parent::__construct("User not privileged");
	}
	
}