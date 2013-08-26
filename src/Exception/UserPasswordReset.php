<?php
class Exception_UserPasswordReset extends Exception_Standard{
	
	private $id;
	
	public function __construct( $userid ){
		parent::__construct("Need reset");
		$this->id = $userid;
	}
	
	public function getID(){ return $this->id; }
	
}