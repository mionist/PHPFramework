<?php

class Manage_ORM_Controller_Export extends Manage_ORM_Controller_List{
	
	public function castEvent($eventID){
		if ( $eventID == StandardEventReciever::START_ADMIN ) $this->buildExport();
	}
	
	public function buildExport(){
		$formats = array(
			Export_Table::CSV => array('name'=>'В формате CSV','description'=>'разделители - точка с запятой'),
			Export_Table::JSON => array('name'=>'В формате JSON','description'=>'для работы с JavaScript'),
                        '^SQL' => array('name'=>'SQL','description'=>'для бекапа или последующего импорта в MySQL'),
		);
		
		$charsets = array(
			'utf-8' => 'UTF-8 (рекоммендуется)',
			'cp1251' => 'Windows 1251'
		);
		
                if ( Core::$in->_post['format'] == '^SQL' ){
                        $dump = Helper_Database_ExportTable::Export( $this->data['table'], NULL, Core::$in->_post['headers'] == 'y' );
                        Core::$out->sendContentType( Output::CONTENT_TYPE_PLAIN );
                        echo $dump;
                        exit;
                }elseif ( Core::$in->_post['format'] != '' ){
			$f = $this->getDataModel();
			$f->erasePagination();
			$table = Export_Table::Factory( Core::$in->_post['format'], $f );
			$table->setCharset( Core::$in->_post['charset'] );
			$table->detectColumnsFormat();
			$table->exportOutput();
			exit;
		}
		
		// Кнопки
		Core::$reg->data->buttons->push( array(
			'name'=>'Данные',
			'__'=>array(
				array( 'type'=>'relative_url','name'=>'К списку','icon'=>'fatcow/directory_listing.png','url'=>array('data',$this->name,'list')),
			)
		) );
		Core::$registry->data->replace( 'formats', $formats );
		Core::$registry->data->replace( 'charsets', $charsets );
		Core::$out->show('orm/export');
	}
	
}
