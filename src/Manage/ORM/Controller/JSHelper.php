<?php

class Manage_ORM_Controller_JSHelper implements StandardEventReciever{

	private $structure;
	public function setStructure( $x ){ $this->structure = $x; }

	private $brick;
	public function setBrick( $b ){ $this->brick = $b; }

	public function bindEvent($eventID, StandardEventReciever $object){ /* ignore */ }
	public function castEvent( $eventID ){
		if ( $eventID != StandardEventReciever::START_ADMIN_AJAX ) return;

		if ( Core::$in->_post['js_helper'] == 'date' ) $this->HelperDate();
		if ( Core::$in->_post['js_helper'] == 'toggle' ) $this->HelperToggle();

		$this->makeAnswer();
	}

	private function makeAnswer( $array = NULL ){
		if ( !isset($array) ) $array = array();
		die(JSON::encode( array_merge(array('status'=>'ok','oid'=>Core::$in->_post['oid'],'for'=>Core::$in->_post['for']), $array) ));
	}

	private function HelperDate(){
		switch ( Core::$in->_post['mode'] ){
			case 'today':
				$this->makeAnswer( array('attr'=>'value','value'=>date('Y-m-d')) );
			case 'yesterday':
				$this->makeAnswer( array('attr'=>'value','value'=>date('Y-m-d',time()-86400)) );
			case 'tomorrow':
				$this->makeAnswer( array('attr'=>'value','value'=>date('Y-m-d',time()+86400)) );
			case 'last':
				Core::getDatabase()->getBuilder('plain')
				->select( $this->brick['table'] , 'max(`'.Core::$in->_post['for'].'`) as "value" FROM ??')
				->exec();
				$date = Core::getDatabase()->result->getValue( 'value' );
				$date = strtotime($date);
				$date+=86400;
				$date = date('Y-m-d',$date);
				$this->makeAnswer( array('attr'=>'value','value'=>$date ) );
		}
	}

	private function HelperToggle(){
		// Читаем текущее значени
		Core::getDatabase()->getBuilder('plain')
		->select( $this->brick['table'] , '`'.Core::$in->_post['field'].'` as "value" FROM ?? WHERE `id`= ? ', array((int) Core::$in->_post['eid']))
		->exec();
		$old = Core::getDatabase()->result->getValue( 'value' );
                if ( in_array($old, array('y','n')) ) $new = ( $old == 'y' ? 'n' : 'y' );
		else $new = ( $old == 1 ? '0' : '1' );

		$bundle = Core::getSaveBundle();
		$bundle->replaceIn( Core::$in->_post['eid'] , $this->brick['table'], array(Core::$in->_post['field']=>$new));

		$this->makeAnswer( array('new'=> $new, 'old'=> $old, 'attr'=>'src', 'value'=>''.(new Renderable_Manage_Proxy( 'img/icons/fam/bullet_'.( $new == 1 || $new == 'y' ? 'green' : 'black' ).'.png')) ));
	}
}