<?php

class Manage_DiagnosticPage implements StandardEventReciever {
	
	public function bindEvent($eventID, StandardEventReciever $object){}
	
	public function castEvent($eventID){
		if ( $eventID == StandardEventReciever::START_ADMIN || $eventID == StandardEventReciever::START_ADMIN_AJAX ) $this->buildPage($eventID);
	}
	
	protected function buildPage($eventID){
		Core::$reg->meta->title->push('Диагностика');
		Core::$reg->data['Diagnostic'] = new DataModel_Manage_Diagnostics();
		if ( $eventID == StandardEventReciever::START_ADMIN_AJAX )
			die( JSON::encode( array( 'status'=>'ok', 'data' => Core::$reg->data['Diagnostic'] ) ) );
		else
			Core::$out->show('diagnostic');
	}
	
	
}