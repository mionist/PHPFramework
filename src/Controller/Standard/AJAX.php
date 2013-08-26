<?php

class Controller_Standard_AJAX implements StandardEventReciever{
	
	private $page;
	private $pages_configuration;
	
	public function bindEvent( $eventID, StandardEventReciever $callbackFunction ){}
	
	public function castEvent( $eventID ){
		if ( $eventID != StandardEventReciever::START_AJAX ) return;
		
		if ( !isset($this->pages_configuration) ) $this->pages_configuration = include Configuration::PATH_LOCAL.'config/PagesAJAX.php';
		list($bricks,$actions) = $this->getBricksAndActions();
		if ( !isset($this->page) ) return $this->error404();
		
		
		// Подвязываем bricks к выводу
		foreach ( $bricks as $name=>$row ) {
			if ( $row instanceof DataModel_Brick  && ( $row->getBehaviour() === DataModel_Brick::KEY_VALUE_PAIR || $row->getBehaviour() === DataModel_Brick::KEY_VALUE_PAIR_SECTIONED ) )
				Core::$registry->lib->replace( $name, $row );
			else 
				Core::$registry->data->replace( $name, $row );
		}
		
		// Инициализируем все
		foreach ( $bricks as $row ){
			$row->isEmpty();
			if ( $row->isVolatile() && $row->isEmpty() ) return $this->error404();
		}
		
		// Отрабатываем события
		$answer = array();
		try{
			foreach ( $actions as $row ) $answer = $row->castEvent( $eventID );
		} catch ( Exception $e ){
		    new ExceptionRenderer( $e );
		    exit;
		}
		
		die( JSON::encode(
			array('status'=>'ok','data'=>$answer)
		) );
	}
	
	protected function error404(){
		throw new Exception_404();
	}
	
	protected function getBricksAndActions(){
		$answer = array();
		// Определяем страницу
		foreach ( $this->pages_configuration as $row ){
			$use = false; $break = false;
			if ( $row['name'] == '*' ){
				$use = true;
				$break = false;
			} else {
				if ( !isset($row['detect']) || !is_array( $row['detect'] ) ) break;
				switch ( $row['detect'][0] ){
					case 'index':
						if ( Core::$in->_navigation[1] == '' ){
							// Found
							$use = true;
							$break = true;
							$this->page = $row;
						}
						break;
					case 'urlpos':
						if ( Core::$in->_navigation[1] == $row['detect'][2] ){
							// Found!
							$use = true;
							$break = true;
							$this->page = $row;
						}
						break;
					case 'regex':
						if ( preg_match( $row['detect'][1], $_SERVER['REQUEST_URI'] ) ){
							// Found
							$use = true;
							$break = true;
							$this->page = $row;
						}
						break;
				}
			}
			
			if ( $use && isset($row['bricks']) && is_array($row['bricks']) ){
				// Добавляем бриксы
				$answer = array_merge( $answer, $row['bricks'] );
			}
			
			if ( $break ) break;
		}
		
		$act_list = array();
		if ( isset( $this->page, $this->page['event_handler'] ) ){
			if ( !is_array($this->page['event_handler']) ) $this->page['event_handler'] = array($this->page['event_handler']);
			foreach ( $this->page['event_handler'] as $row ){
				$row = new $row();
				$row->castEvent( StandardEventReciever::INIT );
				$act_list[] = $row; 
			}
		}
		
		$obj_list = array();
		foreach ( $answer as $row ){
			$obj_list[$row] = DataModel_Brick::Factory( $row );
		}
		return array($obj_list,$act_list);
	}
	
}