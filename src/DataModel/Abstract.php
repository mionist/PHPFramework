<?php
/**
 * 
 * Каждая модель данных должна уметь это!
 *
 */
abstract class DataModel_Abstract implements Countable, ArrayAccess, Iterator, StandardEventReciever {
	protected $data;
	protected $event_handlers;
	protected $notLazy = FALSE;
	
	protected $cache_key;
	protected $cache_expiration;
	protected $cache_read_hit = FALSE;
	protected $cache_write_empty_values = TRUE;
	
	public $time_lazy_initialization = 0;
	
	public function __construct( $initialData = NULL ){
		if ( isset( $initialData ) ) $this->inflate($initialData);
	}
	
	protected final function initialize(){
		if ( !isset($this->data) && !$this->readFromCache() ) {
			$start = microtime(true);
			$this->castEvent( StandardEventReciever::INIT );
			$this->lazyInitialization();
			$this->castEvent( StandardEventReciever::INFLATE );
			$this->writeToCache();
			$this->time_lazy_initialization += ( microtime(true) - $start );
                        PerformanceMonitor::report( $this, $this->time_lazy_initialization * 1000);
		}
	}
	
	protected abstract function lazyInitialization();
	
	public function replace( $index, $value ){
		$this->initialize();
		if ( !isset($this->data[$index]) ) throw new StandardException("Index $index not found for replace");
		$this->_set($index, $value);
	}	
	
        public function description(){
            return '';
        }
        
	/* Специальные внутренние функции */
	protected function _get( $index ){
		$this->initialize();
		if ( !isset($this->data[$index]) ) return;
		return $this->data[$index];
	}
	protected function _set( $index, $value ){
		$this->initialize();
		$this->data[$index] = $value;
	}
	protected function _unset( $index ){}
	protected function _exists( $index ){
		$this->initialize();
		return isset( $this->data[$index] );
	}
	
	public function erase(){
		unset($this->data);
		return $this;
	}
        
        public function clear(){
                $this->data = array();
                return $this;
        }
        
	public function isEmpty(){ return ($this->count() == 0); }
	public function valueNotEmpty( $index ){
		if ( !$this->_exists($index) ) return FALSE;
		$value = $this->_get($index);
		if ( trim($value) == '' || $value == '0' ) return FALSE;
		return TRUE;
	}
	/* Заполнить модель данными по-умолчанию */
	public function inflate( $data ){
		$this->erase();
		$this->data = $data;
		$this->castEvent( StandardEventReciever::INFLATE );
		$this->writeToCache();
	}
	
	/* Опасная функция, использовать ТОЛЬКО в обработчиках событий */
	public function &getDataReference(){
		$this->initialize();
		return $this->data;
	}
	
	/* Требуется неленивая инициализация */
	public function isVolatile(){ return FALSE; }
	public final function isNotLazy(){ return $this->notLazy || $this->isVolatile(); }
	
	/* Магические методы НЕ входят в комплект */
	
	/* Кеширование */
	public final function setCache( $key, $expiration = 60 ){
		$this->cache_key = $key;
		$this->cache_expiration = $expiration;
		return $this;
	}
	public final function setCachingOptions( $saveEmpty ){
	    $this->cache_write_empty_values = $saveEmpty;
	}
	public final function isCacheable(){
		return isset( $this->cache_key, $this->cache_expiration );
	}
	public final function writeToCache(){
		if ( !$this->isCacheable() ) return;
		if ( !$this->cache_write_empty_values ){
		    // Не требуется кешировать пустые значения
		    if ( is_array( $this->data ) && count( $this->data ) == 0 ) return;
		    if ( is_object( $this->data ) && $this->data instanceof Countable && count( $this->data ) == 0 ) return;
		}
		$bundle = $this->getCacheBundle();
		if ( $bundle === FALSE ) return;
		return Cache::Set( $this->cache_key, $bundle, $this->cache_expiration );
	}
	public final function readFromCache(){
		if ( !$this->isCacheable() ) return;
		$result = Cache::Get( $this->cache_key );
		if ( $result !== FALSE ) {
			$this->cache_read_hit = TRUE;
			$this->restoreBundle( $result );
			return TRUE;
		} 
		return FALSE;
	}
	
	public final function isFromCache(){ return $this->cache_read_hit; }
	public final function getCacheParams(){ return Cache::GetFullKeyName( $this->cache_key ).'@'.$this->cache_expiration.'s'; }
	
	/* Кешируемые данные */
	protected function getCacheBundle(){
		return array($this->data);
	}
	protected function restoreBundle( $data ){
		$this->data = array_pop($data);
	}
	
	
	/* Countable interface */
	public function count(){
		$this->initialize();
		if ( !isset($this->data) ) return 0;
		return count($this->data);
	}
	
	/* ArrayAccess interface */
	public final function offsetExists($offset){
		return $this->_exists($offset);
	}
	public final function offsetGet($offset){
		return $this->_get($offset);
	}
	public final function offsetSet( $offset, $value ){
		$this->_set($offset, $value);
	}
	public final function offsetUnset( $offset ){
		$this->_unset( $offset );
	}
	
	/* Iterator interface */
	public final function rewind(){
		$this->initialize();
		return reset( $this->data );
	}
	public final function key(){
		$this->initialize();
		return key( $this->data );
	}
	public final function current(){
		return $this->_get( $this->key() );
	}
	public final function next(){
		$this->initialize();
		next($this->data);
		return $this->current();
	}
	public final function valid(){
		$key = $this->key();
		return !( $key === NULL || $key === FALSE );
	}
	
	/* StandardEventReciever interface */
	public function bindEvent($eventID, StandardEventReciever $callback ){
		if ( !isset($this->event_handlers) ) $this->event_handlers = array();
		$this->event_handlers[$eventID] = $callback;
	}
	public function castEvent($eventID){
		if ( !isset($this->event_handlers) || !isset($this->event_handlers[$eventID]) ) return;
		if ( $this->event_handlers[$eventID] instanceof StandardDataModelBinder ) $this->event_handlers[$eventID]->bindDataModel( $this );
		$this->event_handlers[$eventID]->castEvent($eventID);
	}
}