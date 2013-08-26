<?php

class Renderable_HTMLSingleSelect extends Renderable_HTML{
	
	public function __construct( $name = null, $values = null, $value = null, $classes = null, $styles = null, $description = null ){
		// Определяем тип записи в зависимости от количества вариантов
		if ( count($values) < 4 )  $type = Renderable_HTML::HTML_RADIO_LINE;
		else $type = Renderable_HTML::HTML_SELECT;
		parent::__construct($type, $name, $name, $classes, $styles, $values, $value, NULL, $description);
	}
	
}