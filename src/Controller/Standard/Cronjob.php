<?php

abstract class Controller_Standard_Cronjob implements StandardEventReciever, StandardObservable{
    /**
     *
     * @var Helper_Observer_Abstract 
     */
    protected $log;
    public function bindObserver( Helper_Observer_Abstract $w ){ $this->log = $w; }

    /**
     *
     * @var Helper_Observer_Abstract 
     */
    protected $error;
    public function bindObserverError( Helper_Observer_Abstract $w ){ $this->error = $w; }
    
    
    public final function bindEvent($eventID, StandardEventReciever $object) {}
    
    public final function castEvent($eventID) {
	if ( $eventID !== StandardEventReciever::START_CLI ) throw new StandardException("This is Cronjob file, it cannot be executed from apache");
        
	$this->log->notify( get_class($this).' started' );
        try {
            $this->prepare();
            $this->cleanOldValues();
        } catch ( Exception $e ){
            return $this->registerException($e);
        }
	$this->log->notify( 'Old values removed, starting processing' );
        try{
            $this->execute();
        } catch ( Exception $e ){
            return $this->registerException($e);
        }
	$this->log->notify( 'Finished processing, done' );
    }
    
    protected $argv;
    public final function __construct() {
	if ( func_num_args() > 0 )
	    $this->argv = func_get_args();
	$this->log = new Helper_Observer_Default();
        $this->error = new Helper_Observer_Default();
    }
    
    private function registerException( Exception $e ){
        $x = new Standard_ExceptionRenderer( $e, true );
        $this->log->notify( $x->buffer );
        $this->error->notify( $x->buffer );
        return;
    }

        /**
     * При необходимости подготавливаем переменные
     */
    protected function prepare(){}
    
    /**
     * Так как это - крон, сначала чистим старые значения
     */
    protected function cleanOldValues(){}
    
    /**
     * Основной код 
     */
    protected abstract function execute();
}