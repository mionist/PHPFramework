<?php

class Compiler_CSS extends Compiler_Abstract{
    
    protected function prepare($data) {
	$data = parent::prepare($data);
	return $this->replaceVariables($data);
    }
    
    protected function make($data, $options) {
	return $data;
    }
    
    private function replaceVariables( $data ){
	$m = array();
	if (preg_match_all('|^([a-z0-9а-я_]+)[ =]{1,3}([a-z0-9\-\:\#\%\,]+)$|uUim', $data, $m) ){
	    $vars = array();
	    for ( $i=0; $i < count($m[0]); $i++ ){
		$data = str_replace( $m[0][$i] , '', $data);
		$vars[$m[1][$i]] = $m[2][$i];
	    }
	    // Строим замены
	    $data = str_replace( array_keys($vars) , array_values($vars), $data);
	}
	
	// Убираем пустые строки
	$data = explode("\n",$data);
	foreach ( $data as $k=>$v ){
	    $v = trim($v);
	    if ( $v == '' ) unset( $data[$k] );
	}
	$data = implode("\n",$data);
	return $data;
    }
}