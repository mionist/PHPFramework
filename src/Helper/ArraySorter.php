<?php

class Helper_ArraySorter {
	
	const NESTED_VALUES = 5;
	const NESTED_VALUES_CYRRILLIC = 10;
	
	public static function Sort( $array, $type, $parameter = NULL ){
		$obj = new self($array, $type, $parameter);
		return $obj->result;
	}
	
	private $result;
	private $array;
	private $type;
	private $parameter;
	private function __construct( $array, $type, $parameter = NULL ){
		$this->array = $array;
		$this->type = $type;
		$this->parameter = $parameter;
		$this->result = $array;
		switch ( $type ){
			case self::NESTED_VALUES:
				uasort( $this->result , array($this, 'sortFunctionNested'));
				break;
			case self::NESTED_VALUES_CYRRILLIC:
				uasort( $this->result , array($this, 'sortFunctionNestedWithCyrillic'));
				break;
		}
	}
	
	
	private function sortFunctionNestedWithCyrillic( $a, $b ){
		$a = ( isset($a[$this->parameter]) ? ( mb_convert_encoding($a[$this->parameter], 'cp1251') ) : '' );
		$b = ( isset($b[$this->parameter]) ? ( mb_convert_encoding($b[$this->parameter], 'cp1251') ) : '' );
		return strcmp($a, $b);
	}
	private function sortFunctionNested( $a, $b ){
		$a = ( isset($a[$this->parameter]) ? $a[$this->parameter] : 0 );
		$b = ( isset($b[$this->parameter]) ? $b[$this->parameter] : 0 );
		
		if ( $a > $b ) return -1;
		if ( $a < $b ) return 1;
		return 0;
	}
	
}