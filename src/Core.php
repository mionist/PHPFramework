<?php

class Core{
	
	const MODE_HTTP 		= 1;
	const MODE_AJAX			= 2;
	const MODE_CLI			= 3;
	
	/**
	 * 
	 * @var Auth_Abstract
	 */
	public static $auth;
	public static $in;
	public static $statistics;
	public static $reg;
	public static $registry;
	
        /**
         *
         * @var Standard_CoreFS 
         */
        public static $fs; // Файловая система движка
        
	/**
         *
         * @var Output
         */
	public static $out;
	
	public static $mode		= self::MODE_HTTP;
	
	private static $handlers = array();
	private static $database;
	
	private static $i18n;
	private static $i18n_languages;
	private static $i18n_languages_in_priority;
	private static $i18n_language;
	private static $i18n_default;
        
	public static function ClassLoader( $className ){
		// Подготавливаем имя класса
		if ( strpos($className, '_') !== FALSE ) $fileName = str_replace('_', '/', $className);
		else $fileName = $className;
		$fileName .= '.php';
		
		if ( isset(self::$statistics) ) self::$statistics->replace('cls_loader_calls',  self::$statistics->getInt('cls_loader_calls') + 1 );
		
                if ( !self::$fs->find( Standard_CoreFS::TYPE_SOURCE , $fileName ) ) throw new LoaderStandardException($className);
                self::$fs->load( Standard_CoreFS::TYPE_SOURCE , $fileName);
	}

        public static function Init(){
                // Инициализируем файловую систему
                self::$fs = new Standard_CoreFS();
                self::$fs->registerPath( Standard_CoreFS::TYPE_SOURCE , Configuration::PATH_LOCAL.'functions/' );
                self::$fs->registerPath( Standard_CoreFS::TYPE_SOURCE , Configuration::PATH_LOCAL.'classes/' );
                self::$fs->registerPath( Standard_CoreFS::TYPE_SOURCE , Configuration::PATH_LOCAL.'src/' );
                self::$fs->registerPath( Standard_CoreFS::TYPE_SOURCE , Configuration::PATH_FRAMEWORK.'src/' );
                self::$fs->registerPath( Standard_CoreFS::TYPE_BRICKS , Configuration::PATH_LOCAL.'config/bricks/');
                
		// Инициализируем статистику
		self::$statistics = new DataModel_Hashtable( NULL, true );
		self::$statistics->replace('start', microtime(true));
		
		// Инициализируем хранилище данных
		self::$registry = new CoreRegistry();
		self::$reg = self::$registry; // Shortcut
		self::$registry->data = new DataModel_MagicHashtable( array() );
		self::$registry->lib  = new DataModel_MagicHashtable( array() );
		self::$registry->meta = new DataModel_MagicHashtable( array(
			'title'=>( new DataModel_Array() ),
			'keywords'=>( new DataModel_Array() ),
			'open_graph'=>( new DataModel_Hashtable() ),
			'description'=>'',
			'index'=>TRUE,
			'follow'=>TRUE
		), TRUE );
		
		// Определяем режим работы
		if ( substr(php_sapi_name(),0,3) === 'cli' ){
			self::$mode = self::MODE_CLI;
		}
		
		// Обрабатываем magic_quotes
		if ( get_magic_quotes_gpc() ){
			$process = array(&$_GET, &$_POST);
			while (list($key, $val) = each($process)) {
				foreach ($val as $k => $v) {
					unset($process[$key][$k]);
					if (is_array($v)) {
						$process[$key][stripslashes($k)] = $v;
						$process[] = &$process[$key][stripslashes($k)];
					} else {
						$process[$key][stripslashes($k)] = stripslashes($v);
					}
				}
			}
			unset($process);
		}
		
		// Заполняем входящие параметры
		self::$in = new DataModel_InputParameters( $_REQUEST );
		self::$in->_cookie = new DataModel_Hashtable( $_COOKIE );
		/*if ( isset($_COOKIE['PHPSESSID']) )*/ session_start();
		self::$in->_post = new DataModel_Hashtable( $_POST );
		self::$in->_get = new DataModel_Hashtable( $_GET );
		
		if ( self::$mode == self::MODE_CLI ){
			self::$in->_session = new DataModel_Hashtable(); // Нет _SESSION в CLI
			self::$in->_navigation = new DataModel_Array( $_SERVER['argv'] );
			self::$in->_domain = new DataModel_Array(array());
		}else{
			self::$in->_session = new DataModel_Hashtable( isset($_SESSION) ? $_SESSION : NULL );
			$uri = urldecode( $_SERVER['REQUEST_URI'] );
			if ( strpos($uri, '?') !== false ) $uri = substr($uri, 0, strpos($uri, '?'));
			self::$in->_navigation = new DataModel_Array( explode('/', $uri) );
			$host = strtolower($_SERVER['HTTP_HOST']);
			if ( substr( $host, 0, 4) == 'www.' ) $host = substr($host, 4);
			self::$in->_domain = new DataModel_Array( explode('.', $host) ); 
		}
		
		self::$out = new Output();
		// Линкуем объекты
		self::$out->inflate( 
			self::$registry->data,
			self::$registry->lib,
			self::$registry->meta 
		); 
		
		// Извините парни, но _GET, _POST, _COOKIE вы уже не увидите
		unset( $_GET, $_POST );
		
		// Определяем язык
		self::detectLanguage();
		
		// Насыщаем URL
		if ( Configuration::I18N && Configuration::I18N_IN_URL ) Renderable_URL::$prefixes = array( self::getI18NLanguage() );
		
		// Подключаем авторизацию
		if ( method_exists( 'Configuration' , 'getAuthenticator') ) {
			self::$auth = Configuration::getAuthenticator();
			if ( self::$out->isUser = self::$auth->isHere() )
				self::$out->isAdmin = self::$auth->isPrivileged();
		}
	}
	
	public static function hasI18N(){ return ( isset(self::$i18n) && self::$i18n ); }
	public static function getI18NLanguagesInPriority( $limit = NULL ){
		if ( !isset( $limit ) ) return self::$i18n_languages_in_priority;
		return array_slice(self::$i18n_languages_in_priority, 0, $limit);
	}
	public static function getI18NLanguages(){ return self::$i18n_languages; }
	public static function getI18NLanguage(){ return self::$i18n_language; }
	private static function detectLanguage(){
		if ( Configuration::I18N === FALSE ) return; // Отключено
		
		self::$i18n_languages = array_map('trim', explode(',', strtolower(Configuration::I18N_LANGUAGES)));
		if ( count(self::$i18n_languages) < 2 ) return; // Один язык
		self::$i18n_default = trim(strtolower( Configuration::I18N_DEFAULT ));
		self::$i18n_language = self::$i18n_default;
		self::$i18n_languages_in_priority = array();
		self::$i18n_languages_in_priority[] = self::$i18n_default;
		
		// Есть ряд URL, для которых не делается проверка языка
		if ( self::$in->_navigation[1] != '' ){
			$compare = strtolower( self::$in->_navigation[1] );
			if ( $compare == 'robots.txt' ) return;
			if ( $compare == 'sitemap.xml' ) return;
			if ( $compare == Configuration::MANAGE_URL ) return;
		}
		
		
		$found = false;
		// Этап 0 - куки
		if ( self::$in->_cookie->valueNotEmpty('lang') && in_array(self::$in->_cookie->valueNotEmpty('lang'), self::$i18n_languages) ){
			// В куках лежал язык, отлично!
			self::$i18n_language = self::$in->_cookie->getString('lang');
			$found = true;
		}
		// Этап 1 - строка URL
		if ( !$found && Configuration::I18N_IN_URL && isset(self::$in->_navigation[1]) ){
			$suggest = self::$in->_navigation[1];
			if ( in_array($suggest, self::$i18n_languages) ){
				self::$i18n_language = $suggest;
				$found = true;
			}
			// Между прочим, нужно убрать упоминание о языке ;)
			self::$in->_navigation = new DataModel_Array( array_slice( self::$in->_navigation->getArray(), 1) );
		}
		
		// Этап 2 - браузер
		if ( !$found && self::$mode != self::MODE_CLI && isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ){
			$requested = explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
			foreach ( $requested as $row ){
				$two = strtolower( substr( trim( $row ), 0, 2 ) );
				if ( in_array( $two, self::$i18n_languages ) ){
					self::$i18n_language = $two;
					break;
				}
			}
		}
		
		// Выстраиваем приоритет языков
		self::$i18n_languages_in_priority = array();
		self::$i18n_languages_in_priority[] = self::$i18n_language;
		if ( self::$i18n_language != self::$i18n_default ) self::$i18n_languages_in_priority[] = self::$i18n_default;
		foreach ( self::$i18n_languages as $row ) if ( !in_array( $row , self::$i18n_languages_in_priority) ) self::$i18n_languages_in_priority[] = $row; 
	}
	
	/**
	 *
	 * @return Dbx
	 */
	public static function getDatabase(){
		if ( !isset(self::$database) ){
			self::$database = new Dbx( include Configuration::PATH_LOCAL.'config/Database.php' );
		}
		return self::$database;
	}
	
	public static function castEvent( $eventID ){
		if ( StandardEventReciever::START === $eventID ){
			// Автоопределение
			if ( self::$mode == self::MODE_CLI ) self::start( StandardEventReciever::START_CLI );
			else if ( self::$in->_navigation[1] == 'ajax' || self::$in->_navigation[1] == 'a' ) self::start( StandardEventReciever::START_AJAX );
			else if ( self::$in->_navigation[1] == Configuration::MANAGE_URL ) {
				if ( self::$in->_navigation[2] == 'ajax' || self::$in->_navigation[2] == 'a' )
					self::start( StandardEventReciever::START_ADMIN_AJAX );
				else 
					self::start( StandardEventReciever::START_ADMIN );
			}
			else self::start( StandardEventReciever::START_HTML );
			return;
		}
		elseif ( StandardEventReciever::START_CLI === $eventID ) self::start($eventID);
		elseif ( StandardEventReciever::START_AJAX === $eventID ) self::start($eventID);
		elseif ( StandardEventReciever::START_HTML === $eventID ) self::start($eventID);
	}
	
	private static function start( $eventID ){
		
		// В случае админки убираем лишнее
		if ( $eventID === StandardEventReciever::START_ADMIN ){
			Renderable_URL::$prefixes = array(Configuration::MANAGE_URL);
			self::$in->_navigation = new DataModel_Array( array_slice( self::$in->_navigation->getArray(), 1) );
		}

		// В случае AJX админки убираем ещё больше лишнего
		if ( $eventID === StandardEventReciever::START_ADMIN_AJAX ){
			Renderable_URL::$prefixes = array(Configuration::MANAGE_URL);
			self::$in->_navigation = new DataModel_Array( array_slice( self::$in->_navigation->getArray(), 2) );
		}
		
		// В случае ajax убираем лишнее
		if ( $eventID === StandardEventReciever::START_AJAX ){
			self::$mode = self::MODE_AJAX;
			self::$in->_navigation = new DataModel_Array( array_slice( self::$in->_navigation->getArray(), 1) );
		}
		
		try{
			self::getEventHandler($eventID)->castEvent($eventID);
		}  catch ( Exception_404 $e ){
			Core::$out->show('404');
		} catch ( Exception $e ){
			new ExceptionRenderer( $e );
		}
	}
	
	
	public static function bindEvent( $eventID, StandardEventReciever $classObject ){
		self::$handlers[$eventID] = $classObject;
	}
	
	/**
	 *
	 * @return StandardEventReciever
	 */
	public static function getEventHandler( $eventID ){
		if ( isset( self::$handlers[$eventID] ) ) return self::$handlers[$eventID];
		
		// Админка
		if ( $eventID === StandardEventReciever::START_ADMIN || $eventID === StandardEventReciever::START_ADMIN_AJAX ){
			if ( !isset( self::$handlers['default-admin'] ) ) self::$handlers['default-admin'] = new Controller_Standard_Manage();
			return self::$handlers['default-admin'];
		}
		
		// AJAX
		if ( $eventID === StandardEventReciever::START_AJAX ){
			if ( !isset( self::$handlers['default-ajax'] ) ) self::$handlers['default-ajax'] = new Controller_Standard_AJAX();
			return self::$handlers['default-ajax'];
		}
		
		// Остальное
		if ( !isset( self::$handlers['default'] ) ) self::$handlers['default'] = new Controller_Standard_Page();
		return self::$handlers['default'];
	}
	
	
	private static $loadedTrirdPartyModules = array();
	public static function Require3rdParty( $package, $filenames ){
		$package_folder = '3rdparty/'.str_replace('.', '/', $package).'/';
		if ( !is_array($filenames) ) $filenames = array($filenames);
		
		$sandbox = FALSE;
		
		foreach ( $filenames as $row ){
			if ( isset( self::$loadedTrirdPartyModules[$package_folder.$row] ) ) continue;
			
			// Разворачиваем песочницу
			if ( $sandbox === FALSE ){
				$sandbox = ini_get('include_path');
				ini_set('include_path', Configuration::PATH_FRAMEWORK.$package_folder.':'.$sandbox);
			}
			self::$loadedTrirdPartyModules[$package_folder.$row] = TRUE;
			include Configuration::PATH_FRAMEWORK.$package_folder.$row;
		}
		
		// Сворачиваем песочницу
		if ( $sandbox !== FALSE ) ini_set( 'include_path', $sandbox );
	}
	
	/**
	 * 
	 * @return Manage_SaveBundle
	 */
	public static function getSaveBundle(){
		if ( !self::$auth->isHere() ) throw new StandardException("User not found");
		if ( !self::$auth->isPrivileged() ) throw new StandardException("User not privileged");
		return new Manage_SaveBundle( self::$auth->getUID(), ( defined('Configuration::ADMIN_LOGGING_TABLE') ? Configuration::ADMIN_LOGGING_TABLE : NULL ) );
	}
}

class CoreRegistry{
	public $data;
	public $lib;
	public $meta;
}