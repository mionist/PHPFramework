<?php
class Renderable_HTMLInput extends Renderable_HTML{
	
	public function __construct( $name, $value, $isPassword = FALSE, $default = NULL, $classes = NULL, $styles = NULL, $pattern = NULL ){
		$attributes = NULL;
		if ( $pattern != NULL ) $attributes = array('pattern'=>$pattern);
		
		parent::__construct( 
			( $isPassword ? Renderable_HTML::HTML_PASSWORD : Renderable_HTML::HTML_INPUT ),
			$name,
			$name,
			$classes,
			$styles,
			NULL,
			( $isPassword ? NULL : $value ),
			$default, NULL, $attributes
		 );
	}
	
}