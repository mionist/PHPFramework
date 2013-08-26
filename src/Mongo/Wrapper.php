<?php
/**
 * @internal 
 */
class Mongo_Wrapper{
    
    private static $config;
    private static $instance;
    
    public static function getMongo(){
        if ( !isset( self::$instance ) ){
            if ( !self::exists() ) throw new StandardException("PHP Mongo driver is not present");
            
            // Loading config
            self::$config = include(Configuration::PATH_LOCAL.'config/Mongo.php');
            
            $p = array( 'db'=>self::$config->db );
            if ( isset(self::$config->username) && self::$config->username != '' ){
                $p['username'] = self::$config->username;
                $p['password'] = self::$config->password;
            }
            self::$instance = new Mongo( 'mongodb://'.self::$config->host.':27017', $p );
        }
        return self::$instance;
    }
    
    public static function getDB(){
        $m = self::getMongo();
        return $m->selectDB( self::$config->db );
    }
    
    public static function exists(){
        return class_exists( 'Mongo', FALSE);
    }
}