<?php

class Dbx_Builder_Extended implements DbxBuilderInterface{
	
	/**
	 * 
	 * @var Dbx
	 */
	private $linked_object;
	
	private $table;
	private $SQL;
	
	private $callback;
	
	public function __construct( $oid ){ $this->linked_object = $oid; }
	
	public function exec( $onlyOutputNotExec = FALSE ){
		if ( $onlyOutputNotExec )
			echo $this->getSQL()."\n";
		else
			$this->linked_object->invokeBuilder( $this );
	}	
	
	public function erase( $fast = FALSE ){
		$this->table = NULL;
		$this->SQL = NULL;
	}
	
	public function getTable(){ return $this->table; }
	public function getSQL(){ return $this->SQL; }
	public function isCacheable(){ return FALSE; }
	public function getCacheKey(){ return NULL; }
	public function getCacheDuration(){ return NULL; }
	public function isCallbackAble(){ return FALSE; }
	public function getCallback(){ return NULL; }
	
	public function lock( $table, $read = TRUE ){
                if ( !is_array( $table ) ) $table = array($table);
		$mode = 'WRITE';
		if ( $read ) $mode = 'READ';
                $da = $table;
                foreach ( $da as &$row ) $row .= ' '.$mode;
                unset($row);
		$this->table = $table[0];
		$this->SQL = "LOCK TABLES ".implode( ', ', $da );
		return $this;
	}
	
	public function unlock(){
		$this->table = '*';
		$this->SQL = "UNLOCK TABLES";
		return $this;
	}
        
        public function alter( $table, $sql ){
                $this->table = $table;
                $this->SQL = "ALTER TABLE `$table` ".$sql;
                return $this;
        }
}
