<?php

// TODO нуна сделать
// FEXME нуна сделать

class Renderable_Table extends Renderable_Item{
    
    protected $className;
    
    public function __construct($data = NULL, $context = NULL, $className = 'RenderableTable') {
	parent::__construct($data, $context);
	$this->className = $this->className;
    }
    
    public function getContext( $ignore = NULL ){
	$answer = '';
	
	$answer .= "<table border='0' cellspacing='0' cellpadding='0' class='".$this->className."'>";
	
	if ( $this->data instanceof Export_Table ){
	    $answer .= '<thead>';
	    $answer .= '</thead>';
	}
	
	$answer .= '</table>';
	
	return $answer;
    }
    
}