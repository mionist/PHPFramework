<?php
/**
 * @internal 
 */
class DataModel_External_Counter_AlexaRating extends DataModel_Hashtable{
    
    private $url;
    public function __construct( $url ){
	$this->url = $url;
    }
    
    protected function lazyInitialization() {
        // Отправляем запрос на alexa
        $url = "http://data.alexa.com/data?cli=10&dat=snbamz&url={$this->url}";
        $data = file_get_contents( $url );
        
        // Рейтинг
        // Очень сильно лень делать правильно - через обход XML дерева
	$this->data = array();
        $m = array();
        if (preg_match('%<POPULARITY URL="[^"]*" TEXT="([0-9]*)"%', $data, $m) ) $this->data['rating'] = $m[1];
    }
}