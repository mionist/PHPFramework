<?php
class Renderable_HTMLButton extends Renderable_HTML{
	
	public function __construct( $name, $text, $is_submit = false, $classes = NULL, $styles = NULL ){
		parent::__construct( 
			( $is_submit ? Renderable_HTML::HTML_BUTTON_SUBMIT : Renderable_HTML::HTML_BUTTON ),
			$name,
			$name,
			$classes,
			$styles,
			NULL,
			$text
		 );
	}
	
}