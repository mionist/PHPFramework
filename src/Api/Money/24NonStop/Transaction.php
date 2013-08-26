<?php

class Api_Money_24NonStop_Transaction {
    
    const TYPE_FINDUSER = 1;
    const TYPE_PAY = 4;
    const TYPE_STATUS = 7;
    
    const STATUS_USER_NOT_FOUND = -40;
    const STATUS_INTERNAL_ERROR = -90;
    const STATUS_OK_FOR_PAY = 21;
    const STATUS_PAYMENT_COMPLETE = 22;
    const STATUS_PAYID_FAILURE = -100;
    
    /**
     * 
     * @return Api_Money_24NonStop_Transaction
     * @throws StandardException
     */
    public static function fromArray( $in, $Secret ){
        if ( !isset($in,$in['ACT']) ) throw new StandardException("Data not valid");
        $ans = new self();
        $ans->Raw = $in;
        if ( $in instanceof DataModel_Hashtable )
            $ans->Raw = $in->getArray ();
        $ans->Act = (int) self::reader($in, 'ACT');
        $ans->Amount = self::reader($in, 'PAY_AMOUNT');
        $ans->Account = self::reader($in, 'PAY_ACCOUNT');
        $ans->PayId = self::reader($in, 'PAY_ID');
        $ans->CheckNo = self::reader($in, 'RECEIPT_NUM');
        $ans->ServiceId = self::reader($in, 'SERVICE_ID');
        $ans->TradePoint = self::reader($in, 'TRADE_POINT');
        $ans->Sign = self::reader($in, 'SIGN');
        $ans->Secret = $Secret;
        $ans->validate();
        return $ans;
    }
    
    private static function reader( $in, $field ){
        if ( !isset($in[$field]) ) return NULL;
        return $in[$field];
    }
    
    private function __construct() {}
    
    public $Act; // Код операции
    public $Amount; // Количество денег
    public $Account; // Идентификатор клиента
    public $PayId; // Идентификатор транзации
    public $CheckNo; // Номер черка
    public $ServiceId; // Идентификатор сервиса
    public $TradePoint; // Идентификатор торговой точки
    
    private $Raw;
    private $Sign;
    private $Secret;

    public $DetectedAccountId = NULL; // Определённый аккаунт
    public $DetectedAccountFIO = NULL;
    public $DetectedAccountBalance = NULL;
    
    
    private function validate(){
        if ( $this->Act != self::TYPE_FINDUSER && $this->Act != self::TYPE_PAY && $this->Act != self::TYPE_STATUS )
            throw new StandardException("ACT Not valid");
        // Checking hash
        $hash_md5 = strtoupper( md5( $this->Act.'_'.$this->Account.'_'.$this->ServiceId.'_'.$this->PayId.'_'.$this->Secret ) );
        $hash_sha = strtoupper(sha1( $this->Act.'_'.$this->Account.'_'.$this->ServiceId.'_'.$this->PayId.'_'.$this->Secret ) );
        if ( $this->Sign != $hash_md5 && $this->Sign != $hash_sha )
            throw new StandardException("Sign Not valid");
        // Checking amount
        $this->Amount = number_format( 0 + str_replace( ',','.', $this->Amount), 2, '.', 0 );
        if ( $this->Amount < 1 && $this->Act == self::TYPE_PAY )
            throw new StandardException("Wrong amount - {$this->Amount}");
            
    }
    
    public function getUUID(){
        return '24NS-'.$this->PayId;
    }
    
    public function getPackedData(){
        return JSON::encode( $this->Raw );
    }
    
    /**
     * Сообщение об ошибке
     * @param int $statusCode Код ошибки
     */
    public function finalAnswerError( $statusCode ){
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
        echo "<pay-response>";
        echo "<status_code>$statusCode</status_code>";
        echo "<time_stamp>".date('d.m.Y H:i:s')."</time_stamp>";
        echo "</pay-response>";
        exit;
    }
    
    /**
     * Ответ на ACT = 1
     */
    public function finalAnswerForCheck(){
        if ( $this->Act != self::TYPE_FINDUSER )
            return $this->finalAnswerError ( self::STATUS_INTERNAL_ERROR );
        if ( $this->DetectedAccountId == NULL )
            return $this->finalAnswerError( self::STATUS_USER_NOT_FOUND );
        
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
        echo "<pay-response>";
        echo "<account>".$this->Account."</account>";
        echo "<service_id>{$this->ServiceId}</service_id>";
        if ( $this->DetectedAccountBalance != NULL )
            echo "<balance>{$this->DetectedAccountBalance}</balance>";
        if ( $this->DetectedAccountFIO != NULL )
            echo "<name>{$this->DetectedAccountFIO}</name>";
        echo "<status_code>".self::STATUS_OK_FOR_PAY."</status_code>";
        echo "<time_stamp>".date('d.m.Y H:i:s')."</time_stamp>";
        echo "</pay-response>";
        exit;
    }
    
    /**
     * Ответ на ACT = 4
     */
    public function finalAnswerForPay(){
        if ( $this->Act != self::TYPE_PAY )
            return $this->finalAnswerError ( self::STATUS_INTERNAL_ERROR );
        if ( $this->DetectedAccountId == NULL )
            return $this->finalAnswerError( self::STATUS_USER_NOT_FOUND );
        
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
        echo "<pay-response>";
        echo "<pay_id>{$this->PayId}</pay_id>";
        echo "<service_id>{$this->ServiceId}</service_id>";
        echo "<amount>{$this->Amount}</amount>";
        echo "<description></description>";
        echo "<status_code>".self::STATUS_PAYMENT_COMPLETE."</status_code>";
        echo "<time_stamp>".date('d.m.Y H:i:s')."</time_stamp>";
        echo "</pay-response>";
        exit;
    }
    
    public function finalAnswerForTransactionCheck( $amount, $timepay ){
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
        echo "<pay-response>";
        echo "<transaction>";
        echo "<pay_id>{$this->PayId}</pay_id>";
        echo "<service_id>{$this->ServiceId}</service_id>";
        echo "<amount>$amount</amount>";
        echo "<status>111</status>";
        echo "<time_stamp>$timepay</time_stamp>";
        echo "</transaction>";
        echo "<status_code>11</status_code>";
        echo "<time_stamp>".date('d.m.Y H:i:s')."</time_stamp>";
        echo "</pay-response>";
        exit;
    }
    
}