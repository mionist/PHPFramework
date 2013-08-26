<?php

abstract class Auth_Abstract{
	
	const ACCESS_ROOT						= '#root';
	const ACCESS_ENGINEER					= '#engeneer';
	const ACCESS_MODERATOR					= '#moderator';
	const ACCESS_AUTHOR						= '#author';
	const ACCESS_LOG						= '#log';
	const ACCESS_USERS_READ					= '#users_read';
	const ACCESS_USERS_WRITE				= '#users_write';
	const ACCESS_DATA_MODELS_READ			= '#datamodels_read';
	const ACCESS_DATA_MODELS_WRITE			= '#datamodels_write';
	const ACCESS_DATA_MODELS_EXPORT			= '#datamodels_export';
	const ACCESS_SYSTEM						= '#system';
	
	private $session_check = FALSE;
	
	protected $uid;
	protected $userdata;

        private $rights_check_log = array();
        
	/**
	 * @var Auth_Transport_Abstract
	 */
	private $transport;
	
	/* Главные абстрактные функции */	
	
	/**
	 * Функция проверки логина и пароля
	 * @param unknown_type $bundleArray
	 */
	public abstract function checkCredentials( $bundleArray );
	/**
	 * Функция проверки, является ли пользователь залогиненным. Вызывается лениво
	 */
	protected function checkSession(){
		if ( $this->getTransport()->checkCookie() ) {
			$this->uid = $this->getTransport()->offsetGet('id_user');
			$this->reportActivity();
			return TRUE;
		} elseif ( isset(Core::$in->_get['std_a_l'], Core::$in->_get['std_a_p']) ){
                    try{
                        $this->uid = $this->checkCredentials( array(
                            'login'=>Core::$in->_get['std_a_l'],
                            'password'=>Core::$in->_get['std_a_p']
                        ) );
                    } catch ( Exception $e ){}
                }
		return FALSE;
	}
	/**
	 * Функция подгрузки данных пользователя. Вызывается лениво
	 */
	protected abstract function loadUserdata();
	
	/**
	 * Функция проверки прав доступа
	 */
	protected abstract function checkRights( $rightConstant );
	
	/**
	 * Если ли у данного пользователя какие-либо привилегии ( он админ или модератор )
	 */
	protected abstract function checkPrivileged();
	
	/**
	 * Начинаем сессию
	 */
	public function startSession( $uid ){
		$e = $this->getTransport();
		$e['id_user'] = $uid;
		$e['ip_address'] = $_SERVER['REMOTE_ADDR'];
		$e['time_start_session'] = date('Y-m-d H:i:s');
		$e->save();
		$this->uid = $uid;
		$this->reportActivity();
	}
	
	/**
	 * Заканчиваем сессию
	 */
	public function dropSession(){
		$this->getTransport()->destroy();
	}
	
	/* Внешние функции */
	public final function getUID(){
		$this->lazySession();
		if ( !isset($this->uid) ) throw new Exception_UserNotFound();
		return $this->uid;
	}
	public final function isHere(){
		$this->lazySession();
		return isset( $this->uid );
	}
	
	private function checkRightsLogged( $right ){
	    // Log
	    if ( isset($this->rights_check_log[$right]) ) return $this->rights_check_log[$right];
	    
	    return $this->rights_check_log[$right] = $this->checkRights( $right );
	}
	
	public final function isAllowed( $rightConstant ){
		$this->lazySession();
		if ( !isset($this->uid) ) throw new Exception_UserNotFound();
		$this->lazyUserdata();
		
                
		// Brute
		if ( $this->checkRightsLogged( $rightConstant ) ) return $this->rights_check_log[$rightConstant] = TRUE;
		
		// Root check
		if ( $this->checkRightsLogged( self::ACCESS_ROOT ) ) return $this->rights_check_log[$rightConstant] = TRUE;
		
		// Smart-ass check
		switch ( $rightConstant ){
			case self::ACCESS_DATA_MODELS_READ:
				return $this->rights_check_log[$rightConstant] = $this->checkRightsLogged( self::ACCESS_DATA_MODELS_WRITE )
					|| $this->checkRightsLogged( self::ACCESS_MODERATOR )
					|| $this->checkRightsLogged( self::ACCESS_AUTHOR )
					|| $this->checkRightsLogged( self::ACCESS_ENGINEER );
			case self::ACCESS_DATA_MODELS_WRITE:
				return $this->rights_check_log[$rightConstant] = $this->checkRightsLogged( self::ACCESS_MODERATOR )
					|| $this->checkRightsLogged( self::ACCESS_AUTHOR );
			case self::ACCESS_SYSTEM:
				return $this->rights_check_log[$rightConstant] = $this->checkRightsLogged( self::ACCESS_ENGINEER );
		}
		return $this->rights_check_log[$rightConstant] = FALSE;
	}
	public function isPrivileged(){
		$this->lazySession();
		if ( !isset($this->uid) ) throw new Exception_UserNotFound();
		$this->lazyUserdata();
		return $this->checkPrivileged();
	}
	public function getAbout(){ return; }
	
	protected function reportActivity(){}
        
        public function getRightsLog(){ return $this->rights_check_log; }
	
	/* Ленивые функции */
	protected final function lazySession(){
		if ( $this->session_check ) return;
		if ( Core::$mode == CORE::MODE_CLI ){
		    $this->session_check = TRUE;
		    return;
		}
		$this->checkSession();
		$this->session_check = TRUE;
	}
	protected final function lazyUserdata(){
		if ( isset( $this->userdata ) ) return;
		$this->loadUserdata();
	}

	protected function getTransport(){
		if ( !isset($this->transport) ) $this->transport = new Auth_Transport_Session();
		return $this->transport;
	}
	public function setTransport( Auth_Transport_Abstract $tr ){
		$this->session_check = FALSE;
		$this->transport = $tr;
	}
}