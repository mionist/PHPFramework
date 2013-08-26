<?php

class Export_Table {
	
	const CSV			= 1;
	const JSON			= 2;
	
	const COLUMN_FORMAT_UNKNOWN			= 0;
	const COLUMN_FORMAT_TEXT			= 1;
	const COLUMN_FORMAT_INTEGER			= 2;
	const COLUMN_FORMAT_DECIMAL			= 3;
	const COLUMN_FORMAT_CURRENCY		= 4;
	const COLUMN_FORMAT_BOOLEAN			= 10;
	const COLUMN_FORMAT_USER_DEFINED	= 1000;
	
	/**
	 * 
	 * Factory for table-based export
	 * @param int $type
	 * @param Object $wrapObject
	 * @throws StandardException
	 * @return Export_Table
	 */
	public static function Factory( $type, $wrapObject = null ){
		switch ( $type ){
			case self::CSV:
				return new Export_Table_CSV( $wrapObject );
			case self::JSON:
				return new Export_Table_JSON( $wrapObject );
			default:
				throw new StandardException("Unknown table type - $type");
		}
	}
	
	// ABSTRACT
	public function export(){ throw new StandardException("Unimplemented"); }
	public function exportOutput(){ echo $this->export(); }
	
	
	
	// Helpers
	protected $data					= array();
	protected $columns				= array();
	protected $columns_count		= 0;
	
	protected $charset				= 'utf-8';
	
	public function __construct( $wrapObject = NULL ){
		if ( $wrapObject != NULL && is_object($wrapObject) && $wrapObject instanceof Iterator ) $this->add( $wrapObject );
	}
	
	public function setCharset( $value ) { $this->charset = strtolower($value); }
	public function getCharset( $value ) { return $this->charset; }
	
	public function add( $object ){
		if ( $object == NULL ) return;
		if ( is_object( $object ) && $object instanceof Iterator ) foreach ( $object as $row ) $this->add( $row );
		else {
			$this->columns_count = max( $this->columns_count, count( $object ) );
			$this->data[] = $object;
		}
	}
	
	public function setColumnAttribute( $name, $attribute, $value ){
		if ( !isset($this->columns[$name]) ) $this->columns[$name] = array();
		$this->columns[$name][$attribute] = $value;
	}
	
	public function setColumnType( $name, $type ){
		$this->setColumnAttribute($name, 'type', $type);
	}
	
	
	public function detectColumnsFormat(){
		reset( $this->data );
		foreach ( $this->data as $row ){
			// Проходимся по колонкам
			foreach ( $row as $col=>$value ){
				if ( !isset($this->columns[$col]) ) $this->columns[$col] = array();
				if ( !isset($this->columns[$col]['type']) ) $this->columns[$col]['type'] = self::COLUMN_FORMAT_UNKNOWN;
				if ( $this->columns[$col]['type'] != self::COLUMN_FORMAT_UNKNOWN ) continue; // Тип уже определён
				
				if ( !is_numeric( $value ) ) $this->columns[$col]['type'] = self::COLUMN_FORMAT_TEXT;
			}
		}
		
		// Делаем замену
		foreach ( $this->columns as $k=>$v ){
			if ( $v['type'] == self::COLUMN_FORMAT_UNKNOWN ) $this->columns[$k]['type'] = self::COLUMN_FORMAT_DECIMAL;
		}
	}
	
	protected final function formatValue( $value, $column ){
		if ( !is_array( $column ) ) {
			if ( !isset($this->columns[$column]) ) $type = self::COLUMN_FORMAT_TEXT;
			else $type = $this->columns[$column]['type'];
		} else $type = $column['type'];
		switch ( $type ){
			case self::COLUMN_FORMAT_CURRENCY:
				return number_format( $value + 0, 2, '.', '' );
			case self::COLUMN_FORMAT_INTEGER:
				return (int) $value;
			case self::COLUMN_FORMAT_BOOLEAN:
				return (boolean) $value;
			case self::COLUMN_FORMAT_DECIMAL:
				return (float) $value;
			default:
				return trim($this->encodeString($value));
		}
	}
	
	protected final function encodeString( $value ){
		if ( $this->charset == 'utf-8' || $this->charset == 'utf8' ) return $value;
		return mb_convert_encoding( $value, $this->charset);
	}
	
	protected final function needEscape( $column ){
		if ( !is_array( $column ) ) {
			if ( !isset($this->columns[$column]) ) return TRUE;
			$column = $this->columns[$column];
		}
		switch ( $column['type'] ){
			case self::COLUMN_FORMAT_CURRENCY:
			case self::COLUMN_FORMAT_INTEGER:
			case self::COLUMN_FORMAT_BOOLEAN:
			case self::COLUMN_FORMAT_DECIMAL:
				return FALSE;
		}
		return TRUE;
	}
	
}