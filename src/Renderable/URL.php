<?php

class Renderable_URL extends Renderable_Item{
	
	private static $allowedCharsRegexp = 'a-z0-9а-я_\-';
	public static $prefixes = array();
        
        private $ignorePrefixes = FALSE;
	protected $get;
	
        public static function LanguageRedirector( $toLanguage ){
            if ( !Configuration::I18N ) throw new StandardException("I18N not enabled");
            if ( !Configuration::I18N_IN_URL ) throw new StandardException("I19N in url must be enabled");
            $pos = Core::$in->_navigation->getArray();
            if ( count($pos) < 2 ) $pos = array($toLanguage);
            else $pos[0] = $toLanguage;
            $a = new self( $pos );
            $a->ignorePrefixes = TRUE;
            return $a;
        }
        
	public function __construct( $pathArray, $getParamatersArray = NULL ){
		if ( is_string($pathArray) ) $pathArray = explode('/',$pathArray);
		parent::__construct( $pathArray, Renderable_Item::CONTEXT_PLAINTEXT );
		$this->get = $getParamatersArray;
	}
	
	protected function produceContext($context){
		// Контекст не важен
		$url = '/';
		if ( !$this->ignorePrefixes && isset(self::$prefixes) && is_array( self::$prefixes ) && count(self::$prefixes) ) $url .= implode('/',self::$prefixes).'/';
		
		if ( is_object( $this->data ) && $this->data instanceof DataModel_Array )
			$this->data = $this->data->getArray();
		$url .= implode('/', array_map(array($this,'encode'), $this->data));
		if ( isset($this->get) && ( is_array($this->get) || is_object($this->get) ) && count($this->get) > 0 ){
			$first = TRUE;
			foreach ( $this->get as $k=>$v ){
				if ( is_array($v) ){
					foreach ( $v as $row ){
						if ( !$first )
							$url .= '&';
						else 
							$url .= '?';
						$url .= $k.'[]='.urlencode($row);
						$first = FALSE;
					}
					continue;
				}
				
				if ( !$first )
					$url .= '&';
				else 
					$url .= '?';
				$url .= $k.'='.urlencode($v);
				$first = FALSE;
			}
		}
		return $url;
	}
	
	protected function encode( $string ){
		if ( is_numeric( $string ) ) return $string;
		if ( !preg_match('/[^'.self::$allowedCharsRegexp.']/', $string) ) return $string;
		
		// Очищаем
		$string = preg_replace('/[^'.self::$allowedCharsRegexp.']/iu', ' ', $string);
		$string = trim($string);
		$string = str_replace(' ', '-', $string);
		return $string;
	}
	
}