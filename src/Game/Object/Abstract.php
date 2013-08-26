<?php

/**
 *
 * Таблица, в которой лежит игровой объект, должен обязательно иметь следующие поля:
 * - id - ID объекта
 * - id_parent - ID парента
 * - type_parent - тип парента
 *  
 */

class Game_Object_Abstract{
    // Константы
    const L_BRIEF	    = 10;
    const L_SHORT	    = 20;
    const L_FULL	    = 30;
    
    // Описание объекта
    protected static $database_table = NULL;
    protected static $manifest = array();
    
    // Закрытый конструктор
    private function __construct( $id ){ $this->id = $id; }
    
    // Статические методы и переменные
    private static $FactorizedItems = array();
    /**
     *
     * @param String $typename
     * @param int $id
     * @param int $level
     * @return Game_Object_Abstract
     */
    public static function Factory( $typename, $id = NULL, $level = NULL ){
	if ( isset( $id, self::$FactorizedItems[$typename.'.'.$id] ) ) return self::$FactorizedItems[$typename.'.'.$id];
	
	$className = 'Game_Object_'.$typename;
	$object = new $className( $id );
	if ( isset($id) ) self::$FactorizedItems[$typename.'.'.$id] = $object;
	if ( !isset($level) ) $level = self::L_BRIEF;
	return $object;
	
    }
    
    
    
    // Свойства объекта
    private $id;
    private $level;
    private $level_loaded = FALSE;
    private $data = array();
    private $changes = array();
    private $isTransactionStarted = FALSE;
    
    // Методы
    public final function __call($name, $arguments) {
	$prefix = substr( $name , 0, 3);
	if ( $prefix === 'set' || $prefix === 'get' ){
	    $suffix = substr( $name , 3);
	    if ( $prefix === 'get' ) return $this->_get( $suffix );
	    return $this->_set( $suffix, $arguments[0] );
	}
    }
    
    public final function __get($name) {
	return $this->_get($name);
    }
    
    // Читалки и писалки
    protected final function _get( $field ){
	if ( !isset( static::$manifest[ $field ] ) ) throw new Game_Object_FieldNotInManifestException("Field $field not found in manifest");
	if ( !$this->level_loaded ) $this->load();
    }
    
    protected final function _set( $field, $new_value ){
	if ( !isset( static::$manifest[ $field ] ) ) throw new Game_Object_FieldNotInManifestException("Field $field not found in manifest");
	if ( $field == 'id_parent' || $field == 'id' || $field == 'type_parent'  ) throw new Game_Object_FieldNotInManifestException("Field $field is forbidden to direct edition");
	if ( !$this->level_loaded ) $this->load();
	$this->data[$field] = $new_value;
	$this->changes[$field] = $new_value;
	if ( !$this->isTransactionStarted ) $this->commit();
    }
    
    protected final function startTransaction(){$this->isTransactionStarted = TRUE;}
    private function commit(){
	$this->isTransactionStarted = FALSE;
    }
    
    private function escalate( $toLevel ){
	$this->level = $toLevel;
	$this->level_loaded = FALSE;
	$this->load();
    }
    
    private function load(){
	// Получаем перечень полей, которые будем использовать на текущем уровне
	$fields = array( 'id','id_parent','type_parent' );
	foreach ( static::$manifest as $row ){
	    if ( is_array( $row ) ) $fields[] = $row['field'];
	}
	
	// Читаем их из БД
	$fields = implode( ',', array_map( function($a){ return "`$a`"; }, $fields) );
	Core::getDatabase()->getBuilder('plain')
		->select( static::$database_table, $fields.' FROM ?? WHERE `id`='.$this->id.' LIMIT 1' )
		->exec();
	if ( count(Core::getDatabase()->result) != 1 ) throw new Game_Object_NotExists();
	$this->data = Core::getDatabase()->result->getData();
	$this->level_loaded = TRUE;
    }
    
}