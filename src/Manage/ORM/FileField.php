<?php

class Manage_ORM_FileField extends Manage_ORM_Field {

	protected $fileField;
	protected $folder;

	public function __construct( $fieldNameRawdata, $fileField, $parameters ){
		parent::__construct( $fieldNameRawdata );
		$this->fileField = $fileField;
		if ( substr( $parameters['folder'] , -1) != '/' ) $parameters['folder'] .= '/';
		$this->folder = 'content/'.$parameters['folder'];
	}

	public function getFileFieldName(){ return $this->fileField; }

	public function isOrderAble(){ return FALSE; }
	public function isHiddenInList(){ return TRUE; }
	public function isSearchAble(){ return FALSE; }

	public function hasFile( $row ){
		return !empty($row[$this->fileField.'_name']);
	}
	public function getFullFilename( $row ){
		return $this->folder.$row[$this->fileField.'_name'].'.'.$row[$this->fileField.'_ext'];
	}

	public function prepareDelete( $oldData ){
		if ( !$this->hasFile( $oldData ) ) return;
		$this->safeDeleteFile( $this->folder.$oldData[$this->fileField.'_name'].'.'.$oldData[$this->fileField.'_ext'] );
	}

        protected function safeDeleteFile( $filename ){
                if (strpos( $filename, '..') ) return; // данунах
                try{
                    unlink( $filename );
                } catch( Exception $e ){}
        }
        
	public function prepareSaveData( $oldData, $ignore ){
                $answer = array();

                // Проверяем необходимость удаления файла
                if ( isset( $ignore[ 'delete_file_'.$this->getFileFieldName() ]) && $ignore[ 'delete_file_'.$this->getFileFieldName() ] == 'yes' ){
                    $this->prepareDelete($oldData);
                    $answer = array(
			$this->fileField.'_name' =>'',
			$this->fileField.'_ext' =>''
                    );
                }

		// Проверяем заполненность files
		if ( !isset($_FILES) || !is_array($_FILES) || count($_FILES) === 0 || !isset($_FILES[$this->fileField]) || $_FILES[$this->fileField]['error'] === UPLOAD_ERR_NO_FILE ) return $answer;

		// Проверяем на необходимость удаления старого файла
		if ( $this->hasFile( $oldData ) ) $this->prepareDelete($oldData);

		$uploader = File_Upload::createOnIncomingFiles( $_FILES[$this->fileField] );
		$uploader->uploadTo( $this->folder, time().'_'.mt_rand(1, 10000) );

		$answer = array(
			$this->fileField.'_name' =>$uploader->getSavedName(),
			$this->fileField.'_ext' =>$uploader->getSavedExt()
		);

                return $answer;
	}

	public function getPropertiesEdit( $row ){
		if ( !$this->hasFile( $row ) ) return array();
                try{
                    return array(
                            'загружено'=>'(<a style="color: black;" href="/'.$this->getFullFilename( $row ).'">ссылка</a>)&nbsp;'.$this->getFullFilename( $row ),
                            'размер'=>intval(filesize( $this->getFullFilename( $row ) ) / 1000).' Кб',
                    );
                } catch ( Exception $e ){
                    return array(
                            'файл'=>$this->getFullFilename( $row ),
                            'ошибка'=>'файл должен быть, но видимо удалён или недоступен для чтения'
                    );
                }
	}
}