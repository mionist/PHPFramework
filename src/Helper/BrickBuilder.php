<?php

class Helper_BrickBuilder{

    const HTML = 1;
    const IMAGE = 2;
    const FILE = 3;
    const CUSTOM = 4;

    private $data = array();

    public function build(){ return $this->data; }
    public function __construct( $tableName, $name = NULL, $section = NULL ) {
        $this->data['table'] = $tableName;
        $this->data['use_order'] = TRUE;
        $this->data['use_show'] = TRUE;
        $this->data['use_edit'] = TRUE;
        if ( isset($name) ) $this->data['name'] = $name;
        if ( isset($section) ) $this->data['section'] = $section;
    }

    // Decorations
    public function setOrder( $array ){$this->data['order'] = $array;return $this;}
    public function setLimit( $limit ){$this->data['limit'] = (int) $limit; return $this;}
    public function setReadOnly( $field ){
        if ( !isset( $this->data['admin_field_classes'] ) ) $this->data['admin_field_classes'] = array();
        $this->data['admin_field_classes'][$field] = 'Manage_ORM_ReadOnlyField';
    }
    

    public function setAdminListView( $filename ){ $this->data['admin_list_view'] = $filename; return $this; }
    public function setAdminEditView( $filename ){ $this->data['admin_edit_view'] = $filename; return $this; }
    public function setAdminListFields( $arrayOfFields ){
        // ID присутствует всегда
        if ( !in_array('id', $arrayOfFields) ) $arrayOfFields = array_merge (array('id'), $arrayOfFields);
        $this->data['admin_list_fields'] = $arrayOfFields;
        return $this;
    }
    
    public function setAdminListController( $className ){ $this->data['admin_controller_list'] = $className; return $this; }
    public function setAdminEditController( $className ){ $this->data['admin_controller_edit'] = $className; return $this; }
    
    // For Libraries ( Hashtables )
    public function thisIsLibrary( $key = 'key', $value = 'value', $name = NULL, $type = 'string', $section = NULL, $description = NULL ){
        $this->data['behaviour'] = array('hash',$key, $value, $type, $name, $description, $section);
        $this->noOrder();
        $this->noShow();
        return $this;
    }
    
    // Setters
    public function noEdit(){ $this->data['use_edit'] = FALSE; return $this; }
    public function noOrder(){ $this->data['use_order'] = FALSE; return $this; }
    public function noShow(){ $this->data['use_show'] = FALSE; return $this; }
    public function cacheParams( $key, $duration = 60 ){ $this->data['cache'] = array( $key, $duration ); return $this; }
    public function notEmpty(){ $this->data['not_empty'] = TRUE; return $this; }
    public function notLazy(){ $this->data['not_lazy'] = TRUE; return $this; }
    public function setPage( $page ){ $this->data['page'] = $page; return $this; }
    public function setI18NFields( $array ){
        if ( !is_array($array) ) $array = array( $array );
        $this->data['fields_i18n'] = $array;
        return $this;
    }
    public function setFieldType( $type, $name, $className = NULL, $parameter = NULL ){
        switch ( $type ){
            case self::IMAGE:
                if ( !isset( $this->data['admin_files'] ) ) $this->data['admin_files'] = array();
                $this->data['admin_files'][$name] = array('type'=>'image','folder'=>$parameter);
                if ( $className != NULL ) $this->data['admin_files'][$name]['class'] = $className;
                break;
            case self::HTML:
                if ( !isset( $this->data['admin_fields'] ) ) $this->data['admin_fields'] = array();
                $this->data['admin_fields'][$name] = array('type'=>'html');
                break;
            default:
                throw new Exception("Not implemented");
        }
        return $this;
    }
    public function setFieldImage( $folder, $name = 'image', $className = NULL ){
        return $this->setFieldType( self::IMAGE, $name, $className, $folder);
    }

    public function addFilter( $field, $value ){
        if ( !isset($this->data['filter']) ) $this->data['filter'] = array();
        $this->data['filter'][] = array($field, $value);
        return $this;
    }
    
    public function setController( $controllerName ){
        $this->data['event_handler'] = $controllerName;
        return $this;
    }

	public function setIllustrations($type, $table = 'illustrations', $folder = 'illustrations'){
        $this->data['illustrations'] = array(
            'table' => $table,
            'type'=>$type,
            'folder'=>$folder
        );
    }

    public function linkToTable( $onField, $toTable, $toFields, $toi18Fields = NULL, $filters = NULL, $toField = 'id' ){
        if ( !isset($this->data['external']) ) $this->data['external'] = array();
        if ( !is_array($toFields) ) $toFields = (array) $toFields;
        $this->data['external'][ $onField ] = array( $toTable, $toFields, $toi18Fields, $filters, $toField );
        return $this;
    }

    public function linkToTableMany( $onField, $toTable, $toFields, $toi18Fields = NULL, $filters = NULL, $toField = 'id' ){
        if ( !isset($this->data['external_many']) ) $this->data['external_many'] = array();
        if ( !is_array($toFields) ) $toFields = (array) $toFields;
        $this->data['external_many'][ $onField ] = array( $toTable, $toFields, $toi18Fields, $filters, $toField );
        return $this;
    }
    
    //Expose Fields
    public function exposeTitle( array $title ){ $this->data['expose_title'] = $title; }
    public function exposeKeywords( array $keywords ){ $this->data['expose_keywords'] = $keywords; }
    public function exposeDescription( $description ){ $this->data['expose_description'] = $description; }

}