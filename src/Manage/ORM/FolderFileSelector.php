<?php

class Manage_ORM_FolderFileSelector extends Manage_ORM_ListField{
    
        protected $listValues;
	protected $folder;
        
        public function __construct($rawData, $folder) {
            parent::__construct($rawData);
            if (substr( $folder , -1) != '/' ) $folder .= '/';
            if ( !file_exists($folder) || !is_dir($folder) || !is_readable($folder) ) throw new StandardException("Expected folder $folder doesnt exists or not readable");
            $this->folder = $folder;
        }
        
	protected function lazyReadValues(){
            if ( isset($this->listValues) ) return;
            $this->listValues = array(''=>'Файл не выбран');
            $dp = opendir( $this->folder );
            while ( FALSE != ($file = readdir( $dp )) ){
                if ( $file[0] == '.' ) continue;
                if (is_dir( $this->folder.$file ) ) continue;
                
                $this->listValues[$file] = $file;
            }
            closedir( $dp );
	}
	
	public function resolveValue( $value ){
		$this->lazyReadValues();
		if ( !in_array($value, $this->listValues) ) return 'неизвестный файл'.$value;
		return $value;
	}
	
}
