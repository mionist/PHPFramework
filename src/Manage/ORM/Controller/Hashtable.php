<?php

class Manage_ORM_Controller_Hashtable implements StandardEventReciever{
	
	protected $name;
	protected $data;
	protected $structure;
	
	public function __construct( $brickname, $brickdata ){
		$this->name = $brickname;
		$this->data = $brickdata;
	}
	
	public function bindEvent($eventID, StandardEventReciever $object){ /* ignore */ }
	public function castEvent( $eventID ){
		if ( $eventID == StandardEventReciever::START_HTML ) $this->buildList();
		if ( $eventID == StandardEventReciever::START_ADMIN ) $this->buildList();
		if ( $eventID == StandardEventReciever::START_ADMIN_AJAX && Core::$in->_navigation[3] == 'savevalue' ) $this->saveValue();
	}
	
	protected function prepareEverything(){
		$this->structure = new Manage_ORM_Structure( $this->data );
		Core::$out->data['ORMBrick'] = $this->name;
		Core::$out->data['ORMTable'] = $this->data['table'];
		Core::$out->data['ORMStructure'] = $this->structure->getFields();
		
		// Читаем статистику
		Core::getDatabase()->getBuilder('plain')
		->status($this->data['table'])
		->exec();
		
		// Вносим в тулбар
		$toolbar = array(
			'name'=>'Сводная',
			'_'=>array(
				array('type'=>'text','name'=>'Таблица','value'=>$this->data['table']),
				array('type'=>'text','name'=>'Строк','value'=>Core::getDatabase()->result->getValue('Rows')),
				array('type'=>'text','name'=>'Размер','value'=>number_format( Core::getDatabase()->result->getValue('Data_length')/1000000,2,'.','').' Мб')
			)
		);
		Core::$reg->data->buttons->push( $toolbar );
	}	
	
	private function saveValue(){
		if ( !Core::$auth->isAllowed( Auth_Abstract::ACCESS_DATA_MODELS_WRITE ) ) throw new Exception_UserNotPrivileged();
		
		$this->prepareEverything( $this->data );
		$beh = $this->data['behaviour'];
		
		$id = (int) Core::$in->_navigation[4];
		
		$key = $beh[2];
		if ( isset(Core::$in->_post['lang']) && Core::$in->_post['lang'] != '' ) $key .= '_'.Core::$in->_post['lang'];
		
		$save = array( $key => Core::$in->_post['value'] );
		
		// Делаем обновление
		$bundle = Core::getSaveBundle();
		$bundle->replaceIn($id, $this->data['table'], $save);
		
		// Если есть кеш - ревалидируем
		if ( Configuration::CACHE_ENABLED && isset($this->data['cache']) && is_array($this->data['cache']) ){
			$cache_entries = array();
			if ( strpos($this->data['cache'][0], '$') === FALSE ){
				$cache_entries[] = $this->data['cache'][0];
			} elseif ( Configuration::I18N ) {
				// Если единственные замены - языковые
				$test = str_replace('$L', '', $this->data['cache'][0]);
				if ( strpos( $test , '$') === FALSE ){
					foreach ( Core::getI18NLanguages() as $row )
						$cache_entries[] = str_replace('$L', $row, $this->data['cache'][0]);
				}
			}
			foreach ( $cache_entries as $row ) Cache::Erase( $row );
		}
		exit;
	}
	
	protected function buildList(){
		$this->prepareEverything();
		// Читаем данные 
		$f = new DataModel_Fetcher( $this->data['table'] );
		$f->setShowChecking(false);
		
		$beh = $this->data['behaviour'];
		
		$sortArray = array($beh[1],'asc');
		
		// Сортируем
		if ( isset($beh[4]) )
			$sortArray = array_merge(array($beh[4],'asc'), $sortArray);
		if ( isset($beh[6]) )
			$sortArray = array_merge(array($beh[6],'asc'), $sortArray);
			
			
		$f->setOrdering(array( $sortArray ));
		
		// Создаём данные
		$data = array();
		foreach ( $f as $row ){
			$tmp = array(
				'section'=>'',
				'key'=>$row['id'],
				'name'=>$row[$beh[1]],
				'value'=>array(),
				'type'=>'string',
				'description'=>NULL,
			);
			
			// Заполняем value
			if ( isset( $this->data['fields_i18n'] ) && in_array( $beh[2] , $this->data['fields_i18n']) ){
				foreach ( Core::getI18NLanguages() as $lang ){
					$tmp['value'][ $lang ] = $row[$beh[2].'_'.$lang];
				}
			} else $tmp['value'] = array( '' => $row[$beh[2]] );
			
			
			if ( isset($beh[3]) && !empty($row[$beh[3]]) ) $tmp['type'] = $row[$beh[3]];
			if ( isset($beh[4]) && !empty($row[$beh[4]]) ) $tmp['name'] = $row[$beh[4]]; 
			if ( isset($beh[6]) && !empty($row[$beh[6]]) ) $tmp['section'] = $row[$beh[6]];
			
			$data[] = $tmp;
		}
		
		Core::$reg->data->buttons->push(array(
			'name'=>'Фильтр',
			'_'=>array(
				array('type'=>'caption','value' =>'фильтрация' ),
				array('type'=>'jsid','value' =>'FilterInputbox' ),
			)
		));
		
		Core::$reg->data->buttons->push( array(
			'name'=>'Данные',
			'__'=>array(
				array( 'type'=>'relative_url','name'=>'Экспорт','icon'=>'fatcow/table_export.png','url'=>array('data',$this->name,'export'), 'disabled'=>!Core::$auth->isAllowed( Auth_Abstract::ACCESS_DATA_MODELS_EXPORT ))
			)
		) );		
		
		Core::$out->data['ORMData'] = $data;
		Core::$out->show('orm/hashtable');
	}
	
}