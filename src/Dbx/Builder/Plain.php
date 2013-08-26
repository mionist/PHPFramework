<?php
class Dbx_Builder_Plain implements DbxBuilderInterface{
	
	/**
	 * 
	 * @var Dbx
	 */
	private $linked_object;
	
	private $table;
	private $SQL;
	private $cache_key;
	private $cache_duration;
	
	private $callback;
	
	private $fast;
	
	public function __construct( $oid, $fast = FALSE ){ $this->linked_object = $oid; $this->erase($fast); }

	public function erase(){
		$this->table = NULL;
		$this->SQL = NULL;
		$this->cache_key = NULL;
		$this->cache_duration = NULL;
	}
	
	public function exec( $onlyOutputNotExec = FALSE ){
		if ( $onlyOutputNotExec )
			echo $this->getSQL()."\n";
		else
			$this->linked_object->invokeBuilder( $this );
	}
	
	public function getTable(){ return $this->table; }
	public function getSQL(){ return $this->SQL; }
	public function getCacheKey(){ return $this->cache_key; }
	public function getCacheDuration(){ return $this->cache_duration; }
	public function isCacheable(){ return isset($this->cache_key, $this->cache_duration); }
	public function getCallback(){ return $this->callback; }
	public function isCallbackAble(){ return isset($this->callback) && is_callable( $this->callback ); }
	
	public function setCacheParams( $key, $duration = 60 ){
		$this->cache_key = $key;
		$this->cache_duration = $duration;
		
		return $this;
	}
	
	public function setCallback( $function ){ $this->callback = $function; return $this; }
	
	public function select( $table, $SQL, $statementParameters = null ){
		if ( strpos($SQL, '??') !== false ) $SQL = str_replace( '??' , "`$table`", $SQL);
		if ( $statementParameters !== NULL && count( $statementParameters ) > 0 ) {
			foreach ( $statementParameters as &$sp ) {
			    if (is_array( $sp ) ){
                    $sp = array_map( function($a){ return $this->linked_object->escape($a); }, $sp );
                    $sp = implode(',',array_map(function($a){ return "'".$a."'"; },$sp));
                }else
				    $sp = '"'.$this->linked_object->escape($sp).'"';
			}
			$i = 0;
			while ( TRUE ){
				if ( ($pos = strpos( $SQL, '?')) === FALSE ) break;
				if ( !isset($statementParameters[$i]) ) break;
				$SQL = substr_replace( $SQL, $statementParameters[$i], $pos, 1);
				$i++;
			}
			$this->SQL = $SQL;
		}
		else $this->SQL = $SQL;

		$this->SQL = 'SELECT '.$this->SQL;
		
		$this->table = $table;
		if ( $this->fast ) return $this->exec();
		return $this;
	}
	
	public function describe( $table ){
		$this->SQL = "SHOW FULL COLUMNS FROM `$table`";
		$this->table = $table;
		if ( $this->fast ) return $this->exec();
		return $this;
	}
	
	public function status( $table ){
		$this->SQL = "SHOW TABLE STATUS LIKE '$table'";
		$this->table = $table;
		if ( $this->fast ) return $this->exec();
		return $this;
	}
	
	public function show( $something, $table = '*' ){
		$this->SQL = "SHOW $something";
		$this->table = $table;
		if ( $this->fast ) return $this->exec();
		return $this;
	}
	
	public function showCreate( $table ){
		$this->SQL = "SHOW CREATE TABLE `$table`";
		$this->table = $table;
		if ( $this->fast ) return $this->exec();
		return $this;
	}	
	
	public function update( $table, $array, $conditionOrId ){
		$values = array();
		foreach ( $array as $k=>$v ){
			$k = "`$k`";
			if ( !is_numeric($v) ) $v = $this->linked_object->escape($v);
			$v = "'$v'";
			$values[] = " $k = $v ";
		}
		if ( is_numeric( $conditionOrId ) ) $conditionOrId = " `id`= '$conditionOrId' LIMIT 1";
		$this->SQL = 'UPDATE `'.$table.'` SET '.implode( ', ', $values ).' WHERE '.$conditionOrId;
		$this->table = $table;
		$this->cache_key = NULL; // Отключаем кеширование
		if ( $this->fast ) return $this->exec();
		return $this;
	}
	
	public function insert( $table, $array ){
		$values = array();
		$keys = array();
		foreach ( $array as $k=>$v ){
			if ( !is_numeric($v) ) $v = $this->linked_object->escape($v);
			//$v = "'$v'";
						
			$keys[] = '`'.$k.'`';
			$values[] = "'$v'";
		}
				
		$this->SQL = 'INSERT INTO `'.$table.'`('.implode(', ', $keys).') VALUES ('.implode(', ', $values).')';
		$this->table = $table;
		$this->cache_key = NULL; // Отключаем кеширование
		if ( $this->fast ) return $this->exec();
		return $this;
	}
	
	public function insertOrUpdate( $table, $array, $arrayForUpdate = NULL ){
		if ( !isset($arrayForUpdate) ) $arrayForUpdate = $array;
		$values = array();
		$keys = array();
		foreach ( $array as $k=>$v ){
			if ( !is_numeric($v) ) $v = $this->linked_object->escape($v);
			$keys[] = '`'.$k.'`';
			$values[] = "'$v'";
		}
		$update_values = array();
		foreach ( $arrayForUpdate as $k=>$v ){
			$k = "`$k`";
			if ( !is_numeric($v) ) $v = $this->linked_object->escape($v);
			$v = "'$v'";
			$update_values[] = " $k = $v ";
		}				
		$this->SQL = 'INSERT INTO `'.$table.'`('.implode(', ', $keys).') VALUES ('.implode(', ', $values).') ';
		$this->SQL .= 'ON DUPLICATE KEY UPDATE '.implode( ', ', $update_values );
		$this->table = $table;
		$this->cache_key = NULL; // Отключаем кеширование
		if ( $this->fast ) return $this->exec();
		return $this;
	}
	
	public function delete( $table, $conditionOrId ){
		if ( is_numeric( $conditionOrId ) ) $conditionOrId = " `id`= '$conditionOrId' LIMIT 1";
		$this->SQL = "DELETE FROM `$table` WHERE $conditionOrId";
		$this->table = $table;
		$this->cache_key = NULL; // Отключаем кеширование
		if ( $this->fast ) return $this->exec();
		return $this;
	}
	
	public function increase( $table, $field, $delta, $conditionOrId ){
		$delta = floatval( $delta );
		$sign = '+';
		if ( $delta < 0 ){
			$sign = '-';
			$delta = 0-$delta;
		}
		
		if ( is_numeric( $conditionOrId ) ) $conditionOrId = " `id`= '$conditionOrId' LIMIT 1";
		$this->SQL = "UPDATE `$table` SET `$field` = `$field` $sign $delta WHERE $conditionOrId";
		$this->table = $table;
		if ( $this->fast ) return $this->exec();
		return $this;
	}

    public function setSQLVar( $param, $value ){
        $this->SQL = 'SET @'.$param.' = '.$value;
        return $this;
    }
}
