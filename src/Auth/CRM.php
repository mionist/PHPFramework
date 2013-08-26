<?php

class Auth_CRM extends Auth_Abstract{
	
	private static $conn;
	private static $user;
	private static $site;
	private static $pass;
	private static function getConnector(){
		if ( !isset( self::$conn ) ) self::$conn = new CRM_XGate_Connector( self::$user, self::$pass, 'auth');
		return self::$conn;
	}
	
	public function __construct( $APIUsername, $APIPassword, $sitename ){
		self::$user = $APIUsername;
		self::$pass = $APIPassword;
		self::$site = $sitename;
	}
	
	public function checkCredentials( $bundleArray ){
		$login = ( isset($bundleArray,$bundleArray['login']) ? $bundleArray['login'] : Core::$in->_post['login'] );
		$password = ( isset($bundleArray,$bundleArray['password']) ? $bundleArray['password'] : Core::$in->_post['password'] );
		
		// Получаем информацию по пользователю
		try{
			if ( !is_array( $result = self::getConnector()->AuthWithLoginPassword( $login, $password ) ) ) throw new Exception_UserNotFound();
		} catch ( XGateFuncLevelException $e ){
			throw new Exception_UserNotFound();
		}

		return $result['id'].'@crm';
	}
	
	public function startSession($uid){
		parent::startSession($uid);
		$this->loadUserdata();
		$this->getTransport()->setExternalData('rights', $this->userdata['rights']);
	}

	public function getAbout(){
		return 'Для входа используйте логин и пароль от CRM. Также у Вас должно быть соответствующее право.';
	}
	
	protected function checkSession(){
		if ( parent::checkSession() ){
			$this->userdata = array(
				'rights'=>$this->getTransport()->getExternalData('rights')
			);
		}
	}
	
	protected function loadUserdata(){
		try{
			$this->userdata = array(
				'rights'=>self::getConnector()->GetRightsMatrix( intval($this->uid), 'external_manage_' )
			);
		} catch ( Exception $e ){
			$this->dropSession();
			throw new StandardException("CRM Connect error ".$e->getMessage());
		}
	}
	
	protected function checkRights( $right ){
		return in_array('external_manage_'.self::$site, $this->userdata['rights'] );
	}
	
	protected function checkPrivileged(){
		return in_array('external_manage_'.self::$site, $this->userdata['rights'] );
	}
	
}