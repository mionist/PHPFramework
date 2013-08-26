<?php

class DataModel_Advertisement_FromArray extends DataModel_Advertisement{
    
    private $dataarray;
    
    public function __construct( $dataArray, $hostname, $section, $pagetype, $geo = '' ) {
	parent::__construct($hostname, $section, $pagetype, $geo);
	$this->dataarray = $dataArray;
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

/**
 *
 * Пример конфигурационного массива
 * {
 *	'hostname.section.pagetype':[]
 *	'*.section.pagetype':[]
 *	'hostname.*.pagetype':[]
 *	'*.*.pagetype':[]
 * }
 * 
 * Каждый подмассив с данным имеет следующую структуру:
 * {
 *  'head':...,
 *  'foot':...,
 *  'body':...,
 *  'place':...
 * 
 * 
 * 
 * Запрос для получения списка рекламы при просмотре новостей леди
 * new DataModel_Advertisement_FromArray( <array>, 'lady', 'мой малыш', 'viewnews' );
 * 
 * Запрос для получения списка рекламы при просмотре домена it
 * new DataModel_Advertisement_FromArray( <array>, 'it', '', 'domain' );
 * 
 * Запрос для получения списка рекламы при просмотре категории "Слухи-сплетни"
 * new DataModel_Advertisement_FromArray( <array>, 'showbiz', 'Слухи-сплетни', 'category' );
 * 
 */