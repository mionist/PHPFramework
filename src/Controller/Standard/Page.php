<?php

class Controller_Standard_Page implements StandardEventReciever{
	
	private $page;
	private $pages_configuration;
	
	public function bindEvent( $eventID, StandardEventReciever $callbackFunction ){
		// Do nothing
	}
	
	public function castEvent( $eventID ){
		if ( !in_array($eventID, array(
		StandardEventReciever::START_CLI,
		StandardEventReciever::START_HTML
		)) ) return;
		
		if ( !isset($this->pages_configuration) ) $this->pages_configuration = include Configuration::PATH_LOCAL.'config/Pages.php';
		list($bricks,$actions) = $this->getBricksAndActions();
		
		
		// Подвязываем bricks к выводу
		foreach ( $bricks as $name=>$row ) {
			$bricks[$name] = $row = DataModel_Brick::Factory( $name );
			if ( $row instanceof DataModel_Brick && ( $row->getBehaviour() === DataModel_Brick::KEY_VALUE_PAIR || $row->getBehaviour() === DataModel_Brick::KEY_VALUE_PAIR_SECTIONED ) )
				Core::$registry->lib->replace( $name, $row );
			else 
				Core::$registry->data->replace( $name, $row );
		}
		
		// Насыщаем META
		foreach ( $bricks as $row ){
			if ( !($row instanceof DataModel_Brick) ) continue;
			if ( !is_null( $row->getExposeFields('title') ) ) foreach ( $row->getExposeFields('title') as $field ) foreach ( $row as $datarow ){
				$x = $datarow[$field];
				if ( !empty($x) )
					Core::$out->meta->title->push( $x );
			}
			if ( !is_null( $row->getExposeFields('keywords') ) ) foreach ( $row->getExposeFields('keywords') as $field ) foreach ( $row as $datarow ){
				$x = $datarow[$field];
				if ( !empty($x) )
					Core::$out->meta->keywords->push( $x );
			}
			if ( !is_null( $row->getExposeFields('description') ) ) {
				$field = $row->getExposeFields('description');
				foreach ( $row as $datarow ){
					Core::$out->meta->description = $datarow[$field];
				}
			}
		}
		
		// Инициализируем notLazy
		foreach ( $bricks as $row ){
			if ( !$row->isNotLazy() ) continue;
			$row->isEmpty();
			if ( $row->isVolatile() && $row->isEmpty() ) return $this->error404();
		}
		
		// Отрабатываем события
		foreach ( $actions as $row ) $row->castEvent( $eventID );
		
		if ( !isset($this->page) ) return $this->error404();
		
		// Отображаем view
		if ( isset($this->page['view']) )
			Core::$out->show($this->page['view']);
		else{
			// Инициализируем бриксы
			foreach ( $bricks as $row ) $row->isEmpty();
			Core::$out->dump();
		}
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
						// Поддержка множественных проверок
						$all_ok = FALSE;
						for ( $i=1; $i < 20; $i+=2 ){
							if ( !isset($row['detect'][$i],$row['detect'][$i+1]) ) break;
							
							if ( strpos($row['detect'][$i+1], ',') !== FALSE ){
								// Проверка по массиву
								if ( in_array( Core::$in->_navigation[$row['detect'][$i]], explode(',',$row['detect'][$i+1]) )){
									$all_ok = TRUE;
								} else {
									$all_ok = FALSE;
									break;
								}
							} elseif ( $row['detect'][$i] > 0 ) {
								// Проверка по строке URL
								if ( Core::$in->_navigation[$row['detect'][$i]] == $row['detect'][$i+1] ){
									$all_ok = TRUE;
								} else {
									$all_ok = FALSE;
									break;
								}
							} else {
								// Проверка по субдомену
								if ( Core::$in->_domain[-$row['detect'][$i]] == $row['detect'][$i+1] ){
									$all_ok = TRUE;
								} else {
									$all_ok = FALSE;
									break;
								}
							}
						}
						if ( $all_ok ){
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
		if ( isset( $this->page['event_handler'] ) ){
			if ( !is_array($this->page['event_handler']) ) $this->page['event_handler'] = array($this->page['event_handler']);
			foreach ( $this->page['event_handler'] as $row ){
				$row = new $row();
				$row->castEvent( StandardEventReciever::INIT );
				$act_list[] = $row; 
			}
		}
		
		$obj_list = array();
		foreach ( $answer as $row ){
			$obj_list[$row] = TRUE;
		}
		return array($obj_list,$act_list);
	}
	
}