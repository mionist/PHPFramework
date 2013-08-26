<?php
class Manage_ORM_TogglerField extends Manage_ORM_ListField{
	
	protected $listValues;
	protected $symbolic;

	public function __construct( $rawData, $symbolic = FALSE ){
	    parent::__construct($rawData);
	    $this->symbolic = $symbolic;
	}
	
	protected function lazyReadValues(){
		if ( isset($this->listValues) ) return;
		if ( $this->symbolic )
		    $this->listValues = array('n'=>'Нет','y'=>'Да');
		else 
		    $this->listValues = array(0=>'Нет',1=>'Да');
	}
	
	public function isSymbolic(){
	    return $this->symbolic;
	}
	
	public function getName(){
		if ( $this->name == 'show' ) return 'Показывать';
		return parent::getName(); 
	}
	
	public function prepareSaveData( $oldData, $arrayWithValues ){
		// У тогглеров отдельное событие - неинициализированные они FALSE
		if ( !isset( $arrayWithValues[$this->name] ) ) return array( $this->name => ( $this->symbolic ? 'n' : 0 ) );
		return array( $this->name => $arrayWithValues[$this->name] );
	}
	
}