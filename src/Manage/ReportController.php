<?php

class Manage_ReportController implements StandardEventReciever{
    
    /**
     *
     * @var Report_Abstract 
     */
    private $report;
    private $table;
    
    public function bindEvent($eventID, StandardEventReciever $object) {}
    public function bindReport( Report_Abstract $r ){ $this->report = $r; }
    public function bindTable( $tableName ){ $this->table = $tableName; }
    
    public function castEvent($eventID) {
        if ( $eventID == StandardEventReciever::START_ADMIN ) $this->startHTML();
	if ( $eventID == StandardEventReciever::START_ADMIN_AJAX ) $this->startHTML( TRUE );
    }
    
    private function AJAXReply( $status, $message ){
		if ( $status === 'error' )
			die( JSON::encode( array('status'=>'error' , 'error' => $message ) ) );
		
		die( JSON::encode( array('status'=>$status , 'data' => $message )));
    }
    
    private function startHTML( $ajax = FALSE ){
	if ( Core::$in->_navigation[2] == 'temporary_reports' ){
	    Core::getDatabase()->getBuilder('plain')
		->select( $this->table, '`id`,`time_modify`,`title` FROM ?? WHERE `saved`="0" AND `user_created`=? ORDER BY `time_modify` DESC', array( Core::$auth->getUID() ) )
		->exec();
	    if ( $ajax ) $this->AJAXReply('ok', Core::getDatabase()->result->getData());
	    Core::$registry->data['list'] = Core::getDatabase()->result->getData();
	    Core::$out->show('report.recent.list.php');
	}elseif ( Core::$in->_navigation[2] == 'saved_reports' ){
	    Core::getDatabase()->getBuilder('plain')
		->select( $this->table, '`id`,`time_modify`,`title` FROM ?? WHERE `saved`="1" AND `user_created`=? ORDER BY `time_modify` DESC', array( Core::$auth->getUID() ) )
		->exec();
	    if ( $ajax ) $this->AJAXReply('ok', Core::getDatabase()->result->getData());
	    Core::$registry->data['list'] = Core::getDatabase()->result->getData();
	    Core::$out->show('report.saved.list.php');
	}else if ( is_numeric(Core::$in->_navigation[2]) ) return $this->loadReport ( $ajax );
        else {
	    if ( $ajax ) $this->AJAXReply ('error', 'unimplemented');
            // Генерируем отчёт
            if ( Core::$in->_post['generate'] != 'yes' ){
                // форма
                Core::$registry->data->replace( 'form', $this->report->getForm());
                Core::$out->show('report.form');
            } else {
		// генерируем отчёт
		$this->report->doFill( Core::$in->_post );
		if ( $this->report->isInTestMode() || !isset( $this->table ) ){
		    Core::$registry->data->replace( 'report_description', 'Отчёт находится на стадии разработки' );
		    Core::$registry->data->replace( 'report_saved', FALSE );
		    Core::$registry->data->replace( 'report', $this->report);
		    Core::$out->show('report.show');
		}
		$toSave = $this->report->save();
                $toSave['time_create'] = date('Y-m-d H:i:s');
		$toSave['time_modify'] = date('Y-m-d H:i:s');
                $toSave['user_created'] = Core::$auth->getUID();
		$toSave['name'] = get_class( $this->report );
		$toSave['title'] = $this->report->getTitle();
                
                Core::getDatabase()->getBuilder('plain')
                        ->insert( $this->table, $toSave )
                        ->exec();
                $id = Core::getDatabase()->result->insert_id;
                Core::getDatabase()->getBuilder('plain')
                        ->delete( $this->table, '`saved`=0 AND `time_modify` < "'.date('Y-m-d H:i:s',time()-100000).'"')
                        ->exec();
                
                Core::$out->redirect( new Renderable_URL( array('reports',$id) ) );
	    }
        }
    }
    
    private function loadReport( $ajax = FALSE ){
	$id = (int) Core::$in->_navigation[2];
	if ( !isset( $this->table ) ) throw new StandardException("Reports database not configured");
	Core::getDatabase()->getBuilder('plain')
		->select( $this->table, '* FROM ?? WHERE `id`=? LIMIT 1', array($id) )
		->exec();
	if ( Core::getDatabase()->result->count() == 0 ) throw new StandardException("Report not found");
	if ( Core::getDatabase()->result->getValue('user_created') != Core::$auth->getUID() && Core::getDatabase()->result->getValue('user_assigned') != Core::$auth->getUID() ) throw new Exception_UserNotPrivileged("This is not yours report");
	
	$time_mod = Core::getDatabase()->result->getValue('time_modify');
	$gen_time = Core::getDatabase()->result->getValue('stats_generation_time');
	$saved = (Core::getDatabase()->result->getValue('saved') == 1);
	
	$class = Core::getDatabase()->result->getRow(); $class = $class['name'];
	$this->report = new $class();
	$this->report->load( Core::getDatabase()->result->getRow() );
	
	
	Core::$reg->data->buttons->push( array(
		'name'=>'Отчёт',
		'__'=>array(
			( $saved ? 
			    array( 'type'=>'javascript','name'=>'Обновить','icon'=>'fatcow/arrow_refresh.png','id'=>'SavedReportUpdater') : 
			    array( 'type'=>'relative_url','name'=>'Обновить','icon'=>'fatcow/arrow_refresh.png','url'=>array('reports',Core::$in->_navigation[2],'refresh')) 
			),
			array( 'type'=>'relative_url','name'=>'Сохранить','icon'=>'fatcow/save_as.png','url'=>array('reports',Core::$in->_navigation[2],'save'),'disabled'=>$saved),
                        array( 'type'=>'relative_url','name'=>'Экспорт CSV','icon'=>'fatcow/page_white_excel.png','url'=>array('reports',Core::$in->_navigation[2],'csv')),
			array( 'type'=>'relative_url','name'=>'Печать','icon'=>'fatcow/printer.png','url'=>array('reports',Core::$in->_navigation[2],'print')),
		)
	    
	) );

	Core::$reg->data->buttons->push( array(
	    'name'=>'Данные',
	    '_'=>array(
		    array( 'type'=>'jsid', 'name'=>'Относительные значения', 'value'=>'PH_rel_checker' ),
	    )
	));
	
	Core::$registry->data->replace( 'report_description', 'Отчёт актуализирован '.date('d.m.y в H:i',  strtotime( $time_mod )).' за '.$gen_time.' сек.' );
	Core::$registry->data->replace( 'report_saved', $saved );
	
	if ( Core::$in->_navigation[3] == 'save' ){
	    // Сохранение отчёта
	    Core::getDatabase()->getBuilder('plain')
		->update( $this->table, array('saved'=>'1'), $id )
		->exec();
	    Core::$out->redirect( new Renderable_URL( array('reports',$id) ) );
	}
	
	if ( Core::$in->_navigation[3] != 'refresh' ){
	    Core::$reg->data['report'] = $this->report;
	    if ( $ajax )
		Core::$out->show('report.show.json');
	    else if ( Core::$in->_navigation[3] == 'csv' )
                Core::$out->show('report.show.csv');
            else
		Core::$out->show('report.show');
	}
	
	// Требуется рефреш отчёта
	$this->report->erase();
	$this->report->doFill( NULL );
	$toSave = $this->report->save();
	$toSave['time_modify'] = date('Y-m-d H:i:s');

	Core::getDatabase()->getBuilder('plain')
		->update( $this->table, $toSave, $id )
		->exec();
	Core::getDatabase()->getBuilder('plain')
		->delete( $this->table, '`saved`=0 AND `time_modify` < "'.date('Y-m-d H:i:s',time()-100000).'"')
		->exec();

	Core::$out->redirect( new Renderable_URL( array('reports',$id) ) );
	
    }
    
}