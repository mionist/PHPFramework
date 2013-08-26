<?php

class Renderable_ContentEditable extends Renderable_Item{
	
	public function __construct( $table, $field, $id ){
		parent::__construct( array(
			'table'=>$table,
			'field'=>$field,
			'id'=>$id
		) );
	}
	
	protected function produceContext($context){
		if ( !self::isAdmin() ) return '';
		return ' mcee="'.$this->data['table'].'" mcef="'.$this->data['field'].'" mceid="'.$this->data['id'].'" ';		
	}
	
	private static $amIAdmin;
	private static function isAdmin(){
		if ( isset(self::$amIAdmin) ) return self::$amIAdmin;
		if ( !isset( Core::$auth ) ) return self::$amIAdmin = FALSE;
		if ( !Core::$auth->isHere() ) return self::$amIAdmin = FALSE;
		if ( !Core::$auth->isPrivileged() ) return self::$amIAdmin = FALSE;
		return self::$amIAdmin = TRUE;
	}
	
}