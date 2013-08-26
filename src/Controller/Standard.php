<?php

class Controller_Standard implements StandardEventReciever, StandardDataModelBinder{
    public function bindEvent($eventID, StandardEventReciever $object) { /*IGNORING*/ }
    public function castEvent($eventID) {
        if ( StandardEventReciever::START_HTML === $eventID ) return $this->processHTML ();
        if ( StandardEventReciever::START_AJAX === $eventID ) return $this->processAJAX ();
        if ( StandardEventReciever::FINALIZE_INIT === $eventID ) return $this->inflateDataModel ();
        if ( StandardEventReciever::START_ADMIN === $eventID ) return $this->processAdminHTML ();
        if ( StandardEventReciever::START_ADMIN_AJAX === $eventID ) return $this->processAdminAJAX ();
    }
    
    protected function processHTML(){}
    protected function processAJAX(){}
    protected function processAdminHTML(){}
    protected function processAdminAJAX(){}
    
    protected $dataModel;
    public function bindDataModel( DataModel_Abstract $e) {
        $this->dataModel = $e;
    }
    
    protected function inflateDataModel(){}
}