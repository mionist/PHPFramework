<?php

class Social{
    
    const VK = 1;
    const FACEBOOK = 2;
    const TWITTER = 3;
    const GOOGLEPLUS = 4;
    
    public static function getSharesForURL( $type, $url, $useUTREncode = TRUE, $raiseExceptionsInseadZero = FALSE ){
	if ( $useUTREncode ) $url = urlencode ( $url );
	try{
	    switch ( $type ){
		case self::VK: return self::vkShares($url);
		case self::FACEBOOK: return self::facebookShares($url);
		case self::TWITTER: return self::tweets($url);
		case self::GOOGLEPLUS: return self::googlePlusShares($url);
	    }
	} catch ( Exception $e ){
	    if ( $raiseExceptionsInseadZero ) throw $e;
	    return 0;
	}
	return 0;
    }
    
    private static function facebookShares( $url ){
	$data = file_get_contents('http://graph.facebook.com/?ids='.$url);
	$data = array_values( JSON::decode($data) );
	$data = $data[0];
	if ( !isset($data['shares']) ) return 0;
	return (int) $data['shares']; 
    }
    
    private static function tweets( $url ){
	$data = file_get_contents('http://urls.api.twitter.com/1/urls/count.json?url='.$url);
	$data = JSON::decode( $data );
	if ( !isset($data['count']) ) return 0;
	return (int) $data['count'];
    }
    
    private static function vkShares( $url ){
	$data = file_get_contents('http://vk.com/share.php?act=count&index=1&url='.$url);
	$data = str_replace(' ', '',trim($data));
	$m = array();
	if ( !preg_match('%VK\.Share\.count\(1\,([0-9]*)\)%', $data, $m) ) return 0;
	return (int) $m[1];
    }
    
    private static function googlePlusShares( $url ){
	$data = file_get_contents('https://plusone.google.com/u/0/_/+1/fastbutton?count=true&url='.$url);
	$data = str_replace(' ', '',trim($data));
	$m = array();
	if ( !preg_match('%window\.__SSR=\{c\:([0-9\.]*)\,%', $data, $m) ) return 0;
	return (int) $m[1];
    }
}