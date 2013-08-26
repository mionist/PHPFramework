<?php

// o->lib->text['section']->offsetGet('value')

class DataModel_SectionedHash extends DataModel_Brick{
	
	public function __construct(){
		parent::__construct();
	}
	
	protected function lazyInitialization(){$this->data = array();}
	
	protected function getCacheBundle(){ return FALSE; }
	
	protected function _get($index){
		if ( isset($this->data, $this->data[$index]) ) return $this->data[$index];
		$this->initialize();
		if ( !isset($this->data[$index]) ){
			// Инициализируем брик по образу и подобию своему
			$copy = $this->configuration;
			if ( isset( $copy['filter'] ) )
				$copy['filter'][] = array( $copy['behaviour'][6], $index );
			else {
				$copy['filter'] = array( array( $copy['behaviour'][6], $index ));
			}
			if ( isset($copy['cache']) ) $copy['cache'][0] .= '_'.$index;
			// Меняем behaviour с sectioned_hash на hash
			$copy['behaviour'][0] = 'hash';
			
			$this->data[$index] = DataModel_Brick::Factory( $copy );
		}
		return $this->data[$index];
	}
	
	public function getKey( $section, $key ){
		$o = $this->_get( $section );
		return $o->offsetGet($key);
	}
}