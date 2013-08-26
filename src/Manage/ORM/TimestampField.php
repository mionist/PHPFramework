<?php

class Manage_ORM_TimestampField extends Manage_ORM_Field{

	public function isEmpty( $value ){ return $value == '0000-00-00 00:00:00' || $value == '0000-00-00' || $value == '0'; }
	
        public function isSearchAble() {
            return FALSE;
        }
        
	public function decodeInRowValueForList($row){
		$value = $row[$this->name];
		if ( $this->isEmpty($value) ) return $value;
		if ( !is_numeric($value) ){
			$value = strtotime($value);
		}
		if ( date( 'H:i:s', $value ) === '00:00:00' ) return date('d.m.y', $value);
		return date('d.m.y H:i:s', $value);
	}
	
}