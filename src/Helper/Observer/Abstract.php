<?php

abstract class Helper_Observer_Abstract{
    
    private $isVerbose = FALSE;
    
    public final function setVerbose( $verbose = TRUE ){ $this->isVerbose = $verbose; }
    
    public final function verbose( $message ){ if ( $this->isVerbose ) $this->notify ($message); }
    public abstract function notify( $message );
    public function error( $message ){ $this->notify($message); }
    
    // Shortcuts
    public final function v( $message ){ $this->verbose($message); }
    public final function n( $message ){ $this->notify($message); }
    public final function e( $message ){ $this->error($message); }
    
    // Helpers
    public final function process( $i, $count, $start_unixtime ){
	$time = time() - $start_unixtime;
	if ( $count > 10000 ) $point = intval($count / 500);
	elseif ( $count > 1000 ) $point = intval($count / 50);
	else $point = intval($count / 10);
	$percent = intval( $i / $count * 100 );
	$message = "Processing entry #{$i}/{$count} [{$percent}%]";
	if ( $time > 0 && $i > 0 ){
	    $per_unit = $time / $i;
	    $eta = intval(($count - $i) * $per_unit / 60);
	    if ( $eta == 0 ) $message .= " ETA: about a minute";
	    else $message .= " ETA: {$eta} min.";
	}
	if ( $point > 0 && $i % $point == 0 ) $this->notify ( $message );
	else $this->verbose ($message);
    }
}