<?php
/**
 * @internal 
 */
class Mongo_Configuration{
    public $db;
    public $host;
    public $username;
    public $password;
    
    public function __construct( $db, $host = '127.0.0.1' ) {
        $this->db = $db;
        $this->host = $host;
    }
}