<?php

class Renderable_Manage_Proxy extends Renderable_Item{
	protected function produceContext( $context ){
		try{
                    if ( defined('Configuration::ADMIN_PROXY') ) return Configuration::ADMIN_PROXY.$this->data;
                } catch( Exception $e ){
                    return '/'.Configuration::MANAGE_URL.'/proxy/'.$this->data;
                }
                return '/'.Configuration::MANAGE_URL.'/proxy/'.$this->data; 
	}
}
