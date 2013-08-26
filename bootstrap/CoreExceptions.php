<?php

class StandardException extends Exception{}

class BootstrapperStandardException extends Exception{}

class PHPStandartException extends StandardException{
	
	public $php_errstr;
	public $php_errfile;
	public $php_errline;
	
	public function __construct($errno, $errstr, $errfile, $errline){
		parent::__construct( "PHP Error" , $errno);
		$this->php_errfile = $errfile;
		$this->php_errline = $errline;
		$this->php_errstr = $errstr;
	}

}

class LoaderStandardException extends Exception{
	public function __construct( $className ){
		parent::__construct("Class $className not found", 0);
	}
}

class IndexOutOfBoundsStandardException extends StandardException{}