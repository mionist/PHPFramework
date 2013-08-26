<?php

abstract class DataModel_Advertisement extends DataModel_Hashtable{
    
    public $_head;
    public $_foot;
    
    protected $notLazy = TRUE;
    
    protected $adv_domain;
    protected $adv_section;
    protected $adv_pagetype;
    protected $adv_geo;
    
    public function __construct( $hostname, $section, $pagetype, $geo ) {
	$this->cache_key = 'Adv'.$hostname.$section.$pagetype;
	$this->cache_expiration = 100;
	$this->_head = new DataModel_Array( array() );
	$this->_foot = new DataModel_Array( array() );
	$this->adv_domain = $hostname;
	$this->adv_section = $section;
	$this->adv_pagetype = $pagetype;
	$this->adv_geo = $geo;
    }
    
    protected function getCacheBundle(){
	    $bundle = parent::getCacheBundle();
	    $bundle[] = $this->_head->getArray();
	    $bundle[] = $this->_foot->getArray();
	    return $bundle;
    }
    protected function restoreBundle( $data ){
	    $this->_foot = new DataModel_Array( array_pop( $data ) );
	    $this->_head = new DataModel_Array( array_pop( $data ) );
	    return parent::restoreBundle($data);
    }	    
    
    protected final function lazyInitialization() {
	$structed = $this->readAdvertisements();
	if ( !is_array( $structed ) ) throw new StandardException("Incoming data is not advertisement array");
	$this->data = array();
	foreach ( $structed as $row ){
	    if ( !is_array($row) || !isset($row['body'],$row['place']) ) throw new StandardException("Incoming data row is not valid advertisement map");
	    if ( isset($row['head']) && !empty($row['head']) ) $this->_head->put( $row['head'] );
	    if ( isset($row['foot']) && !empty($row['foot']) ) $this->_foot->put( $row['foot'] );
	    
	    if ( !isset($this->data[$row['place']]) ) $this->data[$row['place']] = array();
	    $this->data[$row['place']][] = $row['body'];
	}
    }
	
    /**
     * Функция readAdvertisements обязана вернуть двухмерный массив 
     * реламных кодов, которые должны быть размещены на текущей странице
     * Формат - двухмерный массив:
     * [{head:'HTML код хедера',foot:'HTML код футера',body:'HTML код непосредственно тела информера',place:'имя места размещения'},...]
     */
    protected abstract function readAdvertisements();
    
}