<?php

class DataModel_Fetcher extends DataModel_Array{

	public $page;
	public $pages;

	const JOIN_ROOT 										= 1;
	const JOIN_MANY_TO_ONE									= 2;
	const JOIN_MANY_TO_MANY									= 3;
	const JOIN_ONE_TO_MANY									= 4;
	const JOIN_DEPS											= 100;

	const I18N_DISABLED										= 0;
	const I18N_IN_ONE										= 1;
	const I18N_FILTER										= 2;

	/**
	 *
	 * @var Dbx
	 */
	private $db;

	protected $fetch_fields_list 				= '*';
	protected $filters;

	protected $table;
	protected $table_alias;
	protected $show_check						= TRUE;
	protected $limit							= 0;
	protected $ordering;
	protected $language_check;

	private $joins							= array();

	protected $force_index = NULL;

	/** ЯЗЫКОВЫЕ ФУНКЦИИ **/
	protected $i18n_mode;
	protected $i18n_filtering_field;
	protected $i18n_infile_fields				= array();

        private $diagnostics_sql                                = '';

	/* Совместимость с DataModel */

	public function erase(){
		return parent::erase();
	}

	protected function lazyInitialization(){
		$this->castEvent( StandardEventReciever::FINALIZE_INIT );
		$this->fetch();
	}

	protected function getCacheBundle(){
		$bundle = parent::getCacheBundle();
		$bundle[] = $this->page;
		$bundle[] = $this->pages;
		return $bundle;
	}
	protected function restoreBundle( $data ){
		$this->pages = array_pop($data);
		$this->page = array_pop($data);
		return parent::restoreBundle($data);
	}










	/* Многоязыковая поддержка */
	public function setI18NModeFilter( $filterField = 'lang' ){
		$this->i18n_mode = self::I18N_FILTER;
		$this->i18n_filtering_field = $filterField;
	}
	public function setI18NModeInOne( $fieldsArray ){
		$this->i18n_mode = self::I18N_IN_ONE;
		$this->i18n_infile_fields = $fieldsArray;
	}

	/* Функции самого фетчера */

	private function smartArray( $array, $concatMode ){
		if ( !is_array( $array ) || count( $array ) == 0 ) return '';
		$columns = array();
		foreach ( $array as $subarray ){
			if ( is_array( $subarray ) ){
				$temp = '`'.$subarray[0].'`';
				if ( $concatMode == 'where' ) $temp .= ' = "'.$subarray[1].'" ';
				elseif ( $concatMode == 'order' ) {
					if ( $subarray[0] == 'RAND()' )
						$temp = ' RAND()';
					else
						$temp .= ' '.strtoupper( $subarray[1] );
				}

				$columns[] = $temp;
			} elseif ( $subarray instanceof SQL_Helper_Abstract ) {
				$thru = $subarray->getSQL( $this->getDatabase() );
				if ($thru) $columns[] = $thru;
			}
		}

		if ( $concatMode == 'where' ) return implode('AND', $columns);
		elseif ( $concatMode == 'order' ) return implode(',', $columns);
		return '';
	}

	/**
	 *
	 * @return Dbx
	 */
	private function getDatabase(){
		if ( isset($this->db) ) return $this->db;
		else return Core::getDatabase();
	}

	public function __construct( $table_name, Dbx $database_object_optional = NULL ){
		$this->table = $table_name;
		$this->table_alias = $table_name;
		$this->db = $database_object_optional;
	}

	/* Настройки */
	public function setShowChecking( $value ){ $this->show_check = (bool) $value; return $this; }

	public function setLimit( $value ){ $this->limit = (int) $value; return $this; }
	public function setPage( $value ){ $this->page = max((int) $value,1); return $this; }
	public function setAlias( $value ){ $this->table_alias = $value; return $this; }
	public function erasePagination(){ $this->page = null; $this->limit = 0; }

	public function forceIndex( $name ){ $this->force_index = $name; }

	/* Простые геттеры */
	public function getTable(){ return $this->table; }
	public function getLimit(){ return $this->limit; }

	/* Манипуляция входящими данными */
	public function setFields( $data, $escape = TRUE ){
		if ( !is_array( $data ) || count( $data ) == 0 ) return;

		if ( $escape ) foreach ( $data as &$row ){
			if ( substr( $row, 0, 8 ) === 'DISTINCT' ) continue;
			$row = "`$row`";
		}

		$data = implode(', ',$data);
		$this->fetch_fields_list = $data;
		return $this;
	}


	public function setFilters( $array ){
		$this->filters = $array;
		return $this;
	}

	public function addFilter( $key, $value ){
		$this->filters[] = array( $key, $this->getDatabase()->escape($value) );
		return $this;
	}

	public function removeFilter( $key ){
		if ( !isset( $this->filters ) || count( $this->filters ) == 0 ) return;
		foreach ( $this->filters as $k=>$v ) {
		    if ( is_array($v) && $v[0] == $key ) {unset( $this->filters[$k] );continue;}
		    if ( is_object($v) && $v instanceof SQL_Helper_Common && $v->getFieldName() == $key ) {unset( $this->filters[$k] );continue;}
		}
		return $this;
	}

	public function addFilterSpecial( SQL_Helper_Abstract $obj ){
		$this->filters[]  =$obj;
		return $this;
	}

	public function setOrdering( $array ){
		$this->ordering = $array;
		return $this;
	}

        public function hasOrderring(){ return isset( $this->ordering ); }

	/**
	 * Связь многие к одному
	 * например - к категории
	 *
	 * @param string $onField
	 * @param Fetcher $object
	 * @param string $child_field
	 */
	public function joinXto1( $onField, DataModel_Fetcher $object, $child_field = 'id' ){
		$object->mode = self::JOIN_MANY_TO_ONE;

		$this->joins[] = array(
			'onfield'=>$onField,
			'type'=>self::JOIN_MANY_TO_ONE,
			'object'=>$object,
			'childfield'=>$child_field
		);
		return $this;
	}

	/**
	 * Связь один к многим
	 * например - приложенные файлы
	 *
	 * @param string $onField
	 * @param Fetcher $object
	 * @param string $child_field
	 */
	public function join1toX( $onField, DataModel_Fetcher $object, $child_field ){
		$object->mode = self::JOIN_ONE_TO_MANY;

		$this->joins[] = array(
			'onfield'=>$onField,
			'type'=>self::JOIN_ONE_TO_MANY,
			'object'=>$object,
			'childfield'=>$child_field
		);
		return $this;
	}


	/**
	 * Связь многие к многим
	 * например - теги
	 *
	 * @param string $onField
	 * @param Fetcher $object
	 * @param Fetcher $deps_object
	 * @param string $deps_source_field
	 * @param string $deps_target_field
	 * @param string $child_field
	 */
	public function joinXtoY( $onField, DataModel_Fetcher $object, DataModel_Fetcher $deps_object, $deps_source_field, $deps_target_field, $child_field = 'id' ){
		$object->mode = self::JOIN_MANY_TO_ONE;
		$deps_object->mode = self::JOIN_DEPS;

		$this->joins[] = array(
			'onfield'=>$onField,
			'type'=>self::JOIN_MANY_TO_MANY,
			'object'=>$object,
			'childfield'=>$child_field,
			'd_object'=>$deps_object,
			'd_field_s'=>$deps_source_field,
			'd_field_t'=>$deps_target_field
		);
		return $this;
	}

	private function fetch(){
		// Результирующие фильтры с учётом языковой версии
		$filters = $this->filters;
		if ( $this->i18n_mode == self::I18N_FILTER )
			$filters[] = array($this->i18n_filtering_field,Core::getI18NLanguage());

		// Сортировка
		$ordering = $this->ordering;
		if ( $ordering && $this->i18n_mode == self::I18N_IN_ONE ) foreach ( $ordering as $k=>$v ){
			if ( in_array($v[0],$this->i18n_infile_fields) )
				$ordering[$k][0] = $v[0].'_'.Core::getI18NLanguage();
		}

                $show_check_override = $this->show_check;
                if ( $this->show_check && isset($this->limit) && $this->limit == 1 && isset( Core::$auth ) && Core::$auth->isHere() &&Core::$auth->isPrivileged() ){
                    $show_check_override = FALSE;
                }

		$SQL = ( isset($this->force_index) ? ' FORCE INDEX(`'.$this->force_index.'`) ' : '' )
				.' WHERE 1 '
				.( $show_check_override ? ' AND `show` = "1"' : '' )
				.( $this->language_check ? ' AND `id_language`='.self::$id_language : '' )
				.( $filters ? ' AND '.$this->smartArray( $filters, 'where' ): '' )
		;

		$ORDERANDLIMIT = '';
		if ( $this->ordering ) $ORDERANDLIMIT .= ' ORDER BY '.$this->smartArray( $ordering, 'order' );

		// Реализовываем постраничку, если требуется
		if ( $this->page > 0 ){
			// Защита от дурака
			if ( $this->limit < 1 ) $this->limit = 10;

			// Определяем количество записей
			$this->getDatabase()->getBuilder('plain')
			->select( $this->table , 'count(*) FROM ?? '.$SQL)
			->exec();

			$posts = $this->getDatabase()->result->getValue('count(*)');

			$this->pages = ceil($posts / $this->limit);

			// Лимитируем значение page
			if ( $this->page > $this->pages ) $this->page = $this->pages;
			if ( $this->page < 1 ) $this->page = 1;
		}

		// Дописываем лимиты
		if ( $this->limit ){
			if ( $this->page > 0 ) $ORDERANDLIMIT .= ' LIMIT '.(($this->page-1)*$this->limit).', '.$this->limit;
			else $ORDERANDLIMIT .= ' LIMIT '.$this->limit;
		}

		// Делаем замену для языковой версии, если требуется
		$fields = $this->fetch_fields_list;
		if ( $fields != '*' && $this->i18n_mode == self::I18N_IN_ONE ){
			// Дописываем поля
			foreach (Core::getI18NLanguagesInPriority(2) as $l) foreach ( $this->i18n_infile_fields as $f ){
				$fields .= ",`{$f}_{$l}`";
			}
		}

		// Вызываем select
		$this->getDatabase()->getBuilder('plain')
		->select( $this->table, $fields
					.' FROM `'.$this->table.'` '
					.$SQL
					.$ORDERANDLIMIT)
		->exec();
                $this->diagnostics_sql = $fields
					.' FROM `'.$this->table.'` '
					.$SQL
					.$ORDERANDLIMIT;

		if ( count($this->getDatabase()->result) === 0 ){
			$this->data = array();
			return;
		}
		$this->data = $this->getDatabase()->result->getData();

		// Делаем обратную замену для языковой версии
		if ( $this->count() && $this->i18n_mode == self::I18N_IN_ONE ){
			$l = Core::getI18NLanguagesInPriority(2);
			foreach ( $this->data as $row_number=>$row ){
				foreach ( $this->i18n_infile_fields as $f ){
					if ( !empty($row[$f.'_'.$l[0]]) ){
						$this->data[$row_number][$f] = $row[$f.'_'.$l[0]];
					}elseif( isset($l[1]) && !empty($row[$f.'_'.$l[1]]) ){
						$this->data[$row_number][$f] = $row[$f.'_'.$l[1]];
					}else {
						$this->data[$row_number][$f] = '';
					}
					unset( $this->data[$row_number][$f.'_'.$l[0]] );
					if ( isset($l[1]) ) unset( $this->data[$row_number][$f.'_'.$l[1]] );
				}
			}
		}


		// Работаем с вложенными объектами
		foreach ( $this->joins as $j ){
			switch ( $j['type'] ){
				case self::JOIN_MANY_TO_ONE: // категория
					if ( !isset( $this->data[0][$j['onfield']] ) ) throw new StandardException('Field not found for join');
					// ------ LEVEL 1
					// Насыщаем вложенный объект IN-овым запросом
					$j['object']->addFilterSpecial(
						new SQL_Helper_Common(
							$j['childfield'],
							SQL_Helper_Common::O_IN,
							$this->extractField( $j['onfield'], true )
					));
					$j['object']->setAlias( $j['onfield'] );
					$j['object']->fetch_fields_list .= ', '.$j['childfield'];
					// Отключаем все уровни кеширования
					$j['object']->caching = false;
					// Читаем данные
					$result = $j['object']->getArray();
					if ( $j['object']->isEmpty() ) break;
					// Разворачиваем и подготавливаем данные
					$ct = $j['object']->table_alias.'.';
					$resolve = array();
					foreach ( $result as $row ){
						$row_prepared = array();
						foreach ( $row as $k=>$v ) $row_prepared[ $ct.$k ] = $v;
						$resolve[$row[$j['childfield']]] = $row_prepared;
					}
					// Объединяем данные
					foreach ( $this->data as $k=>$v ){
						if ( isset( $v[$j['onfield']], $resolve[$v[$j['onfield']]] ) ) $this->data[$k] = array_merge($v,$resolve[$v[$j['onfield']]]);
					}
					break;
				case self::JOIN_ONE_TO_MANY: // вложенные файлы
					if ( !isset( $this->data[0][$j['onfield']] ) ) throw new StandardException('Field not found for join');
					// Насыщаем вложенный объект IN-овым запросом
					$j['object']->addFilterSpecial(
						new SQL_Helper_Common(
							$j['childfield'],
							SQL_Helper_Common::O_IN,
							$this->extractField( $j['onfield'], true )
					));
					// Отключаем все уровни кеширования
					$j['object']->caching = false;
					// Читаем данные
					$result = $j['object']->getArray();
					if ( $j['object']->isEmpty() ) break;
					// Разворачиваем и подготавливаем данные
					$ct = $j['object']->table_alias.'.values';
					$resolve = array();
					foreach ( $result as $row ){
						$resolve[ $row[$j['childfield']] ][] = $row;
					}
					// Объединяем данные
					foreach ( $this->data as $k=>$v ){
						if ( isset( $resolve[$v[$j['onfield']]] ) ) $this->data[$k][$ct] = $resolve[$v[$j['onfield']]];
					}
					break;
				case self::JOIN_MANY_TO_MANY: // теги
					if ( !isset( $this->data[0][$j['onfield']] ) ) throw new StandardException('Field not found for join');
					// ------ LEVEL 2
					// проходим через таблицу депов
					$j['d_object']->addFilterSpecial(
						new SQL_Helper_Common(
							$j['d_field_s'],
							SQL_Helper_Common::O_IN,
							$this->extractField( $j['onfield'], true )
					));
					// отключаем все уровни кеширования
					$j['d_object']->caching = false;
					// Читаем данные
					$j['d_object']->getArray();
					if ( $j['d_object']->isEmpty() ) break;

					// ------ LEVEL 1
					// Насыщаем вложенный объект IN-овым запросом
					$j['object']->addFilterSpecial(
						new SQL_Helper_Common(
							$j['childfield'],
							SQL_Helper_Common::O_IN,
							$j['d_object']->extractField( $j['d_field_t'] ,true )
					));
					// Отключаем все уровни кеширования
					$j['object']->caching = false;
					// Читаем данные
					$j['object']->getArray();
					if ( $j['object']->isEmpty() ) break;
					// Разворачиваем и подготавливаем данные
					$chop = array();
					foreach( $j['object'] as $row ){
						$chop[ $row[$j['childfield']] ] = $row;
					}
					// Работаем с депами
					$deps = array();
					foreach ( $j['d_object'] as $row ){
						$deps[ $row[$j['d_field_s']] ][] = $chop[ $row[$j['d_field_t']] ];
					}
					// Объединяем данные
					foreach ( $this->data as $k=>$v ){
						if ( isset( $deps[$v[$j['onfield']]] ) )
							$this->data[$k][$j['object']->table_alias.'.values'] = $deps[$v[$j['onfield']]];
					}
					break;
			}
		 }
	}

	public function extractField( $fieldName, $produceUnique = TRUE ){
		$result = array();
		if ( count($this) == 0 ) return $result;
		foreach ( $this as $row ){
			if ( !isset($row[$fieldName]) ) continue;
			$result[] = $row[$fieldName];
		}
		if ( $produceUnique ) $result = array_unique($result);
		return $result;
	}

        public function description() {
            return array(
		'sql'=>$this->diagnostics_sql,
		'cache_key'=>$this->cache_key,
		'cache_time'=>$this->cache_expiration,
		'cache_hit'=>$this->cache_read_hit
	    );
        }

}