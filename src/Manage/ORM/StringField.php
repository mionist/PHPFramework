<?php

class Manage_ORM_StringField extends Manage_ORM_Field {
	
	protected $isBig = FALSE;
	protected $isHTML = FALSE;
	private static $TEXT_TYPES = array('tinytext','text','mediumtext','longtext');
	
	protected function decodeRawData(){
		parent::decodeRawData();
		if ( in_array( $this->type, self::$TEXT_TYPES ) ) $this->isBig = TRUE;
	}
	
	public function encodeValue($value){ return trim($value); }
	
	public function setHTML(){ $this->isHTML = TRUE; }
	
	public function isOrderAble(){ return !$this->isBig; }
	public function isHiddenInList(){ return $this->isBig; }
	public function isText(){ return TRUE; }
	public function isBigText(){ return $this->isBig || $this->isHTML; }
	public function isHTML(){ return $this->isHTML; }
}