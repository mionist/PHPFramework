<?php

class SQL_Helper_OR extends SQL_Helper_Abstract{
	
	private $operands;
	
	public function __construct( $operands ){
		$this->operands = $operands;
	}
	
	public function getSQL(){
		$SQL = array();
		foreach ( $this->operands as $row ){
			if ( $row instanceof SQL_Helper_Abstract ) $SQL[] = $row->getSQL();
			elseif ( is_array( $row ) ){
				// Массив пара: ключ-значение
				$SQL[] = " {$row[0]}='{$row[1]}' ";
			}
		}
		
		return '('.implode(' OR ', $SQL).')';
	}

	public function getMongoFilter() {
		$value = array();
		$answer = array('$or'=>&$value);
		foreach ( $this->operands as $row ){
		    if ( $row instanceof SQL_Helper_Abstract ) $value[] = $row->getMongoFilter ();
		    elseif (is_array($row) ){
			$value[] = array( $row[0]=>$row[1] );
		    }
		}
		return $answer;
	}
	
}