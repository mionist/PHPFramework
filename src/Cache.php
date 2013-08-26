<?php
class Cache{
	
	private static $Initialized			= false;
	private static $Ready				= false;
	
	private static $Connection			= false;
	
	private static $Prefix				= '';
	
	private static function Init(){
		if ( self::$Initialized ) return self::$Ready;
		self::$Initialized = true;
		
		if ( !defined('Configuration::CACHE_ENABLED')
		|| !defined('Configuration::CACHE_PREFIX')
		|| !defined('Configuration::CACHE_SERVER')
		) return FALSE;
		
		if ( !Configuration::CACHE_ENABLED ) return FALSE;
		if ( !function_exists( 'memcache_connect' ) ) return FALSE;
		
		self::$Prefix = Configuration::CACHE_PREFIX;
		
		self::$Connection = @memcache_connect( Configuration::CACHE_SERVER );
		if ( !self::$Connection ) return FALSE;
		
		return self::$Ready = TRUE;
	}
	
	public static function GetFullKeyName( $key ){
		return self::$Prefix.$key;
	}
	
	public static function Get( $key ){
		if ( !self::Init() ) return false;
		return memcache_get( self::$Connection, self::$Prefix.$key );
	}
	
	
	public static function Set( $key, $value, $expiration = 3600 ){
		if ( !self::Init() ) return false;
		return memcache_set( self::$Connection, self::$Prefix.$key, $value, 0, $expiration );
	}
	
	public static function Erase( $key ){
		if ( !self::Init() ) return false;
		return memcache_delete( self::$Connection, self::$Prefix.$key );
	}
	
	public static function getInfo(){
		if ( !self::Init() ) return false;
		return memcache_get_stats( self::$Connection );
	}
}

