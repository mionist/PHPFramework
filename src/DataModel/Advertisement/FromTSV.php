<?php

class DataModel_Advertisement_FromTSV extends DataModel_Advertisement{
    
    private $dataarray;
    
    public function __construct( $filename, $hostname, $section, $pagetype, $geo = '' ) {
	parent::__construct($hostname, $section, $pagetype, $geo);
	$fp = fopen( $filename, 'r' );
	$headers = fgets( $fp );
	$headers = explode( "\t", $headers );
	
	$this->dataarray = array();
	while ( $row = fgets( $fp ) ){
	    $row = explode("\t", $row);
	    if ( count($row) < 2 ) continue;
	    $t = array();
	    foreach ( $headers as $k=>$v ){
		if ( isset($row[$k]) ) $t[$v] = $row[$k];
		else break;
	    }
	    if ( !isset($t['filter']) ) continue;
	    if ( !isset( $this->dataarray[$t['filter']] ) ) $this->dataarray[$t['filter']] = array();
	    $this->dataarray[$t['filter']][] = $t;
	}
	fclose( $fp );
	
    }

    protected function readAdvertisements() {
	// Комбинируем массив ответа
	$answer = array();
	//if ( isset( $this->dataarray[ $this->adv_domain.'.'.$this->adv_section.'.'.$this->adv_pagetype.'.' ] ) ) $answer = array_merge( $answer, $this->dataarray[ $this->adv_domain.'.'.$this->adv_section.'.'.$this->adv_pagetype.'.' ] );
	$this->extendArray($answer, $this->adv_domain.'.'.$this->adv_section.'.'.$this->adv_pagetype);
	$this->extendArray($answer, '*.'.$this->adv_section.'.'.$this->adv_pagetype);
	$this->extendArray($answer, $this->adv_domain.'.*'.'.'.$this->adv_pagetype);
	$this->extendArray($answer, '*.*'.'.'.$this->adv_pagetype);
	
	return $answer;
    }
    
    protected function extendArray( &$data, $key ){
	if ( isset( $this->dataarray[$key] ) && count( $this->dataarray[$key] ) > 0 ) $data = array_merge ( $data, $this->dataarray[$key] );
    }
}