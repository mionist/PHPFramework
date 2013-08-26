<?php

class File_Upload{
	
	protected $allowed_exts;
	protected $default_ext = NULL;
	
	protected $temporary;
	protected $original_name;
	protected $original_ext;
	
	protected $final_name;
	protected $final_ext;
	
        /**
         *
         * @param array $filesSubarray
         * @return File_Upload
         * @throws Exception_File 
         */
	public static function createOnIncomingFiles( $filesSubarray ){
		if ( !is_array( $filesSubarray ) || !isset($filesSubarray['tmp_name'],$filesSubarray['name']) ) throw new Exception_File("Empty _FILES data");
		return new self( $filesSubarray['tmp_name'], $filesSubarray['name'] );
	}
	
	protected final function __construct( $name_tmp, $name_original ){
		// Временное имя
		$this->temporary = $name_tmp;
		
		// Разбиваем оригинальное имя на составляющие
		if ( empty($name_original) ) throw new Exception_File_WrongFilename( $name_original );
		$name_original = strtolower( $name_original );
		$dot = strrpos( $name_original, '.');
		if ( $dot === FALSE ){
			$this->original_name = $name_original;
			$this->original_ext = $this->default_ext;
		}else {
			$this->original_name = substr( $name_original , 0, $dot);
			$this->original_ext = substr( $name_original , $dot+1 );
		}
	}
	
	public final function uploadTo( $destinationFolder, $newNameWithoutExt = NULL ){
		// Добавляем слеш
		if ( substr($destinationFolder, -1) != '/' ) $destinationFolder .= '/';
		// Проверка папки
		if ( !file_exists( $destinationFolder ) || !is_dir( $destinationFolder ) ) throw new Exception_File_FolderNotWriteable("Folder not exists");
		if ( !is_readable( $destinationFolder ) || !is_writeable( $destinationFolder ) ) throw new Exception_File_FolderNotWriteable("Folder not writeable");
		
		// Устанавливаем новое имя
		$this->final_name = $this->original_name;
		$this->final_ext = $this->original_ext;
		if ( isset( $newNameWithoutExt ) ) $this->final_name = $newNameWithoutExt;
		$fullname = $destinationFolder.$this->final_name.'.'.$this->final_ext;
		
		// Пытаемся переместить
		if ( !file_exists( $this->temporary ) ) throw new Exception_File("Temporary file not exists");
		
		// Проверяем
		$this->validateExt();
		
		// Перемещаем
		move_uploaded_file( $this->temporary, $fullname );
		
		return $this;
	}
	
	public function validateExt(){
		if ( !isset($this->original_ext) ) throw new Exception_File_WrongFilename( "File without exception" );
		if ( isset($this->allowed_exts) && !in_array( $this->original_ext , $this->allowed_exts) )  throw new Exception_File_WrongFilename( "Extension {$this->original_ext} not supported" );
	}
	
	public final function getSavedName(){ return $this->final_name; }
	public final function getSavedExt(){ return $this->final_ext; }
	
	public function setAllowedExtensions( $array ){
		$this->allowed_exts = array_map( 'strtolower', $array);
		return $this;
	}
	public function setDefaultExtension( $value ){
		$this->default_ext = $value;
		if ( !isset($this->original_ext) ) $this->original_ext = $this->default_ext;
		return $this;
	}
}