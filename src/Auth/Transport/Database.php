<?php
/**
 *
 *
 *
 * @author gotter
 */


class Auth_Transport_Database extends Auth_Transport_Abstract{

        private $hostname;
	protected $table;
	protected $external = array();

	public function __construct( $databaseTable = 'sessions', $hostname = NULL ){
		$this->table = $databaseTable;
                $this->hostname = isset( $hostname ) ? $hostname : $_SERVER['HTTP_HOST'];
	}

        private function getHashName(){
            return str_replace('.', '', 'hash'.$this->hostname);
        }

	protected function doInit(){
		if ( $this->doCheck() ){
			// Пользователь найден, информация уже прочитана
		} else {
			// Удаляем старые сессии
			Core::getDatabase()->getBuilder('plain')
			->delete( $this->table , '`time_update_session` < "'.date('Y-m-d H:i:s', time()-864000).'"')
			->exec();

			// Пользователя ещё нужно внести
			$start = time();
			Core::getDatabase()->getBuilder('plain')
			->insert( $this->table , array(
				'time_start_session'=>date('Y-m-d H:i:s', $start),
				'time_update_session'=>date('Y-m-d H:i:s', $start),
				'hostname'=>$this->hostname
				))
			->exec();
			$sid = Core::getDatabase()->result->insert_id;
			$hash = md5( $sid.get_class($this).$start.$_SERVER['REMOTE_ADDR'].$this->hostname );

			// Обновляем хеш
			Core::getDatabase()->getBuilder('plain')
			->update( $this->table , array('hash'=>$hash), $sid)
			->exec();

			// Устанавливаем куку
			setcookie( 'sid', $sid, 0, '/', $this->hostname );
			setcookie( $this->getHashName(), $hash, 0, '/', $this->hostname );

			$this->data = array('id'=>$sid);
		}
	}

	protected function doSave(){
		Core::getDatabase()->getBuilder('plain')
		->update( $this->table , $this->changes, $this->data['id'])
		->exec();
	}

	protected function doDestroy(){
		setcookie( 'sid', '', 0, '/', $this->hostname );
		setcookie( $this->getHashName(), '', 0, '/', $this->hostname );
		Core::getDatabase()->getBuilder('plain')
		->delete($this->table, $this->data['id'] )
		->exec();
	}

	protected function doCheck(){
		if ( !isset( $_COOKIE['sid'], $_COOKIE[$this->getHashName()] ) || $_COOKIE['sid'] == '' ) return FALSE;
		// Проверяем по базе, одновременно выполняя работу для init
		$sid = (int) $_COOKIE['sid'];
		Core::getDatabase()->getBuilder('plain')
		->select($this->table, '`id`,`id_user`,`ip_address`,`hash`,`external` FROM ?? WHERE `id` = ? AND `hash` = ? AND `ip_address` = ? AND `hostname` = ? ORDER BY `id` DESC', array($sid, $_COOKIE[$this->getHashName()], $_SERVER['REMOTE_ADDR'], $this->hostname))
		->exec();
		if ( Core::getDatabase()->result->count() == 0 ){
			$this->destroy();
			return FALSE;
		}
		$this->data = Core::getDatabase()->result->getRow(0);
		if ( $this->data['external'] != '' ) $this->external = JSON::decode( $this->data['external'] );

		// Обновляем статус
		Core::getDatabase()->getBuilder('plain')
		->update( $this->table , array('time_update_session'=>date('Y-m-d H:i:s')), $this->data['id'])
		->exec();


		return TRUE;
	}

	public function getExternalData($key){
		return $this->external[$key];
	}

	public function setExternalData($key, $value){
		$this->external[$key] = $value;
		$this->changes['external'] = JSON::encode( $this->external );
	}
}