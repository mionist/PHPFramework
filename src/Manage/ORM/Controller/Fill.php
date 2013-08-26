<?php

class Manage_ORM_Controller_Fill extends Manage_ORM_Controller_List{
	public function castEvent( $eventID ){
		if ( $eventID == StandardEventReciever::START_ADMIN ) $this->buildFill();
	}
	
	protected function buildFill(){
		$this->prepareEverything();
		
		$bundle = Core::getSaveBundle();
		if ( Core::$in->_post['values'] != '' ){
			$fields = array(); $nullarray = array();
			$used = array();
			foreach ( Core::$registry->data->ORMStructure as $row ){
				if ( $row instanceof Manage_ORM_FileField ) continue;
				$fields[] = $row->name;
				if ( Core::$in->_post['enable_field_'.$row->name] == 'y' ) $used[] = $row->name;
				if ( Core::$in->_post['enable_field_'.$row->name] != 'y' && Core::$in->_post['default_field_'.$row->name] != '' ) $nullarray[] = Core::$in->_post['default_field_'.$row->name];
				else $nullarray[] = 'NULL';
			}
			
			foreach ( explode("\n",Core::$in->_post['values']) as $row ){
				$row = trim($row);
				if ( $row == '' ) continue;
				$save = array_combine( $fields , $nullarray);
				
				$row = explode(';', $row);
				for ( $i=0; $i < min( count($row), count($used) ); $i++ )
					$save[ $used[$i] ] = trim( $row[$i] );
				
				// Убираем NULL-ы
				foreach ( $save as $k=>$v ){
					if ( $v == 'NULL' ) unset($save[$k]);
				}
				
				$bundle->insertInto( $this->data['table'], $save);	
			}
			
		}
		// Кнопки
		Core::$reg->data->buttons->push( array(
			'name'=>'Данные',
			'__'=>array(
				array( 'type'=>'relative_url','name'=>'К списку','icon'=>'fatcow/directory_listing.png','url'=>array('data',$this->name,'list')),
			)
		) );
		Core::$out->show('orm/fill');
	}
}