<?php

class Auth_Hardcoded extends Auth_Abstract{
    
    private $users;
    
    public function __construct( $usersAssocArray = NULL ) {
	if ( !isset( $usersAssocArray ) ) $usersAssocArray = array('demo'=>'demoX');
	$this->users = $usersAssocArray;
    }
    
protected function checkPrivileged() {
	return TRUE;
    }
protected function checkRights($rightConstant) {
	return TRUE;
    }
protected function loadUserdata() {
	return;
    }
public function checkCredentials($bundleArray) {
	$login = ( isset($bundleArray,$bundleArray['login']) ? $bundleArray['login'] : Core::$in->_post['login'] );
	$password = ( isset($bundleArray,$bundleArray['password']) ? $bundleArray['password'] : Core::$in->_post['password'] );
	
	if ( !isset($this->users[$login]) ) throw new Exception_UserNotFound();
	if ( $this->users[$login] != $password ) throw new Exception_UserNotFound();
	
	return 1;
    }
}