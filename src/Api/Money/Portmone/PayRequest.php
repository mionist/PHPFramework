<?php

/**
 * Класс для получения формы или параметров
 * совершения платежа portmone
 * 
 */


class Api_Money_Portmone_PayRequest extends Api_Money_PayRequest {
    
    private $id;
    private $email;
    private $url_success;
    private $url_failure;
    private $language;


    private static $uri = 'https://www.portmone.com.ua/secure/gate/pay.php';
    
    public function __construct( $id, $email, $url_success, $url_failure, $language = 'ru' ) {
        $this->id = $id;
        $this->email = $email;
        $this->url_failure = $url_failure;
        $this->url_success = $url_success;
        $this->language = $language;
    }
    
    public function getName(){
        return 'Portmone.com';
    }
    
    
    public function build(){
        $this->reset();
        $this->type = 'form';
        $this->url = self::$uri;
        
        $this->addField('PAYEE_ID', $this->id);
        $this->addField('UTF-8', 'TRUE');
        $this->addField('SHOPORDERNUMBER', htmlspecialchars( $this->getVariable('bill_id') ));
        $this->addField('BILL_AMOUNT', $this->getAmountWithRate());
        $this->addField('EMAIL', $this->email);
        $this->addField('DESCRIPTION', htmlspecialchars( $this->getVariable('description') ));
        $this->addField('SUCCESS_URL', htmlspecialchars( $this->url_success ));
        $this->addField('FAILURE_URL', htmlspecialchars( $this->url_failure ));
        $this->addField('LANG', $this->language);
    }

    public function getAmountFieldName() {
        return 'BILL_AMOUNT';
    }

}