<?php

class DateTimeWrapper{
    
    public static $defaultTimezone = NULL;
    
    public static function Factory( $any, $format = NULL, $timezone = NULL ){
	// Already object
	if ( is_object($any) && $any instanceof DateTime ) return $any;
	
	if ( !isset( $timezone ) && isset( self::$defaultTimezone ) ) $timezone = self::$defaultTimezone;
	
	// Assuming unix timestamp
	if ( ''.intval( $any ) == $any ) {
	    if ( isset( $timezone ) )
		return DateTime::createFromFormat ('U', $any, $timezone);
	    return DateTime::createFromFormat ('U', $any);
	}
	// Using format
	if ( isset( $format ) ) {
	    if ( isset( $timezone ) )
		return DateTime::createFromFormat ($format, $any);
	    return DateTime::createFromFormat ($format, $any, $timezone);
	}
	// Creating new
	return new DateTime($any, $timezone);
    }
    
    
    
}