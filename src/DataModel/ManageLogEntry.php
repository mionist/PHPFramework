<?php

class DataModel_ManageLogEntry extends DataModel_Hashtable{
    
    public function __construct( $initialData ) {
        if ( !isset($initialData) ) throw new StandardException("Initial data cannot be empty");
        parent::__construct($initialData);
    }
    
    
    private static $resolvedUsernames = array();
    public function getUsername(){
        if ( isset(self::$resolvedUsernames[$this->data['uid']]) ) return self::$resolvedUsernames[$this->data['uid']];
        $a = Core::$auth;
        if ( $a instanceof Auth_Common ){
            // Пытаемся идентифицировать пользователя
            $id = (int) $this->data['uid'];
            if ( "$id" != $this->data['uid'] ) return $this->data['uid'];
            try{
                Core::getDatabase()->getBuilder('plain')
                        ->select($a->getTable(), " `".$a->getFieldName()."` FROM ?? WHERE `id` = ? LIMIT 1", array($id))
                        ->exec();
                if ( count(Core::getDatabase()->result) == 0 ) return $this->data['uid'];
                return self::$resolvedUsernames[$this->data['uid']] = 'user '.Core::getDatabase()->result->getValue( $a->getFieldName() );
            } catch ( Exception $e ){
                return $this->data['uid'];
            }
        }
        return $this->data['uid'];
    }
    
}