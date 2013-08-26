<?php
/**
 * Created by JetBrains PhpStorm.
 * User: gotter
 * Date: 12.12.11
 * Time: 9:25
 * To change this template use File | Settings | File Templates.
 */

abstract class Auth_Transport_Abstract implements ArrayAccess {
	protected $data;
	protected $changes;
	protected $implicitSave 			= FALSE;

	public final function checkCookie(){
		if ( isset($this->data) ) return TRUE;
		return $this->doCheck();
	}

	public final function init(){
		$this->doInit();
	}

	public final function save(){
		if ( !isset($this->changes) ) return;
		$this->doSave();
		$this->changes = NULL;
	}

	public final function destroy(){
		if ( !isset($this->data) ) return;
		$this->doDestroy();
		$this->data = NULL;
		$this->changes = NULL;
	}

	protected abstract function doCheck();
	protected abstract function doInit();
	protected abstract function doSave();
	protected abstract function doDestroy();

	/* Временные данные */
	public function setExternalData( $key, $value ){ return FALSE; }
	public function getExternalData( $key ){ return NULL; }

	/* Array Access */
	public final function offsetExists($offset){
		if ( !isset($this->data) ) $this->doInit();
		return isset($this->data[$offset]);
	}

	public final function offsetGet($offset){
		if ( !isset($this->data) ) $this->doInit();
		if ( !isset($this->data[$offset]) ) return $this->data[$offset];
		return $this->data[$offset];
	}

	public function offsetSet($offset, $value){
		if ( !isset($this->data) ) $this->doInit();
		if ( !isset($this->changes) ) $this->changes = array();
		$this->data[$offset] = $value;
		$this->changes[$offset] = $value;
	}

	public function offsetUnset($offset){
		throw new StandardException("Unimplemented");
	}

	/* Destructor */
	public final function __destruct(){ $this->save(); }


}