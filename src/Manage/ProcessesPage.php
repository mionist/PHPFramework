<?php

class Manage_ProcessesPage implements StandardEventReciever {
	
	public function bindEvent($eventID, StandardEventReciever $object){}
	
	public function castEvent($eventID){
		if ( $eventID == StandardEventReciever::START_ADMIN || $eventID == StandardEventReciever::START_ADMIN_AJAX ) $this->buildPage($eventID);
	}
	
	public function buildPage( $eventID ){
	    
	    Core::getDatabase()->getBuilder('plain')
		    ->show('FULL PROCESSLIST')
		    ->exec();
	    
	    Core::$registry->data['list'] = Core::getDatabase()->result->getData();
	    Core::$out->show('processes');
	}
}