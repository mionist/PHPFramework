<?php

class SQL_Helper_Common extends SQL_Helper_Abstract{
	const O_NE				= 1;
	const O_NOT_EQUAL		= 1;
	const O_BIG				= 2;
	const O_BIGGER_THAN		= 2;
	const O_BE				= 21;
	const O_BIGGER_EQUAL	= 21;
	const O_SMALL			= 3;
	const O_SMALLER_THAN	= 3;
	const O_SE				= 31;
	const O_SMALLER_EQUAL	= 31;
	const O_IN				= 4;
	const O_NOT_IN			= 5;
	const O_LIKE			= 6;
	const O_LIKE_WO			= 7;
	
	private $field;
	private $operand;
	private $value;
	
	private $escape_value;
	
	public function __construct( $field, $operand, $value, $escape_value = true ){
		$this->field = $field;
		$this->operand = $operand;
		$this->value = $value;
		$this->escape_value = $escape_value;
	}
	
	public function wrapQuotes( $string ){ return '"'.$string.'"'; }
	
	public function getFieldName(){ return $this->field; }
	
	public function getSQL( $db = NULL ){
		if ( !isset($db) ) $db = Core::getDatabase();
		$SQL = ' `'.$this->field.'` ';
		switch ( $this->operand ){
			case self::O_NOT_EQUAL:
				$SQL .= ' != ';
				$SQL .= ' "'.( $this->escape_value ? $db->escape($this->value) : $this->value ).'" ';
				break;
			case self::O_BIGGER_THAN:
				$SQL .= ' > ';
				$SQL .= ' "'.( $this->escape_value ? $db->escape($this->value) : $this->value ).'" ';
				break;
			case self::O_BIGGER_EQUAL:
				$SQL .= ' >= ';
				$SQL .= ' "'.( $this->escape_value ? $db->escape($this->value) : $this->value ).'" ';
				break;
			case self::O_SMALLER_THAN:
				$SQL .= ' < ';
				$SQL .= ' "'.( $this->escape_value ? $db->escape($this->value) : $this->value ).'" ';
				break;
			case self::O_SMALLER_EQUAL:
				$SQL .= ' <= ';
				$SQL .= ' "'.( $this->escape_value ? $db->escape($this->value) : $this->value ).'" ';
				break;
			
			case self::O_NOT_IN:
			case self::O_IN:
				if ( !is_array( $this->value ) ) throw new StandardException( 'Array parameter expected for IN operation' );
				if ( count( $this->value ) == 0 ) throw new StandardException( 'Array parameter is empty' );
				
				$value = $this->value;
				if ( $this->escape_value ) $value = array_map( array( $db, 'escape' ), $value );
				$value = array_map( array($this,'wrapQuotes'), $value ); 
				
				$SQL .= ($this->operand == self::O_NOT_IN?' NOT':'').' IN ('.implode(', ',$value).')';
				break;
				
			case self::O_LIKE:
				$SQL .= ' LIKE "%'.( $this->escape_value ? $db->escape($this->value) : $this->value ).'%" ';
				break;
				
			case self::O_LIKE_WO:
				$SQL .= ' LIKE "'.( $this->escape_value ? $db->escape($this->value) : $this->value ).'" ';
				break;
				
			default:
				throw new FetcherSQLRuleException('Unknown operand with code '.$this->operand);
		}
		
		return $SQL;
	}
	
	public function getMongoFilter() {
		$value = array();
		$answer = array( $this->field => &$value );
		switch ( $this->operand ){
			case self::O_NOT_EQUAL:
				$value = array('$ne'=>$this->value); 
				break;
			case self::O_BIGGER_THAN:
				$value = array('$gt'=>$this->value); 
				break;
			case self::O_BIGGER_EQUAL:
				$value = array('$gte'=>$this->value); 
				break;
			case self::O_SMALLER_THAN:
				$value = array('$kt'=>$this->value); 
				break;
			case self::O_SMALLER_EQUAL:
				$value = array('$lte'=>$this->value); 
				break;
			
			case self::O_NOT_IN:
			case self::O_IN:
				if ( !is_array( $this->value ) ) throw new StandardException( 'Array parameter expected for IN operation' );
				if ( count( $this->value ) == 0 ) throw new StandardException( 'Array parameter is empty' );
				if ( $this->operand == self::O_IN )
				    $value = array('$in'=>$this->value); 
				else
				    $value = array('$nin'=>$this->value); 
				break;
				
			case self::O_LIKE:
				$value = new MongoRegex("/".$this->value."/");
				break;
				
			case self::O_LIKE_WO:
				throw new StandardException('Not implemented');
				
			default:
				throw new FetcherSQLRuleException('Unknown operand with code '.$this->operand);
		}
		
		return $answer;
	}	
	
}