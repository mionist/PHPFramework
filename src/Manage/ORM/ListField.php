<?php

class Manage_ORM_ListField extends Manage_ORM_Field{
	
	protected $listValues;
	
	protected function lazyReadValues(){
		if ( isset($this->listValues) ) return;
		$enum = str_replace("'", '', $this->rawData['Type']);
		$sp = strpos($enum, '(');
		$enum = substr( $enum, $sp+1, strlen($enum)-$sp-2);
		$combine = explode(',', $enum);
		$this->listValues = array_combine( $combine, $combine);
	}
	
	public function resolveValue( $value ){
		$this->lazyReadValues();
		if ( !in_array($value, $this->listValues) ) return 'unknown - '.$value;
		return $value;
	}
	
	public final function getValues(){
		$this->lazyReadValues();
		return $this->listValues;
	}
	
	public function isSearchAble(){ return FALSE; }
	
}