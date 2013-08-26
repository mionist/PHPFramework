<?php
class DataModel_MagicHashtable extends DataModel_Hashtable {
	
	// TODO не работает сеттер :(
//	public function __set ($index, $value) {
//		throw new Exception('Thrown on '.$index);
//		return parent::_set($index, $value);
//	}
	
	public function __get($index) {
		return parent::_get($index);
	}
	
	public function __isset($index) {
		return parent::_exists($index);
	}
}