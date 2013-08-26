<?php

class Standard_CoreFS{
    const TYPE_BRICKS = 1;
    const TYPE_SOURCE = 2;
    
    private $registeredFolders = array();
    private $resolve = array();
    private $loadedResources = array();
    
    public function __construct() {
        $this->registeredFolders = array( self::TYPE_BRICKS=>array(), self::TYPE_SOURCE=>array() );
        $this->resolve = array( self::TYPE_BRICKS=>array(), self::TYPE_SOURCE=>array() );
        $this->loadedResources = array( self::TYPE_BRICKS=>array(), self::TYPE_SOURCE=>array() );
    }


    public function registerPath( $type, $path ){
        if (substr($path, -1) != '/' ) $path .= '/';
        $this->checkType($type);
        
        $this->registeredFolders[$type][] = $path;
    }
    
    public function find( $type, $filename ){
        $this->checkType($type);
        if ( !isset( $this->resolve[$type][$filename] ) ){
            $this->resolve[$type][$filename] = FALSE; // Файл не найден
            foreach ( $this->registeredFolders[$type] as $path ){
                if ( !file_exists($path.$filename)) continue;
                $this->resolve[$type][$filename] = $path.$filename;
                break;
            }
        }
        return $this->resolve[$type][$filename];
    }
    
    public function listFilenames( $type, $extensionsArray = 'php' ){
        if ( !is_array( $extensionsArray ) ) $extensionsArray = explode(',',$extensionsArray);
        if ( count($extensionsArray) == 0 || empty($extensionsArray[0]) ) $extensionsArray = NULL;
        $this->checkType($type);
        $list = array();
        foreach ( $this->registeredFolders[$type] as $path ){
            $dp = opendir( $path );
            while ( $file = readdir( $dp ) ){
                if ( !is_file($path.$file) || !is_readable($path.$file) ) continue;
                if (substr($file, 0,1) == '.' ) continue; // .htaccess protection
                $mustContinue = FALSE;
                if ( !is_null( $extensionsArray ) ) foreach ( $extensionsArray as $ext ){
                    $el = strlen( $ext );
                    if ( $el > strlen($file) ){
                        $mustContinue = TRUE;
                        break;
                    }
                    if (substr( $file, 0-$el) != $ext ){
                        $mustContinue = TRUE;
                        break;
                    }
                }
                if ( $mustContinue ) continue;
                $list[] = $file;
            }
            closedir( $dp );
        }
        return array_unique( $list );
    }


    public function load( $type, $filename ){
        // Searching
        $fullname = $this->find($type, $filename);
        if ( $fullname == FALSE ) throw new StandardException("File $filename not found");
        if ( isset( $this->loadedResources[$type][$filename] ) ) return $this->loadedResources[$type][$filename];
        if ( $type == self::TYPE_SOURCE ){
            include $fullname;
            return $this->loadedResources[$type][$filename] = TRUE;
        } 
       return $this->loadedResources[$type][$filename] = include $fullname;
    }
    
    private function checkType( $type ){
        if ( $type != self::TYPE_BRICKS && $type != self::TYPE_SOURCE ) throw new StandardException("Unsupported type $type");
    }
    
    
}