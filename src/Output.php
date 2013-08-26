<?

class Output /*implements ArrayAccess*/{

	private $folder = 'view/';

	public $data;
	public $lib;
	public $meta;


	public $isUser  = false;
	public $isAdmin = false;

	const CONTENT_TYPE_HTML = 1;
	const CONTENT_TYPE_PLAIN = 2;
	const CONTENT_TYPE_XML = 3;
	const CONTENT_TYPE_JSON = 4;
        const CONTENT_TYPE_PDF = 5;

        private $disableDebugInfo = FALSE;

	public function __construct(){
		$this->data = new DataModel_MagicHashtable();
		$this->lib = new DataModel_MagicHashtable();
		$this->meta = new DataModel_MagicHashtable(array(
			'title'=>'',
			'description'=>'',
			'keywords'=>''
		), TRUE);
		if ( defined('Configuration::PATH_LOCAL') ) $this->folder = Configuration::PATH_LOCAL.$this->folder;
	}

	public function inflate( $data = NULL, $lib = NULL, $meta = NULL ){
		if ( isset($data) ) $this->data = $data;
		if ( isset($lib) )  $this->lib = $lib;
		if ( isset($meta) ) $this->meta = $meta;
	}

	/* META and headers functions */
	public function redirect( $location ){
		header('Location: '.$location, 303);
		exit;
	}

	public function sendContentType( $value ){
		if ( headers_sent() ) return;
		switch ( $value ){
			case self::CONTENT_TYPE_HTML:
				header('Content-Type: text/html;charset=utf-8');
				break;
			case self::CONTENT_TYPE_XML:
				header('Content-Type: text/xml;charset=utf-8');
                                $this->disableDebugInfo = TRUE;
				break;
			case self::CONTENT_TYPE_JSON:
				header('Content-Type: application/json;charset=utf-8');
                                $this->disableDebugInfo = TRUE;
				break;
                        case self::CONTENT_TYPE_PDF:
                                header('Content-Type: application/pdf');
                                break;
			case self::CONTENT_TYPE_PLAIN:
			default:
				header('Content-Type: text/plain;charset=utf-8');
                                $this->disableDebugInfo = TRUE;
		}

	}
        
        public function sendFile( $data, $filename ){
            header('Content-Disposition: attachment; filename="'.  str_replace('"', '\'', $filename).'"');
            die( $data );
        }

	public function setFolder( $value ){
		if ( is_string($value) && defined('Configuration::PATH_LOCAL') ) $this->folder = Configuration::PATH_LOCAL.$value;
		else $this->folder = $value;
	}

	public function getFolder(){ return $this->folder; }

	public function append( $filename ){
		if ( is_array($filename) )
			foreach ( $filename as $row ) $this->useFile($row, false);
		else
			$this->useFile($filename, false);
	}

	public function show( $filename ){ $this->useFile($filename, true); }

	private function useFile( $filename, $finalize ){
                // Автоподключение _header и _footer если стоит +
                if ( $filename[0] == '+' && $finalize ){
                    $this->useFile( '_header', FALSE );
                    $this->useFile( substr( $filename, 1), FALSE );
                    $this->useFile( '_footer', FALSE );
                    $this->engeneerInformation();
                    exit;
                }

		if ( substr( $filename , -4)  != '.php' ) $filename .= '.php';

		$isOutputFileUsed = FALSE;
		$o = &$this;

		if ( !is_array( $this->folder ) ){
			include $this->folder.$filename;
			$isOutputFileUsed = TRUE;
		}else foreach ( $this->folder as $try ){
			if ( !file_exists( $try.$filename ) ) continue;
			include $try.$filename;
			$isOutputFileUsed = TRUE;
			break;
		}
		if ( !$isOutputFileUsed ) throw new StandardException("Template $filename not found");
		if ( $finalize ) {
			$this->engeneerInformation();
			exit;
		}
	}

	public function dump(){
		if ( !headers_sent() ) $this->sendContentType( self::CONTENT_TYPE_PLAIN );
		var_dump( $this->data, $this->lib, $this->meta );
		exit;
	}

	private function engeneerInformation(){
		if ( !$this->amIPrivileged() || $this->disableDebugInfo ) return;
		echo '<script>';
		$this->jsLog( "--- Отладочная информация" );
		$amount = count( Core::$reg->lib ) + count( Core::$reg->data );
		$cached = 0;
		foreach ( Core::$reg->lib as $row ) if ( is_object($row) && method_exists($row,'isFromCache') && $row->isFromCache() ) $cached++;
		foreach ( Core::$reg->data as $row ) if ( is_object($row) && method_exists($row,'isFromCache') && $row->isFromCache() ) $cached++;
		$percent = intval( $cached/ $amount * 100 );
		$this->jsLog("$percent% закешировано, $cached/$amount");
		if ( isset(Core::$statistics['cls_loader_calls']) )
			$this->jsLog( Core::$statistics['cls_loader_calls'].' объектов подключено' );
		$this->jsLog("Общее время генерации страницы ".number_format( (microtime(true) - Core::$statistics['start'])*1000, 1, '.', '' ).' мс.');
		$this->jsLog( "--- Справочники" );
		foreach ( Core::$reg->lib as $name=>$row ){
			if ( is_object( $row ) && $row instanceof DataModel_Abstract ){
				$this->jsLog( "$name :: ".($row->isFromCache() ? 'получен из кеша '.$row->getCacheParams().' ' : 'сгенерирован ').number_format( $row->time_lazy_initialization*1000, 2 ).'мс, '.count($row).' строк');
				$this->jsLog( $row->getDataReference() );
			} else $this->jsLog( $row );
		}
		$this->jsLog( "--- Данные" );
		foreach ( Core::$reg->data as $name=>$row ){
			if ( is_object( $row ) && $row instanceof DataModel_Abstract ){
				$this->jsLog( "$name :: ".($row->isFromCache() ? 'получен из кеша '.$row->getCacheParams().' ' : 'сгенерирован ').number_format( $row->time_lazy_initialization*1000, 2 ).'мс, '.count($row).' строк');
				$this->jsLog( $row->getDataReference() );
			} else $this->jsLog( $row );
		}
		echo '</script>';
	}

	private function jsLog( $msg ){
		if ( is_array( $msg ) || is_object( $msg ) )
			echo 'console.log('.JSON::encode( $msg ).');'."\n";
		else
			echo 'console.log("'.$msg.'");'."\n";
	}

	private function amIPrivileged(){
		if ( defined( 'Configuration::EXCEPTIONS_VIEW_IPS' ) ){
			$ips = explode( ',', Configuration::EXCEPTIONS_VIEW_IPS );
			if ( in_array( $_SERVER['REMOTE_ADDR'], $ips) ) return TRUE;
		}
		if ( defined( 'Configuration::EXCEPTIONS_VIEW_COOKIE' ) ){
			if ( isset($_COOKIE[Configuration::EXCEPTIONS_VIEW_COOKIE]) ) return TRUE;
		}
		return FALSE;
	}

}