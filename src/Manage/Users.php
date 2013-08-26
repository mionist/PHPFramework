<?php

class Manage_Users implements StandardEventReciever {
	
	private $params;
	
	public function bindEvent($eventID, StandardEventReciever $object){}
	
	public function castEvent($eventID){
		if ( $eventID != StandardEventReciever::START_ADMIN ) return;
		if ( !Core::$auth->isAllowed( Auth_Abstract::ACCESS_USERS_READ ) ) throw new Exception_UserNotPrivileged();
		$this->params = Core::$auth->getParams();
		
		Core::$reg->meta->title->push('Пользователи');
		
		if ( is_numeric( Core::$in->_navigation[3] ) || Core::$in->_navigation[3] == 'add' ) $this->buildForm();
		$this->buildList();
	}
	
	protected function buildList(){
		$f = new DataModel_Fetcher( $this->params->table );
		$f->setShowChecking( FALSE );
		$f->setLimit( 50 );
		$f->setPage( 1 );
		if ( Core::$in->_navigation[3] == 'page' && Core::$in->_navigation[4] > 1 )
			$f->setPage( (int) Core::$in->_navigation[4] );
		
		$filter = Core::$in->_get['f'];
		if ( $filter != '' ){
			if ( strpos( $filter, '*' ) === false ) $filter = "%{$filter}%";
			else $filter = str_replace( '*','%', $filter );
			$filter_sql = Core::getDatabase()->escape( $filter );
			$f->addFilterSpecial( new SQL_Helper_Common( $this->params->field_login,  SQL_Helper_Common::O_LIKE_WO, $filter) );
		}
			
			
		$f->setOrdering( array( array($this->params->field_login,'ASC') ) );
		$f->isEmpty();
		
		Core::$out->data['Params'] = $this->params;
		Core::$out->data['List'] = $f;
		
		// Навигация
		if ( $f->pages > 1 ){
			$navi_array = array(
			'name'=>'Навигация',
			'_'=>array(
				array( 'type'=>'relative_url', 'name'=>'Предыдущая', 'icon'=>'fam/arrow_left.png', 'url'=>array('user','users','page',$f->page-1), 'disabled'=>( $f->page == 1 ), 'params'=>array('f'=>Core::$in->_get['f']) ),
				array( 'type'=>'relative_url', 'name'=>'Следующая', 'icon'=>'fam/arrow_right.png', 'url'=>array('user','users','page',$f->page+1), 'disabled'=>( $f->page == $f->pages ), 'params'=>array('f'=>Core::$in->_get['f']) ),
				array('type'=>'text','name'=>'Страница', 'value' =>($f->page.' из '.$f->pages) ),
			)
			);
			Core::$reg->data->buttons->push( $navi_array );
		}		
		
		Core::$reg->data->buttons->push(array(
			'name'=>'Фильтр',
			'_'=>array(
				array('type'=>'caption','value' =>'фильтрация' ),
				array('type'=>'jsid','value' =>'FilterInputbox' ),
			)
		));
		
		if ( Core::$auth->isAllowed( Auth_Abstract::ACCESS_USERS_WRITE ) ) Core::$reg->data->buttons->push( array(
			'name'=>'Данные',
			'__'=>array(
				array( 'type'=>'relative_url','name'=>'Добавить','icon'=>'fatcow/document_import.png','url'=>array('user','users','add'))
			)
		) );
		

		
		
		Core::$out->show('users_list');
	}
	
	protected function buildForm(){
		$defaults = array(
			$this->params->field_login => '',
			$this->params->field_banned => 0,
			$this->params->field_admin_rights => '',
		);
		if ( Core::$in->_navigation[3] != 'add' ){
			$f = new DataModel_Fetcher( $this->params->table );
			$f->setShowChecking( FALSE );
			$f->setLimit(1);
			$f->addFilter( 'id' , Core::$in->_navigation[3]);
			if ( count($f) == 0 ) throw new StandardException("User not found");
			$defaults = $f->get(0);
		}
		
		if ( isset(Core::$in->_post['login']) ){
			// сохраняем данные
			$save = array();
			$save[$this->params->field_login] = Core::$in->_post->getString($this->params->field_login);
			$save[$this->params->field_banned] = Core::$in->_post->getInt( $this->params->field_banned );
			$save[$this->params->field_admin_rights] = Core::$in->_post->getString($this->params->field_admin_rights);
			if ( Core::$in->_post['password_request'] == 'reset' )
				$save['is_reset_needed'] = 1;
			if ( Core::$in->_post['password_request'] == 'manual' && Core::$in->_post->getString('password') != '' ){
				$salt = time()/mt_rand(10, 1000) + mt_rand(1, 1000)*mt_rand(1, 1000);
				$salt = dechex( $salt );
				$hash = Core::$auth->passwordHash( Core::$in->_post->getString('password'), $salt );
				$save['salt'] = $salt;
				$save['password'] = $hash;
			}
			
			if ( Core::$in->_post->getString('name') != '' )
				$save['name'] = Core::$in->_post->getString('name');
			
			$id = Core::$in->_navigation[3];
			if ( Core::$in->_navigation[3] == 'add' ){
				$save['time_create'] = date('Y-m-d H:i:s');
				Core::getDatabase()->getBuilder('plain')
				->insert( $this->params->table , $save)
				->exec();
				$id = Core::getDatabase()->result->insert_id;
			} else {
				Core::getDatabase()->getBuilder('plain')
				->update( $this->params->table , $save, $id)
				->exec();
			}
			
			Core::$out->redirect( new Renderable_URL( array('user','users',$id) ) );
			
		}
		
		if ( Core::$auth->isAllowed( Auth_Abstract::ACCESS_USERS_WRITE ) )Core::$reg->data->buttons->push( array(
			'name'=>'Данные',
			'__'=>array(
				array( 'type'=>'relative_url','name'=>'К списку','icon'=>'fatcow/directory_listing.png','url'=>array('user','users')),
				array( 'type'=>'javascript','name'=>'Сохранить','icon'=>'fatcow/save_as.png','id'=>'SaveButton'),
			)
		) );		
		Core::$out->data['Params'] = $this->params;
		Core::$reg->data['Defaults'] = $defaults;
		Core::$out->show('users_form');
	}
}