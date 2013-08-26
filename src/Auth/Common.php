<?php

class Auth_Common extends Auth_Abstract{
	
	/**
	 * 
	 * @var Auth_Bundle_Database
	 */
	protected $parameters;
	
	private static $map = array(
		Auth_Common::ACCESS_ROOT => 'fullaccess',
		Auth_Common::ACCESS_ENGINEER => 'engineer',
		Auth_Common::ACCESS_MODERATOR => 'manager',
		Auth_Common::ACCESS_AUTHOR => 'user'
	);
	
	public function __construct( Auth_Bundle_Database $bundle = NULL ){
		if ( !isset($bundle) ) $bundle = new Auth_Bundle_Database();
		$this->parameters = $bundle;
	}
	
	public function getParams(){ return $this->parameters; }
	
	public function checkCredentials( $bundleArray ){
		$login = ( isset($bundleArray,$bundleArray['login']) ? $bundleArray['login'] : Core::$in->_post['login'] );
		$password = ( isset($bundleArray,$bundleArray['password']) ? $bundleArray['password'] : Core::$in->_post['password'] );
		
		// Получаем информацию по пользователю
		Core::getDatabase()->getBuilder('plain')
		->select( $this->parameters->table , $this->getSQL(), array($login) )
		->exec();
		
		// Найден ли пользователь
		if ( Core::getDatabase()->result->count() == 0 ) throw new Exception_UserNotFound();
		$user = Core::getDatabase()->result->getRow();
		
		// Заблокирован ли пользователь
		if ( $user[$this->parameters->field_banned] == 'banned'
		|| $user[$this->parameters->field_banned] == 'y'
		|| $user[$this->parameters->field_banned] == '1'  
		) throw new Exception_UserBanned();
		
		// Проверка на ресет
		if ( $user[$this->parameters->field_reset] == '1' ) throw new Exception_UserPasswordReset( $user['id'] );
		
		// Проверка пароля
		if ( !isset($this->parameters->field_salt ) ) $salt = '';
		else $salt = $user[$this->parameters->field_salt];
		
		if ( $user['password'] != $this->passwordHash($password, $salt ) ) throw new Exception_UserNotFound();
		
		return $user['id'];
	}
	
	public function resetPassword( $login, $oldPassword, $newPassword ){
		// Получаем информацию по пользователю
		Core::getDatabase()->getBuilder('plain')
		->select( $this->parameters->table , $this->getSQL(), array($login) )
		->exec();
		
		// Найден ли пользователь
		if ( Core::getDatabase()->result->count() == 0 ) throw new Exception_UserNotFound();
		$user = Core::getDatabase()->result->getRow();
		
		// Заблокирован ли пользователь
		if ( $user[$this->parameters->field_banned] == 'banned'
		|| $user[$this->parameters->field_banned] == 'y'
		|| $user[$this->parameters->field_banned] == '1'  
		) throw new Exception_UserBanned();
		
		// Проверка пароля
		if ( !isset($this->parameters->field_salt ) ) $salt = '';
		else $salt = $user[$this->parameters->field_salt];
		
		if ( $user['password'] != '' && $user['password'] != $this->passwordHash($oldPassword, $salt ) ) throw new Exception_UserNotFound();
		
		// Генерируем соль
		$salt = time()/mt_rand(10, 1000) + mt_rand(1, 1000)*mt_rand(1, 1000);
		$salt = dechex( $salt );
		
		$save = array(
			$this->parameters->field_reset => 0,
			$this->parameters->field_password => $this->passwordHash($newPassword, $salt)
		);
		
		if ( isset($this->parameters->field_salt) ) $save[$this->parameters->field_salt] = $salt;
		
		// Меняем
		Core::getDatabase()->getBuilder('plain')
		->update( $this->parameters->table, $save, $user['id'])
		->exec();
	}
	
	public function getAbout(){
		return 'Используется обычная авторизация, на основании таблицы пользователей сайта.';
	}
	
	protected function loadUserdata(){
		Core::getDatabase()->getBuilder('plain')
		->select( $this->parameters->table, '* FROM ?? WHERE `id` = ? LIMIT 1', array($this->uid))
		->exec();
		if ( Core::getDatabase()->result->count() == 0 ) throw new Exception_UserNotFound("User not found on loadUserdata stage");
		$this->userdata = Core::getDatabase()->result->getRow();
		$this->userdata[$this->parameters->field_admin_rights] = explode(',',$this->userdata[$this->parameters->field_admin_rights]);
	}
	
	protected function checkRights($right){
		if ( !isset( self::$map[$right] ) ) return FALSE;
		if ( !in_array( self::$map[$right] , $this->userdata[$this->parameters->field_admin_rights]) ) return FALSE;
		return TRUE;
	}
	
	protected function checkPrivileged(){
		if ( count( $this->userdata[$this->parameters->field_admin_rights] ) > 0 
			&& !in_array('banned', $this->userdata[$this->parameters->field_admin_rights] ) 
			&& $this->userdata[$this->parameters->field_admin_rights][0] != ''
			) return TRUE;
		return FALSE;
	}
	
	public function passwordHash( $password, $salt ){
		switch ( strtolower($this->parameters->method) ){
			case 'sha1':
				return sha1( $salt.$password, FALSE );
			case 'md5':
				return md5( $salt.$password );
		}
		throw new StandardException("Crypt method $method not supported");
	}
        
        public function getTable(){return $this->parameters->table;}
        public function getFieldName(){return $this->parameters->field_login;}
	
	private function getSQL(){
		return "`id`,`{$this->parameters->field_login}`,`{$this->parameters->field_password}`,`{$this->parameters->field_banned}`,`{$this->parameters->field_reset}`"
		.( isset($this->parameters->field_salt) && !empty($this->parameters->field_salt) ? ', `'.$this->parameters->field_salt.'` ' : '' ).
		" FROM ?? WHERE `{$this->parameters->field_login}` = ? LIMIT 1";
	}
	
	protected function reportActivity(){
		Core::getDatabase()->getBuilder('plain')
		->update( $this->parameters->table , array('time_last_access'=>date('Y-m-d H:i:s')), (int) $this->uid)
		->exec();
	}
}