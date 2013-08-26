<?php

class Manage_Compiler implements StandardEventReciever {
	
	public function bindEvent($eventID, StandardEventReciever $object){}
	
	public function castEvent($eventID){
		if ( $eventID == StandardEventReciever::START_ADMIN || $eventID == StandardEventReciever::START_ADMIN_AJAX ) {
		    if ( Core::$in->_post['compile'] == 'yes' ) $this->compile();
		    $this->buildPage($eventID);
		}
	}
	
	private function buildPage( $eventID ){
	    Core::$registry->data['folders'] = $this->getPaths();
	    
	    Core::$out->show('compile');
	}
	
	private function getPaths(){
	    $a = array(
		array('Путь шаблонов view', Configuration::PATH_LOCAL.Configuration::COMPILATION_TEMPLATES.'view/', Configuration::PATH_LOCAL.'view/'),
		array('Путь шаблонов css', Configuration::PATH_LOCAL.Configuration::COMPILATION_TEMPLATES.'css/', Configuration::PATH_LOCAL.'tpl/css/'),
		array('Путь шаблонов js', Configuration::PATH_LOCAL.Configuration::COMPILATION_TEMPLATES.'js/', Configuration::PATH_LOCAL.'tpl/js/'),
	    );
	    foreach ( $a as &$row ){
		$row[3] = $this->check( $row[1], FALSE );
		$row[4] = $this->check( $row[2], TRUE );
	    }
	    return $a;
	}
	
	private function check( $path, $write = FALSE ){
	    return file_exists( $path )
	    && is_dir( $path )
	    && ( $write ? is_writable($path) : is_readable($path) );
	}
	
	private function compile(){
	    
	    // Компилируем CSS-ки
	    $i = 1; $a = $this->getPaths();
	    if ( $a[$i][3] && $a[$i][4] ){
		$dp = opendir( $a[$i][1] );
		$compiler = new Compiler_CSS($a[$i][1], $a[$i][2]);
		while ( $file = readdir($dp) ) {
		    if (is_dir($a[$i][1].$file) ) continue;
		    $compiler->compile( $file );
		}
		closedir($dp);
	    }
	    
	}
}	