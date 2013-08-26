<?php

class Auth_Facebook extends Auth_Abstract{
	
	/**
	 * 
	 * @var Auth_Bundle_Database
	 */
	protected $api_id;
	protected $api_secret;
	
	public function __construct( $key, $secret ){
		$this->api_id = $key;
		$this->api_secret = $secret;
	}
	
	public function checkCredentials( $forceUserPresence = FALSE ){
		Core::Require3rdParty('com.facebook', 'facebook.php');
		$facebook = new Facebook(array(  
			'appId'  => $this->api_id,  
			'secret' => $this->api_secret,  
			'cookie' => TRUE  
		));
		$user = $facebook->getUser();
		if ( !empty($user) ) return 'FB::'.$facebook->getUser();
		if ( $forceUserPresence )
			Core::$out->redirect( $facebook->getLoginUrl() );
		else 
			throw new Exception_UserNotFound("User not authorized");
	}
	
	public function getAbout(){
		return 'Используется авторизация Facebook';
	}
	
	protected function loadUserdata(){
		$this->userdata = array();
	}
	
	protected function checkRights($rightName){
		return FALSE;
	}
	
	protected function checkPrivileged(){
		return FALSE;
	}
	
}