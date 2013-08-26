<?php

class DataModel_EntityBuffer extends DataModel_Array{
    
    private $buffer = array();
    private $table;
    private $fields;
    private $callable;
    
    public function __construct( $table, $fields = NULL, $callable = NULL ){
	if ( isset($fields) && !is_array($fields) ) {
	    $fields = array( $fields );
	    if ( !in_array('id', $fields) ) $fields[] = 'id';
	}
	$this->table = $table;
	$this->fields = $fields;
	$this->callable = $callable;
    }
    
    protected function lazyInitialization() {
	$this->data = array();
	if ( count($this->buffer) > 0 ){
	    $fields = '*';
	    if ( isset($this->fields) ) $fields = '`'.implode('`,`', $this->fields).'`';
	    // Читаем
	    Core::getDatabase()->getBuilder('plain')
		    ->select( $this->table, $fields.' FROM ?? WHERE `id` IN ('.implode(',', array_unique($this->buffer)).')' )
		    ->exec();
	    foreach ( Core::getDatabase()->result as $row ){
		$this->data[$row['id']] = $row;
	    }
	    $this->buffer = array();
	}
    }
    
    public function push( $id ){
	if ( isset($this->data) ) throw new StandardException("Object already initialized");
	$this->buffer[] = (int) $id;
    }

    protected function _get($index) {
	$x = parent::_get($index);
	if ( !isset($x) ) return 'неизвестный id: '.$index;
	if ( isset( $this->callable ) ) {
            $c = $this->callable;
            return $c( $x );
        }
	return $x;
    }
}
