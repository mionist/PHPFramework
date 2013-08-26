<?php
class DataModel_Array extends DataModel_Abstract {
	
	public function get($index){
		return $this->_get($index);
	}
	
	public function getArray(){
		$this->initialize();
		return $this->data;
	}
	
	public function map( $function ){
		$this->initialize();
		$this->data = array_map( $function , $this->data );
		return $this;
	}
	
	public function implode( $joinOperator = ',' ){
		$this->initialize();
		return implode( $joinOperator , $this->data );
	}
	
	public function prepend( $value ){
		if ( !is_array($value) ) $value = array($value);
		$this->initialize();
		$this->data = array_merge( $value, $this->data );
	}
	
	public function put($value){
		$this->initialize();
		$this->data[] = $value;
	}
	
	public function unique(){
		$this->initialize();
		$this->data = array_unique( $this->data );
		return $this;
	}
	
	public function reverse(){
		$this->initialize();
		$this->data = array_reverse( $this->data );
		return $this;
	}
	
	public function push($value){ $this->put($value); }
	
	/* Переопределяем _set */
	protected function _set($index, $value){
		if ( !isset($this->data[$index]) ) throw new IndexOutOfBoundsStandardException();
		parent::_set($index, $value);
	}
	
	protected function lazyInitialization(){ $this->data = array(); }
}
