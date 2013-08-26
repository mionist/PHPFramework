<?php

class Manage_Dashboard implements StandardEventReciever{

    public function bindEvent($eventID, StandardEventReciever $object){}

    public function castEvent($eventID){
        if ( $eventID == StandardEventReciever::START_ADMIN_AJAX ) $this->makeJS ();
        if ( $eventID != StandardEventReciever::START_ADMIN ) return;
        Core::$reg->data['Diagnostic'] = new DataModel_Manage_Diagnostics();
        Core::$reg->data['MOTD'] = new DataModel_Manage_MOTD();
        Core::$out->show('dashboard');
        exit;
    }
    
    private function makeJS(){
        $answer = array();
        $answer['menu'] = Core::$registry->data['ManageMenu'];
        die( JSON::encode($answer) );
    }
    
}