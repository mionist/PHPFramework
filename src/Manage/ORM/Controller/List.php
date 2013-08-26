<?php

class Manage_ORM_Controller_List implements StandardEventReciever{

	protected $name;
	protected $data;
	protected $structure;
	protected $native_controller = TRUE;
        protected $list_amount = 50;

	public function __construct( $brickname, $brickdata ){
		$this->name = $brickname;
		$this->data = $brickdata;
                $this->provideURLs();
	}

	public function bindEvent($eventID, StandardEventReciever $object){ /* ignore */ }
	public function castEvent( $eventID ){
		if ( $eventID == StandardEventReciever::START_HTML ) $this->buildList();
		if ( $eventID == StandardEventReciever::START_ADMIN ) $this->buildList();
		if ( $eventID == StandardEventReciever::START_ADMIN_AJAX && isset( Core::$in->_post['js_helper'] ) ){
			$this->prepareEverything();
			$obj = new Manage_ORM_Controller_JSHelper();
			$obj->setStructure( $this->structure );
			$obj->setBrick( $this->data );
			$obj->castEvent($eventID);
		} else if ( $eventID == StandardEventReciever::START_ADMIN_AJAX ){
                        $this->buildJSList();
                }
	}

	protected function prepareEverything(){
		$this->structure = new Manage_ORM_Structure( $this->data );
		Core::$out->data['ORMBrick'] = $this->name;
		Core::$out->data['ORMTable'] = $this->data['table'];
		Core::$out->data['ORMStructure'] = $this->structure->getFields();

                // Лимитируем и сортируем поля
                if ( isset($this->data['admin_list_fields']) ){
                    $replacement = array();
                    foreach ( $this->data['admin_list_fields'] as $fname ){
                        foreach ( $this->structure->getFields() as $field ){
                            if ( $field->name == $fname ) $replacement[] = $field;
                        }
                    }
                    Core::$out->data['ORMStructure'] = $replacement;
                }
                
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

		Core::$reg->meta->title->push( !isset($this->data['name']) ? $this->name : $this->data['name'] );
	}

	protected function initDataModel(){return new DataModel_Fetcher( $this->data['table'] );}

        protected function provideURLs(){
            Core::$out->data['url_offset'] = array('data',$this->name);
        }

	protected function getDataModel(){
		$f = $this->initDataModel();
		$f->setLimit( $this->list_amount );
		$f->setPage( 1 );

                $navigation_offset = count( Core::$out->data['url_offset'] ) + 1;
		if ( Core::$in->_navigation[$navigation_offset+1] == 'page' && Core::$in->_navigation[$navigation_offset+2] > 1 )
			$f->setPage( (int) Core::$in->_navigation[$navigation_offset+2] );

		$ordering = array();
		if ( isset( $this->data['admin_force_order'] ) ) $ordering = $this->data['admin_force_order'];

		$ordering_field = NULL;
		if ( isset(Core::$in->_get['of'],Core::$in->_get['oo']) && Core::$in->_get['of'] != '' ){
			// Проверка наличия
			$ordering_field = $this->structure->findFieldByName( Core::$in->_get['of'] );
			if ( is_null($ordering_field) ) throw new StandardException("Field ".Core::$in->_get['of'].' not found');
			if ( !$ordering_field->isOrderAble() ) throw new StandardException("Field ".Core::$in->_get['of'].' dont support ordering');
			$ordering[] = array(Core::$in->_get['of'],Core::$in->_get['oo']);
		} else if ( !$f->hasOrderring() ) {
			$ordering[] = array('id','desc');
		}
                if ( count($ordering) > 0 )
                    $f->setOrdering( $ordering );
		$f->setShowChecking(false);

		// Добавляем фильтры
		$filter = trim(Core::$in->_get['f']);
                $filterNumeric = $filter;
		if ( $filter != '' ){
			$ors = array();
			if ( strpos( $filter, '*' ) === false ) $filter = "%{$filter}%";
			else $filter = str_replace( '*','%', $filter );
			$filter_sql = Core::getDatabase()->escape( $filter );
			foreach ( $this->structure->getFields() as $row ){
				if ( !$row->isSearchAble() ) continue;
				if ( strpos( $row->name, '.') !== FALSE ) continue;
                                if ( $row->isNumeric() ){
                                    if ( !is_numeric( $filterNumeric ) || $filterNumeric == 0 ) continue;
                                    $ors[] = array( $row->name , $filterNumeric );
                                    continue;
                                }
				if ( $row instanceof Manage_ORM_ExternalField ){
					$ext = new DataModel_Fetcher( $row->getTable() );
					$ext->setShowChecking(FALSE);
					$ext->setFields( array('id', $row->getPrimaryField()) );
					$ext->addFilterSpecial( new SQL_Helper_Common( $row->getPrimaryField(), SQL_Helper_Common::O_LIKE_WO, $filter_sql ) );
					$ext->setLimit( 100 );
					$ext->isEmpty();
					if ( count($ext) < 100 && count($ext) > 0 ){
						$arr = $ext->extractField( 'id' );
						$ors[] = new SQL_Helper_Common( $row->name, SQL_Helper_Common::O_IN, $arr );
					}
				}else $ors[] = new SQL_Helper_Common( $row->name, SQL_Helper_Common::O_LIKE_WO, $filter_sql );
			}
			if ( count($ors) > 0 )
				$f->addFilterSpecial( new SQL_Helper_OR( $ors ) );
		}

		// Подключаем внешние поля
		if ( isset($this->data['external']) ) foreach ( $this->data['external'] as $k=>$v ) {
			$xf = new DataModel_Fetcher( $v[0] );
			$xf->setShowChecking( FALSE );
			$xf->setFields( $v[1] );
			$f->joinXto1( $k, $xf);
		}
		return $f;
	}

        protected function buildJSList(){
		$this->prepareEverything();

		// Читаем данные
		$f = $this->getDataModel();
		// Смена лимита
		if ( isset($this->data['admin_limit']) && $this->data['admin_limit'] > 0 ){
			$f->setLimit( $this->data['admin_limit'] );
		}
		$f->isEmpty();

                $answer = array();
                $answer['list'] = $f->getArray();
                
                
                die( JSON::encode( $answer ) );
        }
        
	protected function buildList(){
		$this->prepareEverything();

		// Читаем данные
		$f = $this->getDataModel();
		// Смена лимита
		if ( isset($this->data['admin_limit']) && $this->data['admin_limit'] > 0 ){
			$f->setLimit( $this->data['admin_limit'] );
		}
		$f->isEmpty();

		Core::$out->data['ORMData'] = $f;

		// Глобальный readonly
		$cantTouchThis = ( isset($this->data['admin_readonly']) && $this->data['admin_readonly'] );
		if ( !$cantTouchThis && !Core::$auth->isAllowed( Auth_Abstract::ACCESS_DATA_MODELS_WRITE ) ) $cantTouchThis = TRUE;


		// Навигация
		if ( $f->pages > 1 ){
			$navi_array = array(
			'name'=>'Навигация',
			'_'=>array(
				array( 'type'=>'relative_url', 'name'=>'Предыдущая', 'icon'=>'fam/arrow_left.png', 'url'=>array_merge(Core::$reg->data['url_offset'],array('list','page',$f->page-1)), 'disabled'=>( $f->page == 1 ), 'params'=>array('oo'=>Core::$in->_get['oo'],'of'=>Core::$in->_get['of'],'f'=>Core::$in->_get['f']) ),
				array( 'type'=>'relative_url', 'name'=>'Следующая', 'icon'=>'fam/arrow_right.png', 'url'=>array_merge(Core::$reg->data['url_offset'],array('list','page',$f->page+1)), 'disabled'=>( $f->page == $f->pages ), 'params'=>array('oo'=>Core::$in->_get['oo'],'of'=>Core::$in->_get['of'],'f'=>Core::$in->_get['f']) ),
				array('type'=>'text','name'=>'Страница', 'value' =>($f->page.' из '.$f->pages) ),
			)
			);
			Core::$reg->data->buttons->push( $navi_array );
		}

		// Фильтрация
		if ( TRUE ){
			$filter_array = array(
			'name'=>'Фильтры',
			'_'=>array(
				array('type'=>'caption','value' =>'фильтрация' ),
				array('type'=>'jsid','value' =>'FilterInputbox' ),
			),
			'__'=>array(
				array( 'type'=>'relative_url','name'=>'Расширенный','icon'=>'fatcow/filter.png','url'=>array('data',$this->name,'filter'), 'disabled'=>TRUE )
				)
			);
			Core::$reg->data->buttons->push( $filter_array );
		}

                $dataButtons = array(
			'name'=>'Данные',
			'__'=>array(
				array( 'type'=>'relative_url','name'=>'Добавить','icon'=>'fatcow/document_import.png','url'=>array_merge(Core::$out->data['url_offset'],array('add')), 'disabled'=>$cantTouchThis),
				array( 'type'=>'relative_url','name'=>'Заполнить','icon'=>'fatcow/table_import.png','url'=>array('data',$this->name,'fill'), 'disabled'=>$cantTouchThis || !$this->native_controller),
				array( 'type'=>'relative_url','name'=>'Экспорт','icon'=>'fatcow/table_export.png','url'=>array('data',$this->name,'export'), 'disabled'=>!Core::$auth->isAllowed( Auth_Abstract::ACCESS_DATA_MODELS_EXPORT ) || !$this->native_controller )
			)
		);
                
                if ( Core::$auth->isAllowed( Auth_Abstract::ACCESS_DATA_MODELS_WRITE ) )
                    $dataButtons['__'][] = array( 'type'=>'javascript','name'=>'Удалить','icon'=>'fatcow/bin.png','id'=>'DeleteButton','disabled'=>TRUE);
                    
		Core::$reg->data->buttons->push( $dataButtons );

		Core::$out->show('orm/list');
	}

}