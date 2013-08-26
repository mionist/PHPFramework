<?php

abstract class Compiler_Pattern {
    
    private $arguments;
    
    public final function __construct( $arguments = NULL ) {
        $this->arguments = array();
        if ( isset($arguments) ) $this->arguments = $arguments;
    }
    
    public abstract function getContext();
    
    public final function __toString() {
        return $this->getContext();
    }
    
    protected function count(){
        if ( !isset( $this->arguments ) ) return 0;
        return count( $this->arguments );
    }
    
    protected function get( $index, $default = NULL ){
        if ( isset($this->arguments[$index]) ) return $this->arguments[$index];
        if ( !isset($default) ) throw new StandardException("Argument $index not exists");
        return $default;
    }
    
}
