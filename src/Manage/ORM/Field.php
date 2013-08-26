<?php

class Manage_ORM_Field{
	
	public $name;
	public $type;
	public $size;
	public $comment;
	public $default;
	
	public $index;
	
	public $pattern = NULL;
	
	protected $forceRO = FALSE; // Force read-only
	
	protected $rawData;
	
	public function __construct( $rawData ){
		$this->rawData = $rawData;
		$this->decodeRawData();
	}
	
	protected function decodeRawData(){
		$this->name = $this->rawData['Field'];
		preg_match('/^([a-z]*)/', $this->rawData['Type'], $matches);
		$this->type = strtolower($matches[1]);
		if ( preg_match('/^[a-z]*\(([0-9]*)\)/', $this->rawData['Type'], $matches)){
			$this->size = $matches[1];
		}
		$this->index = $this->rawData['Key'];
		$this->default = $this->rawData['Default'];
		$this->comment = $this->rawData['Comment'];
	}

	public function getName(){
		if ( !empty($this->comment) ) return $this->comment;
		return $this->name; 
	}
	
	public function isEmpty( $value ){ return !isset($value) || $value === ''; }
	public function decodeValue( $value ){ return $value; }
	public function encodeValue( $value ){ return $value; }
	public function decodeInRowValue( $row ){ return $this->decodeValue( $row[$this->name] ); }
	public function decodeInRowValueForList( $row ){ return htmlspecialchars($this->decodeInRowValue( $row ), NULL, 'utf-8'); }
	public function prepareDelete( $oldData ){}
	public function prepareSaveData( $oldData, $arrayWithValues ){
		if ( !isset( $arrayWithValues[$this->name] ) ) return array();
		return array( $this->name => $arrayWithValues[$this->name] );
	}
	
	
	public function isIndex(){ return $this->index == 'MUL' || $this->index == 'PRI'; }
	public function isReadOnly(){ return ( $this->name == 'id' || $this->forceRO); }
	public function isSearchAble(){ return TRUE; }
	public function isOrderAble(){ return TRUE; }
	public function isToggleAble(){ return FALSE; }
	public function isHiddenInList(){ return FALSE; }
	public function isHiddenInForm(){ return FALSE; }
	public function isLinkInList(){ return TRUE; }
	public function isNumeric(){ return FALSE; }
	public function isText(){ return FALSE; }
	public function isList(){ return FALSE; }
	public function isCompound(){ return FALSE; }
	
	public final function setReadOnly(){ $this->forceRO = TRUE; }
	
	public function cleanUpDataRow( $old, $new ){}
}