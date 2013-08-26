<?php

class DataModel_Hashtable extends DataModel_Abstract {
	protected $keys_in_lowercase;
	
	public function __construct( $data = NULL, $lowercase_keys = FALSE ){
		$this->keys_in_lowercase = $lowercase_keys;
		parent::__construct( $data );
	}

	public function replace( $index, $value ){
		$this->_set($index, $value);
	}
        
        public function getArray(){
		$this->initialize();
		return $this->data;
	}        
	
	/* Переопределяем _get, _set, _exists */
	protected function _get( $index ){
		if ( $this->keys_in_lowercase ) $index = strtolower( $index );
		return parent::_get($index);
	}
	
	protected function _set($index, $value){
		if ( $this->keys_in_lowercase ) $index = strtolower( $index );
		return parent::_set($index, $value);
	}
	
	protected function _exists($index){
		if ( $this->keys_in_lowercase ) $index = strtolower( $index );
		return parent::_exists( $index );
	}
	
	protected function lazyInitialization(){ $this->data = array(); }
	
	/* Проверочные ништяки */
	public function isValueArray( $index ){
		$value = $this->_get($index);
		if ( !isset($value) ) return FALSE;
		if ( !(is_array($value) || is_object($value)) ) return FALSE;
		if ( count($value) == 0 ) return FALSE;
		return TRUE;
	}
	
	/* Ништяки */
	public function getString( $index ){
		$value = $this->_get($index);
		if ( !isset( $value ) ) return '';
		return trim( $value );
	}
	public function getInt( $index ){
		$value = $this->_get($index);
		if ( !isset( $value ) ) return 0;
		return intval($value);
	}
	public function getFloat( $index ){
		$value = $this->_get($index);
		if ( !isset( $value ) ) return 0;
		return floatval($value);
	}
	public function getBool( $index ){
		$value = $this->_get($index);
		if ( !isset( $value ) ) return FALSE;
		if ( $value === TRUE ) return TRUE;
		if ( $value === 1 || $value === '1' ) return TRUE;
		return FALSE; 
	}
	public function hasArray( $index ){
		if ( !$this->_exists($index) ) return FALSE;
		if ( !is_array( $this->_get($index) ) && !is_object( $this->_get($index) ) ) return FALSE;
		if ( count( $this->_get($index) ) === 0 ) return FALSE;
		return TRUE;
	}
		
}