<?php

class Api_Money_Webmoney_Transaction extends Api_Money_Transaction{
    private $hashOk = FALSE;
    private $prerequest = TRUE;
    private $testMode = TRUE;
    private $amountRaw;
    private $timeRaw;
    private $modeRaw;
    private $secretKey;
    
    public $billID;
    public $source;
    public $sourceWMID;
    public $target;
    
    public $invoice; // Номер счета в системе WebMoney, выставленного Клиенту от лица Продавца в процессе проведения платежа. Является уникальным в системе WebMoney.
    public $transaction; // Номер платежа в системе WebMoney. Является уникальным в системе WebMoney. Рекомендуем Продавцу сохранять этот параметр в своей базе данных, так как он используется в некоторых дополнительных интерфейсах.
    
    public static $PREREQUEST_OK = 'YES';
    public static $PREREQUEST_FAIL = 'FAIL';
    
    public static function handlePOSTArray( $array, $secretKey ){
        if ( !is_array($array) ) throw new StandardException("Data array not provided");
        if ( !isset($array['LMI_PAYEE_PURSE']) ) throw new StandardException("Wrong data array");
        
        $t = new self();
        $t->secretKey = $secretKey;
        $t->prerequest = ( isset($array['LMI_PREREQUEST']) && $array['LMI_PREREQUEST'] == 1 );
        $t->testMode = ( isset($array['LMI_MODE']) && $array['LMI_MODE'] == 1 );
        $t->modeRaw = ( isset($array['LMI_MODE']) ? $array['LMI_MODE'] : '' );
        $t->target = $array['LMI_PAYEE_PURSE'];
        $t->source = $array['LMI_PAYER_PURSE'];
        $t->amount = (float) $array['LMI_PAYMENT_AMOUNT'];
        $t->amountRaw = $array['LMI_PAYMENT_AMOUNT'];
        $t->billID = $array['LMI_PAYMENT_NO'];
        if ( isset($array['LMI_PAYER_WM']) ) $this->sourceWMID = $array['LMI_PAYER_WM'];
        if ( isset($array['LMI_SYS_INVS_NO']) ) $this->invoice = $array['LMI_SYS_INVS_NO'];
        if ( isset($array['LMI_SYS_TRANS_NO']) ) $this->transaction = $array['LMI_SYS_TRANS_NO'];
        if ( isset($array['LMI_SYS_TRANS_DATE']) ){
            $this->timeRaw = $array['LMI_SYS_TRANS_DATE'];
            $this->timePayment = strtotime( $array['LMI_SYS_TRANS_DATE'] );
        }
        
        if ( isset($array['LMI_HASH']) )
            $t->hashOk = ( $t->getHash() == $array['LMI_HASH'] );
        
        return $t;
    }

    // Самая важная функция
    public function getHash(){
        return strtoupper(md5(
            $this->target.
            $this->amountRaw.
            $this->billID.
            $this->modeRaw.
            $this->invoice.
            $this->transaction.
            $this->timeRaw.
            $this->secretKey.
            $this->source.
            $this->sourceWMID
        ));
    }

    public function getUUID() {
        return 'Webmoney-'.$this->source.'-'.$this->invoice.'-'.$this->transaction;
    }

    public function isFinished() {
        return !$this->prerequest;
    }

    public function isSuccess() {
        return $this->hashOk;
    }

    public function isTestMode() {
        return $this->testMode;
    }
}