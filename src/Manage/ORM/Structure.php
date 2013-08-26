<?php

class Manage_ORM_Structure{
	
	private $tableName;
	private $rawFields = array();
	private $fields = array();
	
	private $forcedDeclaration;
	private $forcedClasses;
	private $externalFields;
	private $fileFields;
	private $i18n_fields;
	
	public function __construct( $brick ){
		$this->tableName = $brick['table'];
		Core::getDatabase()->getBuilder('plain')
		->describe( $brick['table'] )
		->exec();
		foreach ( Core::getDatabase()->result as $row ){
			$this->rawFields[ $row['Field'] ] = $row;
		}
		$this->externalFields = ( isset($brick['external']) ? $brick['external'] : NULL );
		$this->fileFields = ( isset($brick['admin_files']) ? $brick['admin_files'] : NULL );
		$this->forcedDeclaration = ( isset($brick['admin_fields']) ? $brick['admin_fields'] : NULL );
		$this->forcedClasses = ( isset($brick['admin_field_classes']) ? $brick['admin_field_classes'] : array() );
		$this->i18n_fields = ( isset($brick['fields_i18n']) ? $brick['fields_i18n'] : NULL );
		
		$this->produceORMFields();
	}
	
	public function getFields(){ return $this->fields; }
	/**
	 * @return Manage_ORM_Field
	 */
	public function findFieldByName( $name ){
		foreach ( $this->fields as $row ){
			if ( $row->name == $name ) return $row;
		}
	}
	
	private function produceORMFields(){
		$fieldsToProduce = $this->rawFields;
		// Сначала идём по принудительной инициализации
		if ( isset($this->forcedDeclaration) && is_array( $this->forcedDeclaration ) ) foreach ($this->forcedDeclaration as $k=>$v) {
			switch ( strtolower($v['type']) ){
				case 'html':
					if ( isset($this->i18n_fields) && in_array( $k , $this->i18n_fields) ){
						// Требуется замена по i18N
						foreach ( Core::getI18NLanguages() as $lang ){
							$field = new Manage_ORM_StringField( $fieldsToProduce[$k.'_'.$lang] );
							$field->setHTML();
							$this->fields[] = $field;
							unset( $fieldsToProduce[$k.'_'.$lang] );
						}
					} else {
						$field = new Manage_ORM_StringField( $fieldsToProduce[$k] );
						$field->setHTML();
						$this->fields[] = $field;
						unset( $fieldsToProduce[$k] );
					}
					break;
                                 case 'ro':
                                 case 'readonly':
                                     $field = new Manage_ORM_ReadOnlyField( $fieldsToProduce[$k] );
                                     $this->fields[] = $field;
                                     unset( $fieldsToProduce[$k] );
                                     break;
                                 case 'filelist':
                                     $field = new Manage_ORM_FolderFileSelector( $fieldsToProduce[$k], $v['folder'] );
                                     $this->fields[] = $field;
                                     unset( $fieldsToProduce[$k] );
                                     break;
			}
		}
		
		// Идем по инициализации файлов
		if ( isset($this->fileFields) && is_array( $this->fileFields ) ) foreach ( $this->fileFields as $k=>$v ){
			// Проверка наличия необходимых полей
			if ( !isset( $fieldsToProduce[$k.'_name'], $fieldsToProduce[$k.'_ext'] ) ) throw new StandardException("Field `$k` for file upload not found");
			if ( isset($v['class'])  ){ 
				$className = $v['class'];
				$this->fields[] = new $className($fieldsToProduce[$k.'_name'], $k, $v);
			}elseif ( isset($v['type']) && $v['type'] == 'image' )
				$this->fields[] = new Manage_ORM_ImageFileField($fieldsToProduce[$k.'_name'], $k, $v);
			else
				$this->fields[] = new Manage_ORM_FileField($fieldsToProduce[$k.'_name'], $k, $v);
				
			unset( $fieldsToProduce[$k.'_name'], $fieldsToProduce[$k.'_ext'] );
		}
		
		// Идём по внешним связям
		if ( isset($this->externalFields) && is_array( $this->externalFields ) ) foreach ( $this->externalFields as $k=>$v ){
			if ( !isset($fieldsToProduce[$k]) ) continue;
			$this->fields[] = new Manage_ORM_ExternalField( $fieldsToProduce[$k], $v[0], $v[1] );
			unset( $fieldsToProduce[$k] );
		}
		
		// Теперь - просто по столбцам
		foreach ( $fieldsToProduce as $row ){
			$sp = strpos( $row['Type'] , '(');
			$type = strtolower( $row['Type'] );
			if ( $sp !== FALSE ) $type = substr($type, 0, $sp);
			if ( $row['Type'] == 'tinyint(1)' ){
				$this->fields[] = new Manage_ORM_TogglerField($row);
				continue;
			}
			if ( $row['Type'] == "enum('y','n')" || $row['Type'] == "enum('n','y')" ){
				$this->fields[] = new Manage_ORM_TogglerField($row, TRUE);
				continue;
			}
			
			if ( isset($this->forcedClasses[$row['Field']]) ){
				$className = $this->forcedClasses[$row['Field']];
				$this->fields[] = new $className( $row );
				continue;
			}
			
			switch ( $type ){
				case 'int':
				case 'bigint':
				case 'mediumint':
				case 'tinyint':
				case 'float':
				case 'decimal':
					$this->fields[] = new Manage_ORM_NumericField($row);
					break;
				case 'date':
				case 'datetime':
				case 'timestamp':
					$this->fields[] = new Manage_ORM_TimestampField($row);
					break;
				case 'enum':
					$this->fields[] = new Manage_ORM_ListField( $row );
					break;
				case 'char':
				case 'varchar':
				case 'text':
				default:
					$this->fields[] = new Manage_ORM_StringField($row);
					break;
			}
		}
	}
	
}