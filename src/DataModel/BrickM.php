<?php
/**
 * @todo
 * @internal
 * @ignore 
 */
class DataModel_BrickM extends DataModel_FetcherM{

        public $configuration;
	public $behaviour = DataModel_Brick::NORMAL;
	public $volatile = FALSE;
    
        public $brickName;
        
        public function __construct( $name = '' ){ $this->brickName = $name; }
        
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
    
	public function confIsTrue( $param ){
		if ( isset($this->configuration[$param]) && $this->configuration[$param] ) return TRUE;
		return FALSE;
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
            $array['engine'] = 'mongo';
	    return $array;
	}
        
        
        protected function lazyInitialization(){
		$this->table = $this->configuration['table'];
		
		// Обрабатываем переменные
		if ( isset($this->configuration['filter']) && is_array($this->configuration['filter']) ) foreach ( $this->configuration['filter'] as $k=>$v ){
			$this->configuration['filter'][$k][1] = DataModel_Brick::replaceEnvironmentVariables($v[1]);
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
			if ( isset($v[2]) )
				$xf->setI18NModeInOne( $v[2] );
			$this->joinXto1( $k, $xf);
		}

		// Принудительный индекс
		if ( isset( $this->configuration['force_index'] ) ) $this->forceIndex ( $this->configuration['force_index'] );
		
		parent::lazyInitialization();
		
		// Возможно требуется специальное поведение
		if ( isset( $this->configuration['behaviour'] ) )
			$this->changeBehaviour();
	}        
}