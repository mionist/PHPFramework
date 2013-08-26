<?php
/**
 * @ignore
 * @internal
 */

class CRM_XGate_Connector {

	private $url = 'https://crm.wnet.ua/x.php';
	private $user;
	private $password;
	private $module;
	private $version = '0.1';

	private $verbose = FALSE;

	// CURL-specific data
	private static $default_user_agent = "Mozilla/5.0 (Windows; U;Windows NT 5.1; ru; rv:1.8.0.9) Gecko/20061206 Firefox/1.5.0.9";
	private static $default_headers = array(
        "Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5",
        "Accept-Language: ru-ru,ru;q=0.7,en-us;q=0.5,en;q=0.3",
        "Accept-Charset: windows-1251,utf-8;q=0.7,*;q=0.7",
        "Keep-Alive: 300");
	private static $default_followlocation = 1;
	private static $default_maxredirs = 10;
	private static $default_timeout = 10;

	// BLOWFISH-specific data
	private $handler = false;
	private $iv = "aa3e2b76";

	public function __construct( $APIUsername, $APIPassword, $module, $version = 0.1)
	{
		$this->user = $APIUsername;
		$this->password = $APIPassword;
		$this->module = $module;
		$this->version = $version;

		// Checking needed functions
		if (!function_exists('json_decode') || !function_exists('json_encode')){
			throw new XGateAPILevelException('No JSON functions exist');
		}
		if (
			!function_exists('mcrypt_generic_init') ||
			!function_exists('mcrypt_generic') ||
			!function_exists('mcrypt_generic_deinit') ||
			!function_exists('mcrypt_module_open') ||
			!function_exists('mcrypt_create_iv')
		){
			throw new XGateAPILevelException('No MCrypt functions exist');
		}
		if (
			!function_exists('curl_init') ||
			!function_exists('curl_setopt') ||
			!function_exists('curl_exec') ||
			!function_exists('curl_error') ||
			!function_exists('curl_errno') ||
			!function_exists('curl_close')
		){
			throw new XGateAPILevelException('No CURL functions exist');
		}
	}

	public function __call($function,$args){
		return $this->_callFunction($function,$args);
	}

	public function debug(){ $this->verbose = TRUE; return $this; }
	public function verbose(){ return $this->debug(); }

	protected function _callFunction($function, $args = ''){
		$result = $this->_makeCall(array(array(
				'name' => $function,
				'args' => $args
			)));

		if (!is_array($result) || !is_array($result[0])){
			throw new XGateWrongPacketException();
		}

		$result = $result[0];
		switch ($result['resultcode'])
		{
			case 1:
				return $result['data'];
				break;
			case 200:
				throw new XGateFuncLevelException($result['description']);
				break;
			case 201:
				throw new XGateNoFuncFoundException($result['name'],$this->module);
				break;
			case 202:
				throw new XGateWrongParamsException($result['name']);
				break;
			case 203:
				throw new XGateTempErrorException();
				break;
			case 204:
				throw new XGateNoObjectException();
				break;
			case 205:
				throw new XGateCantProcessException();
				break;
			case 206:
				throw new XGAteNotImplementedException();
				break;
			// Custom exception: it could be a 300-something custom function exception
			// Or some non-XGate or unknown XGate exception. If we're familiar with it - rethrow it.
			// Otherwise - throw a generic Function Level Exception
			default:
				if (class_exists($result['exception'], FALSE) && is_subclass_of($result['exception'],'XGateCustomFuncException'))
				{
					throw new $result['exception']();
				}
				else
				{
					throw new XGateFuncLevelException('Unexpected error',$result['resultcode']);
				}
		}
	}

	protected function _makeCall($functions){
		// Checking wether we have connection data set
		if (!isset($this->url))
		{
			throw new XGateAPILevelException('Connection parameters not set!');
		}

		// Processing functions array
		// crypting and packing
		$iv = self::_BF_generateIV();
		$funcdata = base64_encode($this->_BF_crypt(json_encode($functions),$this->password,$iv));
		$iv = base64_encode($iv);

		// Forming post
		$array = array(
			'login' => $this->user,
			'version' => $this->version,
			'module' => $this->module,
			'data' => $funcdata,
			'iv' => $iv
		);
		$data = http_build_query($array);

		$curl = curl_init();

		curl_setopt ( $curl , CURLOPT_URL, $this->url);
		curl_setopt ( $curl , CURLOPT_RETURNTRANSFER , 1 );
		curl_setopt ( $curl , CURLOPT_USERAGENT, self::$default_user_agent );
		curl_setopt ( $curl , CURLOPT_HTTPHEADER, self::$default_headers );
                curl_setopt ( $curl , CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt ( $curl , CURLOPT_HEADER , 0 );
		curl_setopt ( $curl , CURLOPT_FOLLOWLOCATION, self::$default_followlocation) ;
		curl_setopt ( $curl , CURLOPT_MAXREDIRS, self::$default_maxredirs );
		curl_setopt ( $curl , CURLOPT_TIMEOUT, self::$default_timeout );
		curl_setopt ( $curl , CURLOPT_POST, 1 );
		curl_setopt ( $curl , CURLOPT_POSTFIELDS, $data );

		$response = curl_exec( $curl );
		if ( $this->verbose ) var_dump( $response );
		$error = curl_error( $curl );
		$errno = curl_errno( $curl );
		curl_close($curl);

		if ($errno || $error){
			throw new XGateAPILevelException("CURL error: ".$error,$errno);
		}

		// Trying to decode stuff
		$decoded = json_decode($response,true);
		if (!is_array($decoded)){
			// We could not decode answer - some weird shit happened
			throw new XGateAPILevelException("Could not decode answer");
		}

		// Checking wether we have an API level error
		if (isset($decoded['code']) && isset($decoded['message']) && isset($decoded['exception'])){
			if (class_exists($decoded['exception'], FALSE)) {
				throw new XGateServerAPILevelException('Server side exception "'.$decoded['exception'].'": '.$decoded['message'],$decoded['code']);
			}
		}

		// Checking for another weird stuff - not an api error and not a correct answer
		if (!isset($decoded['data']) || !isset($decoded['iv'])){
			throw new XGateWrongPacketException();
		}

		// All seems OK - trying to decrypt data
		$decrypted = json_decode($this->_BF_decrypt(base64_decode($decoded['data']),$this->password,base64_decode($decoded['iv'])),true);
		if (!is_array($decrypted)){
			throw new XGateWrongPasswordException("Could not decrypt response. Password is incorrect.");
		}

		// returning
		return $decrypted;
	}

	// BLOWFISH functions

	private function _BF_init(){
		if ($this->handler === false)
		{
			$this->handler = mcrypt_module_open(MCRYPT_BLOWFISH, '', MCRYPT_MODE_CBC, '');
		}
	}

	private static function _BF_pkcs5_pad ($data, $blocksize){
    	$pad = $blocksize - (strlen($data) % $blocksize);
    	return $data . str_repeat(chr($pad), $pad);
	}

	private static function _BF_pkcs5_unpad($data){
	    $pad = ord($data{strlen($data)-1});
	    if ($pad > strlen($data)) return false;
	    if (strspn($data, chr($pad), strlen($data) - $pad) != $pad) return false;
	    return substr($data, 0, -1 * $pad);
	}

	private function _BF_crypt($data,$key,$iv = false){
		$this->_BF_init();

		if ($iv !== false){
			self::_BF_checkIV ($iv);
			$this->iv = $iv;
		}

		$data = self::_BF_pkcs5_pad($data,8);

		mcrypt_generic_init($this->handler, $key, $this->iv);
		$data = mcrypt_generic($this->handler, $data);
		mcrypt_generic_deinit($this->handler);

		return $data;
	}

	private function _BF_decrypt($data,$key,$iv = false){
		$this->_BF_init();

		if ($iv !== false)
		{
			self::_BF_checkIV ($iv);
			$this->iv = $iv;
		}

		mcrypt_generic_init($this->handler, $key, $this->iv);
		$data = mdecrypt_generic($this->handler, $data);
		mcrypt_generic_deinit($this->handler);

		$data = self::_BF_pkcs5_unpad($data);
		return $data;
	}

	private static function _BF_checkIV ($iv){
		if (strlen($iv) != 8)
		{
			throw new XGateWrongBFIVLengthException("Wrong BF IV length. Needed 8 bytes, provided ".strlen($iv));
		}
	}

	private static function _BF_generateIV(){
		return mcrypt_create_iv(8);
	}
}

/**
 * Base API level exception for XGate
 * @author atomic
 *
 */
class XGateAPILevelException extends Exception {
	public function __construct($message,$code = 100){
		parent::__construct($message,$code);
	}
}
class XGateServerAPILevelException extends Exception {}
class XGateWrongPacketException extends XGateAPILevelException {
	public function __construct(){
		parent::__construct('Invalid packet recieved',101);
	}
}
class XGateWrongPasswordException extends XGateAPILevelException {
	public function __construct(){
		parent::__construct('Wrong password',103);
	}
}
class XGateWrongBFIVLengthException extends XGateAPILevelException {
	public function __construct($num){
		parent::__construct('Wrong BF IV length. Provided: '.(int)$num.', needed: 8',105);
	}
}

/**
 * Base Function level exception for XGate
 * @author atomic
 *
 */
class XGateFuncLevelException extends XGateAPILevelException {
	public function __construct($message,$code = 200){
		parent::__construct($message,$code);
	}
}
class XGateNoFuncFoundException extends XGateFuncLevelException {
	public function __construct($function,$module){
		parent::__construct('Function "'.$function.'" not found in module "'.$module.'"',201);
	}
}
class XGateWrongParamsException extends XGateFuncLevelException {
	public function __construct($function){
		parent::__construct('Wrong parameters for function "'.$function.'"',202);
	}
}
class XGateTempErrorException extends XGateFuncLevelException {
	public function __construct(){
		parent::__construct('Temporary problems. Repeat query later',203);
	}
}
class XGateNoObjectException extends XGateFuncLevelException {
	public function __construct(){
		parent::__construct('Object not found',204);
	}
}
class XGateCantProcessException extends XGateFuncLevelException {
	public function __construct(){
		parent::__construct('Action can not be performed',205);
	}
}
class XGateNotImplementedException extends XGateFuncLevelException {
	public function __construct() {
		parent::__construct('Function not implemented', 206);
	}
}

class XGateWrongVersionException extends XGateAPILevelException {
	public function __construct($ver)
	{
		parent::__construct('API version '.$ver.' is not supported',102);
	}
}
//class XGateWrongModuleException extends XGateAPILevelException {
//	public function __construct(){
//		parent::__construct('Wrong module',104);
//	}
//}
