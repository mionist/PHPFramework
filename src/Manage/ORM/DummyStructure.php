<?php

class Manage_ORM_DummyStructure{
    
    private $fields = array();
    
    public function addField( $obj ){
        $this->fields[] = $obj;
        return $this;
    }


    public function getFields(){ return $this->fields; }
    /**
        * @return Manage_ORM_Field
        */
    public function findFieldByName( $name ){
            foreach ( $this->fields as $row ){
                    if ( $row->name == $name ) return $row;
            }
    }    
    
}