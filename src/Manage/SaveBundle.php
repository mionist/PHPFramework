<?php

class Manage_SaveBundle {
	private $uid;
	private $oid;
	private $log_table;
	
	private $debug_mode = FALSE;
	
	public function __construct( $uid, $log_table = NULL ){
		$this->uid = $uid;
		if ( $uid < 1 ) throw new MediantSecurityException("Пользователь не опознан");
		$this->log_table = $log_table;
		$this->oid = time().'-'.$this->uid;
	}
	
	public function setDebuggingMode( $value ){ $this->debug_mode = (bool) $value; }
	
        public function fetchHistory( $table, $id ){
            if ( !isset($this->log_table) ) return array();
            Core::getDatabase()->getBuilder('plain')
            ->select( $this->log_table, '* FROM ?? WHERE `table`=? AND `entity`=?', array( $table, $id ) )
            ->exec();
            
            $answer = array();
            foreach ( Core::getDatabase()->result->getData() as $row ) $answer[] = new DataModel_Manage_LogEntry ($row);
            return $answer;
        }
        
	private function saveToLog( $table, $type, $entity, $field, $value_old, $value_new ){
		if ( !isset( $this->log_table ) ) return;
		if ( empty($value_new) && empty($value_old) ) return;
		Core::getDatabase()->getBuilder('plain')
		->insert( $this->log_table, array(
			'uid'=>$this->uid,
			'oid'=>$this->oid,
			'entity'=>$entity,
			'type'=>$type,
			'table'=>$table,
			'field'=>$field,
			'value_old'=>$value_old,
			'value_new'=>$value_new,
			'ip'=>$_SERVER['REMOTE_ADDR']
		) )
		->exec( $this->debug_mode );
	}
	
	public function deleteById( $id, $table ){
		$id = (int) $id;
		Core::getDatabase()->getBuilder('plain')
		->select($table, '* FROM ?? WHERE `id`=? LIMIT 1',array($id))
		->exec();
		if ( Core::getDatabase()->result->count() == 0 ) return FALSE;
		$save = Core::getDatabase()->result->getRow();
		unset($save['id']);
		
		foreach ( $save as $k=>$v ) $this->saveToLog($table, 'delete', $id, $k, $v, '');
		
		Core::getDatabase()->getBuilder('plain')
		->delete($table, $id)
		->exec( $this->debug_mode );
		return true;
	}
	
	public function insertInto( $table, $save ){
		Core::getDatabase()->getBuilder('plain')
		->insert( $table, $save )
		->exec( $this->debug_mode );
		$id = Core::getDatabase()->result->insert_id;
		// Пишем в логи
		foreach ( $save as $k=>$v ) {
			if ( $v === '' ) continue;
			$this->saveToLog($table, 'add', $id, $k, '',$v);
		}
		return $id;
	}
	
	public function replaceIn( $id, $table, $save ){
		// Читаем старое значение
		$id = (int) $id;
		Core::getDatabase()->getBuilder('plain')
		->select($table, "* FROM ?? WHERE `id`=? LIMIT 1", array($id))
		->exec();
		if ( Core::getDatabase()->result->count() == 0 ) return FALSE;
		$old = Core::getDatabase()->result->getRow();
		// Оставляем только изменяемое
		$final = array();
		foreach ( $save as $k=>$v ){
			if ( trim($v) == trim($old[$k]) ) continue;
			$final[$k] = $v;
		}
		
		// Делаем замену
		if ( !count($final) ) return TRUE;
		
		Core::getDatabase()->getBuilder('plain')
		->update($table, $final, $id)
		->exec( $this->debug_mode );
		// Пишем в логи
		foreach ( $final as $k=>$v ){
			$this->saveToLog($table, 'edit', $id, $k, $old[$k], $v);
		}
		return TRUE;
	}
	
}