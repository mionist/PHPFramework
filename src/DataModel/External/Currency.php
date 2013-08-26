<?php

class DataModel_External_Currency extends DataModel_Array{
    
    private static $currencies = array(
	'980'=>array('UAH'), // Наша гривня :)
	'826'=>array('GBP'), // Фунт
	'974'=>array('BYR'), // Белорусский рубль
	'840'=>array('USD'),
	'978'=>array('EUR'),
	'985'=>array('PLN'), // Польский злотый
	'643'=>array('RUB') // Российский рубль
    );
    
    private static $supported_base_currencies = array(
	'980'=>'getRateNBU'
    );
    
    private $baseCurrency;
    private $retries;
    private $date;
    
    public function __construct( $date, $baseCurrency = '980', $retries = 1 ) {
	if ( !isset( self::$supported_base_currencies[$baseCurrency] ) ) throw new StandardException("Currency $baseCurrency is not supported as base currency");
	$this->retries = $retries;
	$this->date = DateTimeWrapper::Factory( $date );
	$this->baseCurrency = $baseCurrency;
    }
    
    protected function lazyInitialization() {
	call_user_func(array($this,self::$supported_base_currencies[$this->baseCurrency]));
    }
    
    protected function getRateNBU(){
	$answer = NULL;
	while ( $this->retries-- > 0 ){
	    try{
		$answer = file_get_contents( 'http://www.bank.gov.ua/control/uk/curmetal/currency/search?formType=searchFormDate&time_step=daily&date='.$this->date->format('d.m.Y') );
		break;
	    } catch ( Exception $e ){/* Doing nothing */}
	}
	if ( !isset($answer) ) throw new StandardException('Cannot read currency rates, empty response from server');
	// Чистим ответ
	$answer = str_replace( array("\n","\t","\r",' ') , '', $answer);
	
	// Парсим ответ
	$regex = '%<tr><tdclass="cell_c">([0-9]*)</td><tdclass="cell_c">([a-z]*)</td><tdclass="cell_c">([0-9]*)</td><tdclass="cell">[^<]*</td><tdclass="cell_c">([0-9\.\,]*)</td></tr>%i';
	$m = array();
	if ( preg_match_all($regex, $answer, $m) > 0 ){
	    for ( $i=0; $i < count($m[1]); $i++ ){
		$code = $m[1][$i];
		$charcode = $m[2][$i];
		if ( !isset(self::$currencies[$code]) ) continue;
		if ( self::$currencies[$code][0] != $charcode ) continue;
		$znam = $m[3][$i];
		$prerate = $m[4][$i];
		$rate = $prerate/max(1,$znam);
		
		$this->data[$code] = $rate;
	    }
	} else {
	    throw new StandardException('Regex failed for NBU');
	}
	
    }
    
    protected function _set($offset, $value) { throw new StandardException("Not applicable"); }
    protected function _unset($offset) {throw new StandardException("Not applicable");}
    
}