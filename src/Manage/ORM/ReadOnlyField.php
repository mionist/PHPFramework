<?php

class Manage_ORM_ReadOnlyField extends Manage_ORM_Field{
    
    public function isReadOnly(){ return TRUE; }
    public function prepareSaveData( $oldData, $arrayWithValues ){ return array(); }
}
