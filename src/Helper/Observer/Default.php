<?php

class Helper_Observer_Default extends Helper_Observer_Abstract{
    
    private function output( $message, $stdout = TRUE ){
	$ts = date('[Y-m-d H:i:s]');
	if ( $stdout ){
	    echo $ts.'  '.$message."\n";
	}
    }
    
    public function notify($message) {
	$this->output($message, TRUE);
    }
    
}