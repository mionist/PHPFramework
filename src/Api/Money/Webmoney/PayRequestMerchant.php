<?php

class Api_Money_Webmoney_PayRequestMerchant extends Api_Money_PayRequest{
    
    private static $uri = 'https://merchant.webmoney.ru/lmi/payment.asp';
    
    private $purse;
    private $url_success;
    private $url_failure;
    private $url_result;
    public function __construct( $ourPurse, $url_success, $url_failure, $url_result ) {
        $this->purse = $ourPurse;
        $this->url_failure = $url_failure;
        $this->url_success = $url_success;
        $this->url_result = $url_result;
    }


    public function getName(){
        return 'Webmoney';
    }


    public function build() {
        $this->reset();
        $this->type = 'form';
        $this->url = self::$uri;
        
        $this->addField( 'LMI_PAYEE_PURSE', $this->purse );
        $this->addField( 'LMI_PAYMENT_AMOUNT', $this->getAmountWithRate() );
        $this->addField( 'LMI_PAYMENT_NO', $this->getVariable('bill_id','') );
        $this->addField( 'LMI_PAYMENT_DESC_BASE64', base64_encode( $this->getVariable('description','') ) );
        $this->addField( 'LMI_RESULT_URL', htmlspecialchars($this->url_result) );
        $this->addField( 'LMI_SUCCESS_URL', htmlspecialchars($this->url_success) );
        $this->addField( 'LMI_FAIL_URL', htmlspecialchars($this->url_failure) );
        $this->addField( 'LMI_SUCCESS_METHOD', 1 );
        $this->addField( 'LMI_FAIL_METHOD', 1 );
    }

    public function getAmountFieldName() {
        return 'LMI_PAYMENT_AMOUNT';
    }
}