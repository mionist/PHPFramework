<?php

class Manage_ORM_NumericField extends Manage_ORM_Field{
	
	public $pattern = '[0-9\.\,\-]';
	
	public function encodeValue($value){
		// на вход - только число
		$value = ( float ) str_replace(array(',',' '), array('.',''), $value);
		return $value;
	}
	
	public function isEmpty($value){ return is_numeric($value) && $value == 0; }
	public function isLinkInList(){ return $this->name == 'id'; }
	public function isNumeric(){ return TRUE; }
	
}