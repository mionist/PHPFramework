<?php

class Manage_Log implements StandardEventReciever {
	
	private $params;
	
	public function bindEvent($eventID, StandardEventReciever $object){}
	
	public function castEvent($eventID){
		if ( $eventID != StandardEventReciever::START_ADMIN ) return;
		if ( !Core::$auth->isAllowed( Auth_Abstract::ACCESS_MODERATOR ) ) throw new Exception_UserNotPrivileged();
		
		Core::$reg->meta->title->push('История');
		
		if ( Core::$in->_navigation[3] == 'view' ) $this->buildView();
		$this->buildList();
	}
	
	protected function buildList(){
		$f = new DataModel_Fetcher( Configuration::ADMIN_LOGGING_TABLE );
		$f->setShowChecking( FALSE );
		$f->setOrdering( array( array('id','desc') ) );
		$f->setLimit(50);
		$f->setPage( 1 );
		if ( Core::$in->_navigation[3] == 'page' && Core::$in->_navigation[4] > 1 )
			$f->setPage( (int) Core::$in->_navigation[4] );
		
		$filter = Core::$in->_get['f'];
		if ( $filter != '' ){
			if ( strpos( $filter, '*' ) === false ) $filter = "%{$filter}%";
			else $filter = str_replace( '*','%', $filter );
			$filter_sql = Core::getDatabase()->escape( $filter );
			$f->addFilterSpecial(
				new SQL_Helper_OR( array(
					new SQL_Helper_Common( 'value_old',  SQL_Helper_Common::O_LIKE_WO, $filter),
					new SQL_Helper_Common( 'value_new',  SQL_Helper_Common::O_LIKE_WO, $filter)
				) ) 
			);
		}
                $table = Core::$in->_get['t'];
                if ( $table != '' ){
                    $table_sql = Core::getDatabase()->escape( $table );
                    $f->addFilter('table', $table);
                }
			
                $list = array();
                foreach ( $f as $row ){
                    $list[] = new DataModel_Manage_LogEntry($row);
                }
		Core::$out->data['List'] = $list;
		
		
		
		// Навигация
		if ( $f->pages > 1 ){
			$navi_array = array(
			'name'=>'Навигация',
			'_'=>array(
				array( 'type'=>'relative_url', 'name'=>'Предыдущая', 'icon'=>'fam/arrow_left.png', 'url'=>array('user','log','page',$f->page-1), 'disabled'=>( $f->page == 1 ), 'params'=>array('f'=>Core::$in->_get['f'],'t'=>Core::$in->_get['t']) ),
				array( 'type'=>'relative_url', 'name'=>'Следующая', 'icon'=>'fam/arrow_right.png', 'url'=>array('user','log','page',$f->page+1), 'disabled'=>( $f->page == $f->pages ), 'params'=>array('f'=>Core::$in->_get['f'],'t'=>Core::$in->_get['t']) ),
				array('type'=>'text','name'=>'Страница', 'value' =>($f->page.' из '.$f->pages) ),
			)
			);
			Core::$reg->data->buttons->push( $navi_array );
		}		
		
		Core::$reg->data->buttons->push(array(
			'name'=>'фильтрация истории истории',
			'_'=>array(
                                array('type'=>'jsid','name'=>'таблица','value' =>'TableInputbox' ),
				array('type'=>'jsid','name'=>'поиск','value' =>'FilterInputbox' ),
			)
		));
		
		Core::$out->show('log_list');
	}
	
	protected function buildView(){
		$f = new DataModel_Fetcher( Configuration::ADMIN_LOGGING_TABLE );
		$f->setShowChecking( FALSE );
		$f->setOrdering( array( array('id','desc') ) );
		$f->addFilter( 'oid' , Core::$in->_navigation[4] );
		if ( $f->isEmpty() ) throw new StandardException("Log entry not found");
		
		if ( Core::$auth->isAllowed( Auth_Abstract::ACCESS_USERS_WRITE ) )Core::$reg->data->buttons->push( array(
			'name'=>'Данные',
			'__'=>array(
				array( 'type'=>'relative_url','name'=>'К списку','icon'=>'fatcow/directory_listing.png','url'=>array('user','log')),
			)
		) );				
		
		Core::$out->data['Entry'] = $f;
		Core::$out->show('log_view');
	}
	
}