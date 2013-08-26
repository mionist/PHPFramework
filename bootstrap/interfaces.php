<?php

interface StandardEventReciever{
	const INIT 					= 1;
	const FINALIZE_INIT			= 2;
	const INFLATE				= 3;
	const BRICK_BEHAVIOUR_INIT	= 4;
	
	const START					= 100;
	const START_HTML			= 101;
	const START_AJAX			= 102;
	const START_CLI				= 103;
	const START_ADMIN			= 104;
	const START_ADMIN_AJAX		= 105;
	
	public function castEvent( $eventID );
	public function bindEvent( $eventID, StandardEventReciever $object );
}


interface StandardDataModelBinder{
	public function bindDataModel( DataModel_Abstract $e );
}

interface StandardSaveBundleBinder{
	public function bindSaveBundle( Manage_SaveBundle $e );
}

interface StandardObservable {
    	public function bindObserver( Helper_Observer_Abstract $w );
}