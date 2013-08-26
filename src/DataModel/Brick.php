<?php

class DataModel_Brick extends DataModel_Fetcher {

	const NORMAL = 1;
	const KEY_VALUE_PAIR = 2;
	const KEY_VALUE_PAIR_SECTIONED = 3;

	private static $EnvVars;

	protected $configuration;
	protected $behaviour = self::NORMAL;
	protected $volatile = FALSE;
	protected $brickName = '';

	// Для совместимости делаем через Factory
	public static function Factory( $brickConfigurationFileName ){
		if ( is_string( $brickConfigurationFileName ) ){
			if ( substr($brickConfigurationFileName, -4) !== '.php' ) $brickConfigurationFileName .= '.php';
			if ( !Core::$fs->find( Standard_CoreFS::TYPE_BRICKS , $brickConfigurationFileName) ) throw new Exception("No brick file");
                        $result = Core::$fs->load( Standard_CoreFS::TYPE_BRICKS , $brickConfigurationFileName);
			if ( is_object($result) ) return $result;
		} else $result = $brickConfigurationFileName;

		if ( isset($result['behaviour']) && $result['behaviour'][0] == 'sectioned_hash' ){
			$obj = new DataModel_SectionedHash();
			$obj->configuration = $result;
			$obj->behaviour = self::KEY_VALUE_PAIR_SECTIONED;
			return $obj;
		}

                if ( isset( $result['engine'] ) && $result['engine'] == 'mongo' )
                    $obj = new DataModel_BrickM();
                else
                    $obj = new self();
		$obj->configuration = $result;
		$obj->brickName = ( isset( $result['name'] ) ? $result['name'] : $brickConfigurationFileName );

		// Не корысти ради, а токмо волею пославшей мя супруги!
		// То есть - ради кеширования
		if ( isset($obj->configuration['cache']) && is_array($obj->configuration['cache']) ){
			$obj->setCache( self::replaceEnvironmentVariables( $obj->configuration['cache'][0]), $obj->configuration['cache'][1] );
		}
		if ( isset($obj->configuration['behaviour']) && is_array($obj->configuration['behaviour']) && $obj->configuration['behaviour'][0] == 'hash' ){
			$obj->behaviour = self::KEY_VALUE_PAIR;
		}
		// Устанавливаем спец флаг
		if ( $obj->confIsTrue('not_empty') ) $obj->volatile = TRUE;
		if ( isset( $obj->configuration['use_lazy'] ) && !$obj->configuration['use_lazy'] ) $obj->notLazy = TRUE;
		// Подвязываем события
		if ( isset( $obj->configuration['event_handler'] ) ){
			$eh = $obj->configuration['event_handler'];
			$eh = new $eh();
			if ( !($eh instanceof StandardEventReciever) ) throw new StandardException("Unable set event reciever. It must implement StandardEventReciever interface");
			if ( !($eh instanceof StandardDataModelBinder) ) throw new StandardException("Unable set event reciever. It must implement StandardDataModelBinder interface");
			$eh->bindDataModel( $obj );
			$obj->bindEvent( StandardEventReciever::INIT, $eh );
			$obj->bindEvent( StandardEventReciever::INFLATE, $eh );
			$obj->bindEvent( StandardEventReciever::FINALIZE_INIT, $eh );
			$obj->bindEvent( StandardEventReciever::BRICK_BEHAVIOUR_INIT, $eh );
		}
		return $obj;
	}

	public function __construct(){}

	protected function confIsTrue( $param ){
		if ( isset($this->configuration[$param]) && $this->configuration[$param] ) return TRUE;
		return FALSE;
	}

	protected function getCacheBundle(){
		$bundle = parent::getCacheBundle();
		$bundle[] = $this->behaviour;
		$bundle[] = $this->volatile;
		return $bundle;
	}
	protected function restoreBundle( $data ){
		$this->volatile = array_pop($data);
		$this->behaviour = array_pop($data);
		return parent::restoreBundle($data);
	}

	public static function replaceEnvironmentVariables( $text ){
		if ( strpos($text, '$') === FALSE ) return $text;
		if ( !isset(self::$EnvVars) ){
			for ( $i=1; $i<6; $i++ ){
				$value = '';
				if ( isset(Core::$in->_navigation[$i]) ) $value = Core::$in->_navigation[$i];
				self::$EnvVars['$'.$i] = $value;
				self::$EnvVars['$i'.$i] = intval($value);
			}
			if ( Configuration::I18N )
				self::$EnvVars['$L'] = Core::getI18NLanguage();
			else
				self::$EnvVars['$L'] = '';

			if ( isset(Core::$auth) && Core::$auth->isHere() ) self::$EnvVars['$U'] = Core::$auth->getUID();
			else self::$EnvVars['$U'] = 0;
		}
		// Специально правило для $Core$
		$m = array();
		if ( strpos( $text , '$Core$') !== FALSE && preg_match('/\$Core\$data\$([^\$]+)\$([^\$]+)\$/', $text, $m) ){
			if ( !isset( self::$EnvVars[$m[0]] ) ){
				$element = Core::$registry->data->offsetGet($m[1])->offsetGet(0);
				self::$EnvVars[$m[0]] = $element[$m[2]];
			}
		}
		return str_replace(array_keys(self::$EnvVars), array_values(self::$EnvVars), $text);
	}

	protected function lazyInitialization(){
		$this->table = $this->configuration['table'];

		// Обрабатываем переменные
		if ( isset($this->configuration['filter']) && is_array($this->configuration['filter']) ) foreach ( $this->configuration['filter'] as $k=>$v ){
			$this->configuration['filter'][$k][1] = self::replaceEnvironmentVariables($v[1]);
		}

		// Указываем перечень полей
		if ( isset($this->configuration['fields']) && !empty($this->configuration['fields']) ){
			$this->setFields( $this->configuration['fields'], true );
		}


		// Сортировка
		if ( isset($this->configuration['order']) && is_array($this->configuration['order']) ) $this->setOrdering( $this->configuration['order'] );
		else if ( $this->confIsTrue('use_order') ) $this->setOrdering( array(array('order','asc')) );

		// Лимиты и постраничка
		if ( isset($this->configuration['limit']) && $this->configuration['limit'] > 0 ){
			$this->setLimit( (int) $this->configuration['limit'] );
			if ( isset($this->configuration['page']) ){ // а вот и постраничка
				$this->setPage( (int)  $this->replaceEnvironmentVariables( $this->configuration['page'] ) );
			}
		}

		// Расширенные правила фильтрации
		$ext_filters = array(
			'!'=>SQL_Helper_Common::O_NOT_EQUAL,
			'!='=>SQL_Helper_Common::O_NOT_EQUAL,
			'>'=>SQL_Helper_Common::O_BIGGER_THAN,
			'>='=>SQL_Helper_Common::O_BIGGER_EQUAL,
			'<'=>SQL_Helper_Common::O_SMALLER_THAN,
			'<='=>SQL_Helper_Common::O_SMALLER_EQUAL,
		);

		// Фильтрация
		if ( isset( $this->configuration['filter'] )){
			if ( is_array( $this->configuration['filter'] ) ){ // Обычный фильтр
				foreach ( $this->configuration['filter'] as &$filter ){
					if ( !isset( $filter[2] ) )
						$this->addFilter( $filter[0] , $filter[1]);
					elseif ( isset( $ext_filters[$filter[2]] ) )
						$this->addFilterSpecial( new SQL_Helper_Common($filter[0], $ext_filters[$filter[2]], $filter[1]) );
				}
			}
		}

		// Языковая проверка
		if ( isset($this->configuration['fields_i18n']) && is_array($this->configuration['fields_i18n']) && count($this->configuration['fields_i18n']) )
			$this->setI18NModeInOne( $this->configuration['fields_i18n'] );
		if ( isset($this->configuration['filter_i18n']) )
			$this->setI18NModeFilter( $this->configuration['filter_i18n'] );

		// Проверка на show
		if ( !isset($this->configuration['use_show']) || $this->configuration['use_show'] ) $this->setShowChecking( TRUE );
		else  $this->setShowChecking( FALSE );


		// Внешние связи
		if ( isset($this->configuration['external']) ) foreach ( $this->configuration['external'] as $k=>$v ) {
			$xf = new DataModel_Fetcher( $v[0] );
			$xf->setShowChecking( FALSE );
			$xf->setFields( $v[1] );
			if ( isset($v[2]) && $v[2] !== NULL )
				$xf->setI18NModeInOne( $v[2] );
            if ( isset( $v[3], $v[3]['filter'] ) && $v[3] !== NULL )
                $xf->setFilters( $v[3]['filter'] );
			$this->joinXto1( $k, $xf, (isset($v[4]) ? $v[4] : 'id'));
		}

        if( isset($this->configuration['external_many']) ) foreach ( $this->configuration['external_many'] as $k=>$v ) {
            $xf = new DataModel_Fetcher( $v[0] );
            $xf->setShowChecking( FALSE );
            $xf->setFields( $v[1] );
            if ( isset($v[2]) && $v[2] != NULL )
                $xf->setI18NModeInOne( $v[2] );
            if ( isset( $v[3], $v[3]['filter'] ) && $v[3] !== NULL )
                $xf->setFilters( $v[3]['filter'] );
            $this->join1toX( $k, $xf, $v[4]);
        }

		// Принудительный индекс
		if ( isset( $this->configuration['force_index'] ) ) $this->forceIndex ( $this->configuration['force_index'] );

		parent::lazyInitialization();

		// Возможно требуется специальное поведение
		if ( isset( $this->configuration['behaviour'] ) )
			$this->changeBehaviour();
	}

	public function getBehaviour(){ return $this->behaviour; }
	protected function changeBehaviour(){
		$this->castEvent( StandardEventReciever::BRICK_BEHAVIOUR_INIT );
		if ( is_array($this->configuration['behaviour']) && $this->configuration['behaviour'][0] == 'hash' ) {
			$new_data = array();
			foreach ( $this->data as $row ){
				$new_data[$row[$this->configuration['behaviour'][1]]] = $row[$this->configuration['behaviour'][2]];
			}
			$this->data = $new_data;
		}
	}

	public function isVolatile(){
		return $this->volatile;
	}

	public function getExposeFields( $type ){
		switch ( $type ){
			case 'title':
				if ( !isset($this->configuration['expose_title']) ) return;
				if ( !is_array( $this->configuration['expose_title'] ) ) return array( $this->configuration['expose_title'] );
				return $this->configuration['expose_title'];
			case 'keywords':
				if ( !isset($this->configuration['expose_keywords']) ) return;
				if ( !is_array( $this->configuration['expose_keywords'] ) ) return array( $this->configuration['expose_keywords'] );
				return $this->configuration['expose_keywords'];
			case 'description':
				if ( !isset($this->configuration['expose_description']) ) return;
				return $this->configuration['expose_description'];
		}
	}

	public function description() {
	    $array = parent::description();
	    $array['brick'] = $this->brickName;
	    return $array;
	}
}