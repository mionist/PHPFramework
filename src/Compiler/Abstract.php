<?php

abstract class Compiler_Abstract{
    
    protected $folder_src;
    protected $folder_dest;
    
    private static $patternsRegEx = '|\[([a-z0-9\#\%\-\_ \,\.а-я]*)\]|uUi';
    
    public function __construct( $folder_src, $folder_dest ) {
	if ( substr($folder_src, -1) != '/' ) $folder_src .= '/';
	if ( substr($folder_dest, -1) != '/' ) $folder_dest .= '/';
	$this->folder_src = $folder_src;
	$this->folder_dest = $folder_dest;
    }

    public final function compile( $filename, $options = NULL ){
	if ( !file_exists($this->folder_src) || !is_readable($this->folder_src) ) throw new Exception_File("Folder {$this->folder_src} not found");
	if ( !file_exists($this->folder_dest) || !is_writable($this->folder_dest) ) throw new Exception_File("Folder {$this->folder_dest} not found or not writeable");
	if ( !file_exists($this->folder_src.$filename) ) throw new Exception_File("File $filename not found");
	$data = file_get_contents( $this->folder_src.$filename );
	$compiled = $this->prepare($data);
	$compiled = $this->make( $compiled, $filename , $options);
        $compiled = $this->makePatterns( $compiled );
	$fp = fopen( $this->folder_dest.$filename , 'w+');
	fputs($fp, $compiled);
	fclose( $fp );
    }
    
    protected function prepare( $data ){return $data;}
    
    protected abstract function make( $data, $options );
    
    public final function test( $data ){
	$data = $this->make($data, NULL, NULL);
        return $this->makePatterns($data);
    }
    
    private function makePatterns( $data ){
        // Ищем паттерны
        $m = array();
        while ( preg_match_all(self::$patternsRegEx, $data, $m) )
            for ( $i=0; $i < count( $m[0] ); $i++ ){
                $arguments = $m[1][$i];
                $arguments = explode(',', $arguments);
                $class = 'Compiler_Pattern_'.$arguments[0];
                if ( count($arguments) == 1 ) $obj = new $class();
                else {
                    $arguments = array_slice($arguments, 1);
                    $obj = new $class( $arguments );
                }
                // Замена
                $data = str_replace( $m[0][$i] , $obj->__toString(), $data);
            }
        
        return $data;
    }
}