<?php

interface GeoIP_Interface{
    
    const GEOIP_UNKNOWN_COUNTRY		    = '--';
    
    
    public function __construct( $ip );
    
    /**
     * Функция, возвращающая код страны в UPPERCASE 
     */
    public function getCountryCodeUppercase( $defaultCountry = NULL );
    
    
    /**
     * Функция, возвращающая названия города в UPPERCASE 
     */
    public function getCityUppercase( $defaultCity = NULL );
    
}