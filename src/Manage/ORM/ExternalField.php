<?php

class Manage_ORM_ExternalField extends Manage_ORM_Field{
	
	protected $externalTable;
	protected $fieldList;
	protected $listValues;
	
	public function __construct( $rawData, $externalTable, $externalFieldList ){
		parent::__construct($rawData);
		$this->externalTable = $externalTable;
		$this->fieldList = $externalFieldList;
	} 
	
	public function getTable(){ return $this->externalTable; }
	
	public function isOrderAble(){ return FALSE; }
	public function isText(){ return TRUE; }
	public function isSearchAble(){ return TRUE; }
	
	public final function getValues(){
		$this->lazyReadValues();
		return $this->listValues;
	}
	
	public function getPrimaryField(){
		return $this->fieldList[0];
	}
	
	protected function lazyReadValues(){
		if ( isset($this->listValues) ) return;
		
		Core::getDatabase()->getBuilder('plain')
		->select( $this->externalTable , '`id`,`'.$this->getPrimaryField().'` FROM ?? ORDER BY `'.$this->getPrimaryField().'`')
		->exec();
		
		$hash = array(0=>'Не указано');
		foreach ( Core::getDatabase()->result as $row ) $hash[$row['id']] = ( empty($row[$this->getPrimaryField()]) ? 'id '.$row['id'] : $row[$this->getPrimaryField()]);
		
		$this->listValues = $hash;
	}
	
	public function decodeInRowValue( $row ){
		if ( isset($row[$this->name.'.'.$this->fieldList[0]]) )
			return $row[$this->name.'.'.$this->fieldList[0]];
		return $row[$this->name];
	}
	
}