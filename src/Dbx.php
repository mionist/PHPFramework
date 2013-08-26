<?php

class Dbx {
	
	private $configurations				= array();
	private $connections				= array();
	
	private $last_connection;
	
	private $builders;
	
	// Настройки экранирования и деэкранирования
	private $slashing_decode			= true;
	
	/**
	 * 
	 * @var DbxCursor
	 */
	public $result;
	
	/**
	 * 
	 * @return Dbx_Builder_Plain
	 */
	public function getBuilder( $type = 'plain' ){
		if ( !isset($this->builders[$type]) ) switch ( $type ){
			case 'extended':
				$this->builders[$type] = new Dbx_Builder_Extended( $this );
				break;
			case 'plain':
			case 'fast':
				$this->builders[$type] = new Dbx_Builder_Plain( $this, $type === 'fast' );
				break;
			default:
				throw new Exception("Builder $type not supported");
		} else $this->builders[$type]->erase();
		return $this->builders[$type];
	}
	
	/**
	 * Конструктор
	 *
	 * @param array $cnf
	 */
	public function __construct( $cnf = false ){
		if ( $cnf !== FALSE ) $this->addConfiguration( $cnf );
	}
	
	/**
	 * 
	 * @param array $cnf
	 */	
	public function addConfiguration( $cnf ){
		if ( !is_array( $cnf ) ) throw new DbxException('Wrong configuration type');
		foreach ( $cnf as $row ){
			$key = $row->getTable();
			$this->connections[$key] = false;
			$this->configurations[$key] = $row;
		}
	}
	
	private function getConnection( $tableName ){
		// looking for connection
		$c = false;
		if ( isset($this->connections[$tableName]) ) $c = $tableName;
		if ( $c === false ) foreach ( $this->connections as $row=>$object ){
			if ( $row == '*' || strpos( $row, '*' ) === false ) continue;
			$tbl = substr($row, 0, strpos($row,'*'));
			// настройка со звёздочкой
			if ( substr( $tableName, 0, strlen($tbl) ) == $tbl ){
				$c = $row;
				break;
			}
		}
		if ( $c === false && !isset($this->connections['*']) ) throw new DbxException("No suitable connection for table '$tableName'");
		elseif ( $c === false ) $c = '*';
		
		if ( $this->connections[$c] === false ){
			// Устанавливаем подключение
			$this->connections[$c] = new mysqli( 
				$this->configurations[$c]->getServer(),
				$this->configurations[$c]->getUser(),
				$this->configurations[$c]->getPassword(),
				$this->configurations[$c]->getDatabase());
			
			if ( $this->connections[$c]->connect_error ){
				$this->halt( 'Connect to MySQL failed', $this->connections[$c]->connect_error, $this->connections[$c]->connect_errno );
			}
			
			$this->connections[$c]->set_charset( $this->configurations[$c]->getEncoding() );
		}
		
		return $this->connections[$c];
	}
	
	// Экранирование строк
	public function escape( $string ){ return $this->getConnection('*')->real_escape_string( $string ); }
	
	public function invokeBuilder( DbxBuilderInterface $e ){
		if ( $e->isCacheable() && class_exists('Cache') ){
			// Проверяем значение из кеша
			$cache_value = Cache::Get( $e->getCacheKey() );
			if ( $cache_value !== false && is_object( $cache_value ) && $cache_value instanceof DbxCursor ) {
				$this->result = $cache_value;
				return;
			}
		}
		
		// Выполняем запрос
		$this->execute( $this->getConnection( $e->getTable() ), $e->getSQL() );
		
		// Выполняем callback
		if ( $e->isCallbackAble() ) {
			$callback = $e->getCallback();
			$this->result->setData( $callback( $this->result->getData() ) );
		}
		
		// Кешируем результат
		if ( $e->isCacheable() && class_exists('Cache') ){
			Cache::Set( $e->getCacheKey() , $this->result, $e->getCacheDuration() );
		}
	}
	
	private function execute( mysqli $connection, $SQL ){
		$this->freeResult();
		$this->last_connection = $connection;
		$result = $connection->query( $SQL, MYSQLI_STORE_RESULT );
		
		if ( FALSE === $result ) throw new DbxException( "Database error", $SQL, $connection->error, $connection->errno );
		
		if ( !isset($this->result) ) $this->result = new DbxCursor();
		else $this->result->erase();
		
		$this->result->insert_id = $connection->insert_id;
		$this->result->affected_rows = $connection->affected_rows;
		if ( !isset( $result ) || !is_object($result) ) return true;
		
		if ( FALSE && method_exists( $result , 'fetch_all') ) $this->result->setData( $result->fetch_all() );
		else {
			$data = array();
			while ($row = $result->fetch_assoc()) $data[] = $row;
			// Декодируем слеши
			if ( $this->slashing_decode ) foreach ( $data as &$unslash_row ) foreach ( $unslash_row as &$unsash_value ) $unsash_value = stripslashes($unsash_value); 
			$this->result->setData( $data );
		}
		
	}
	
	private function freeResult(){
		if ( !isset($this->last_connection) ) return;
		while ( $this->last_connection->more_results() ) $this->last_connection->next_result();
	}
	
}

class DbxException extends Exception{
	private $SQL;
	private $mysql_text;
	
	public function __construct( $message, $MySQLSQL = null, $MySQLMessage = null, $MySQLCode = 9 ){
		$this->SQL = $MySQLSQL;
		$this->mysql_text = $MySQLMessage;
		parent::__construct($message, $MySQLCode);
	}
	
	public function getSQL(){ return $this->SQL; }
	public function getMySQLMessage(){ return $this->mysql_text; }
}

interface DbxBuilderInterface{
	
	public function exec();
	
	public function getTable();
	public function getSQL();
	public function getCacheKey();
	public function getCacheDuration();
	public function getCallback();
	public function isCacheable();
	public function isCallbackAble();
}


class DbxPlainBuilder implements DbxBuilderInterface{
	
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

	public function erase( $fast = FALSE ){
		$this->table = NULL;
		$this->SQL = NULL;
		$this->cache_key = NULL;
		$this->cache_duration = NULL;
		$this->fast = $fast;
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
			foreach ( $statementParameters as &$sp ) $sp = '"'.$this->linked_object->escape($sp).'"';
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
}


class DbxCursor implements Countable, Iterator, Serializable {
	
	private $data;
	public $affected_rows;
	public $insert_id;
	
	public function __construct( $data = NULL ){
		$this->erase();
		if ( isset($data) && is_array($data) ) $this->data = $data;
	}

	/* Useful */
	public function erase(){ 
		$this->data = array();
		$this->affected_rows = 0;
		$this->insert_id = NULL; 
		return $this; 
	}
	
	public function getValue( $column, $row = 0 ){
		if ( !isset( $this->data[$row] ) || !isset( $this->data[$row][$column] ) ) return NULL;
		return $this->data[$row][$column];
	}
	
	public function getRow( $row = 0 ){
		if ( !isset( $this->data[$row] ) ) return array();
		return $this->data[$row];
	}
	
	public function shuffle(){ shuffle( $this->data ); }
	public function getData(){ return $this->data; }
	public function setData( $data ){ $this->data = $data; }
	
        public function exportColumn( $columnName, $unique = FALSE ){
            if ( !isset($this->data) || !is_array($this->data) || count( $this->data ) == 0 || !isset( $this->data[0][$columnName] ) ) return array();
            $answer = array();
            foreach ( $this->data as $row ){
                $answer[] = $row[$columnName];
            }
            if ( $unique ) $answer = array_unique ($answer);
            return $answer;
        }
        
	/* Common object behavior */
	public function __clone(){ 
		$new = new self( $this->data );
		$new->insert_id = $this->insert_id;
		$new->affected_rows = $this->affected_rows;
		return $new; 
	}
	
	/* Countable interface */
	public function count(){ return count($this->data); }
	
	/* Iterator interface */
	public function rewind(){ return reset( $this->data ); }
	public function current(){ return current( $this->data ); }
	public function key(){ return key( $this->data ); }
	public function next(){ return next( $this->data ); }
	public function valid(){ return key($this->data) !== NULL; }
	
	/* Serializeable interface */
	public function serialize(){ return json_encode( $this->data ); }
	public function unserialize( $serial ){ $this->erase(); $this->data = json_decode( $serial, true ); }
	
}

/**
 * Конфигурационный класс для базы данных
 *
 * @author gotter
 * @package core
 */
class DbxConfiguration{
	private $server;
	private $database;
	private $user;
	private $password;
	private $table;
	private $encoding = 'utf8';
	private $no_caching_mode = false;
	private $transaction_mode = false;
	
	public function __construct( $server, $database, $user, $password, $table = '*' ){
		$this->server = $server;
		$this->database = $database;
		$this->user = $user;
		$this->password = $password;
		$this->table = $table;
	}
	
	public function setEncoding( $value ){ $this->encoding = $value; }
	public function setNoCaching( $value ){ $this->no_caching_mode = $value; }
	public function setTransactionsEnabled( $value ){ $this->transaction_mode = $value; }
	
	public function getServer(){ return $this->server; }
	public function getDatabase(){ return $this->database; }
	public function getUser(){ return $this->user; }
	public function getPassword(){ return $this->password; }
	public function getEncoding(){ return $this->encoding; }
	public function isNoCachingMode(){ return $this->no_caching_mode; }
	public function getTranscationsMode() { return $this->transaction_mode; }
	
	public function getTable(){ return $this->table; }
}