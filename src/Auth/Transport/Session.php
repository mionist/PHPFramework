<?php
/**
 * Created by JetBrains PhpStorm.
 * User: gotter
 * Date: 12.12.11
 * Time: 9:24
 * To change this template use File | Settings | File Templates.
 */

class Auth_Transport_Session extends Auth_Transport_Abstract{

	protected function doInit(){
		if ( !session_id() ) session_start();
		$this->data = $_SESSION;
	}

	protected function doSave(){
		foreach ( $this->changes as $k=>$v )
			$_SESSION[$k] = $v;
	}

	protected function doDestroy(){
		session_destroy();
	}

	protected function doCheck(){
		$this->init();
		return isset( $this->data['id_user'],$this->data['ip_address']) && $this->data['id_user'] != '';
	}
	
	public function getExternalData($key){
		return $this->offsetGet($key);
	}
	
	public function setExternalData($key, $value){
		return $this->offsetSet($key, $value);
	}
}