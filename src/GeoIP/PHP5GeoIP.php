<?php

class GeoIP_PHP5GeoIP implements GeoIP_Interface {
    protected $ip;
    
    private static $cache = array();
    
    public function __construct($ip) {
	$this->ip = $ip;
    }
    
    
    public function getCountryCodeUppercase($defaultCountry = NULL) {
	$this->read();
	
	if ( self::$cache[$this->ip] === FALSE ) return strtoupper(isset($defaultCountry) ? $defaultCountry : GeoIP_Interface::GEOIP_UNKNOWN_COUNTRY);
	return strtoupper( self::$cache[$this->ip]['country_code'] );
    }
    
    public function getCityUppercase($defaultCity = NULL) {
	$this->read();
	
	if ( self::$cache[$this->ip] === FALSE ) return strtoupper(isset($defaultCity) ? $defaultCity : '');
	return strtoupper( self::$cache[$this->ip]['city'] );
    }

    
    // This function is not in interface!!!!
    public function getDump(){
	$this->read();
	return self::$cache[$this->ip];
    }
    
    private function read(){
	if ( isset(self::$cache[$this->ip]) ) return;
	if ( !function_exists('geoip_record_by_name') ) return self::$cache[$this->ip] = FALSE;
	
	try{
	    return self::$cache[$this->ip] = geoip_record_by_name( $this->ip );
	} catch ( Exception $e ){
	    return self::$cache[$this->ip] = FALSE;
	}
    }
}