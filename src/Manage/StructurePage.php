<?php

class Manage_StructurePage implements StandardEventReciever {
	
	public function bindEvent($eventID, StandardEventReciever $object){}
	
	public function castEvent($eventID){
		if ( $eventID == StandardEventReciever::START_HTML ) $this->buildPage();
		if ( $eventID == StandardEventReciever::START_ADMIN ) $this->buildPage();
                if ( $eventID == StandardEventReciever::START_ADMIN_AJAX ) $this->buildAjaxPage();
	}
	
        protected function buildAjaxPage(){
                // Читаем все страницы
                $pages = array();
                foreach ( include Configuration::PATH_LOCAL.'config/Pages.php' as $row ){
                        $row['call_type'] = 'html';
                        $pages[] = $row;
                }
                if ( file_exists( 'config/PagesAJAX.php' ) )foreach ( include Configuration::PATH_LOCAL.'config/PagesAJAX.php' as $row ){
                        $row['call_type'] = 'ajax';
                        $pages[] = $row;
                }

                // Читаем все бриксы
                $bricks = $this->loadBricksInformation();
            
                die( JSON::encode(array('pages'=>$pages,'bricks'=>$bricks)) );
        }
        
	protected function buildPage(){
		Core::$reg->meta->title->push('Структура сайта');
		// Читаем все страницы
		$pages = array();
		foreach ( include Configuration::PATH_LOCAL.'config/Pages.php' as $row ){
			$row['call_type'] = 'html';
			$pages[] = $row;
		}
		if ( file_exists( 'config/PagesAJAX.php' ) )foreach ( include Configuration::PATH_LOCAL.'config/PagesAJAX.php' as $row ){
			$row['call_type'] = 'ajax';
			$pages[] = $row;
		}
		
		// Читаем все бриксы
		$bricks = $this->loadBricksInformation();

		Core::$reg->data['Bricks'] = $bricks;
		Core::$reg->data['Pages'] = $pages;
		Core::$out->show('structure');
	}

	protected function loadBricksInformation(){
                $bricks = array();
                foreach ( Core::$fs->listFilenames( Standard_CoreFS::TYPE_BRICKS ) as $row ){
                        $name = substr( $row, 0, strlen($row)-4);
                        $data = Core::$fs->load( Standard_CoreFS::TYPE_BRICKS , $row);
                        $bricks[$name] = $data;
                }
                return $bricks;
	} 	
	
}