<?php

class Manage_Cache implements StandardEventReciever {
	
	public function bindEvent($eventID, StandardEventReciever $object){}
	
	public function castEvent($eventID){
		if ( $eventID == StandardEventReciever::START_ADMIN ) $this->buildCachePage();
		if ( $eventID == StandardEventReciever::START_ADMIN_AJAX ) $this->cacheOperation();
	}
	
	protected function buildCachePage(){
		Core::$reg->meta->title->push('Кеш');
		
		$info = array(
		'Настройки кеша'=>array(),
		'Информация'=>array()
		);
		
		$info['Настройки кеша'][] = array( $this->isItBad( Configuration::CACHE_ENABLED, 'error' ), 'Кеш включен', 'ok' );
		$info['Настройки кеша'][] = array('ok', 'Сервер', Configuration::CACHE_SERVER );
		$info['Настройки кеша'][] = array ($this->isItBad( Configuration::CACHE_PREFIX != '', 'error' ), 'Префикс', Configuration::CACHE_PREFIX );
		
		foreach ( Cache::getInfo() as $k=>$v ){
			$info['Информация'][] = array('ok', $k, $v );
		}
		
		Core::$reg->data['CacheInfo'] = $info;
		Core::$out->show('cache');
	}
	
	protected function cacheOperation(){
		switch ( Core::$in->_post->getString('operation') ){
			case 'get':
				$value = Cache::Get( Core::$in->_post['key'] );
				if ( $value != FALSE ) $value = JSON::encode( $value );
				die( JSON::encode( array( 'status'=>'ok', 'mode'=>'get', 'data'=>$value ) ));
			case 'delete':
				Cache::Erase( Core::$in->_post['key'] );
				die( JSON::encode( array( 'status'=>'ok', 'mode'=>'erase' ) ) );
		}
		die( JSON::encode( array( 'status'=>'error', 'error'=>'Unknown operation' ) ) );
	}
	
	protected function isItBad( $value, $badLevel = 'warning' ){
		if ( $this->toBool($value) ) return 'ok';
		return $badLevel;
	}
	
	protected function toBool( $value ){
		if ( $value === TRUE || $value === FALSE ) return $value;
		if ( strtolower( trim($value) ) == 'on' ) return TRUE;
		if ( strtolower( trim($value) ) == 'off' ) return FALSE;
		if ( $value ) return TRUE;
		return FALSE;
	}	
}