<?php

/**
 * Выписка из портмоне
 */

class Api_Money_Portmone_Transaction extends Api_Money_Transaction {
    public $shopBillID;
    public $shopOrderNumber;
    public $billDate;
    public $billDateTimestamp;
    public $payDate;
    public $payDateTimestamp;
    public $authCode;
    public $description;

    private static $gateway = 'https://www.portmone.com.ua/gateway/';
    
    /**
     * 
     * @param type $tsBegin - таймстамп даты начала периода
     * @param type $tsEnd - таймстамп даты конца периода
     * @param type $id - PAYEE_ID из доступа
     * @param type $login - логин портмоне
     * @param type $password - пароль портмоне
     * @throws StandardException
     */
    public static function fetchSuccessTransactions( $tsBegin, $tsEnd, $id, $login, $password ){
        if ( !function_exists('curl_exec') ) throw new StandardException("CURL not installed");
        if ( !function_exists('simplexml_load_string') ) throw new StandardException("SimpleXML not installed"); 
        
        $data = 'method=result&payee_id='.urlencode($id).
                '&login='.urlencode($login).
                '&password='.urlencode($password).
                '&status=PAYED&start_date='.urlencode(date('d.m.Y',$tsBegin)).
                '&end_date='.urlencode(date('d.m.Y',$tsEnd));
        
        // передача POST
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, self::$gateway);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.8.1.16) Gecko/20080702 Firefox/2.0.0.16');
        $resource = curl_exec($curl);
        
        // полученный xml конвертируем в кодировку UTF-8
        $text = mb_convert_encoding($resource, 'UTF-8','windows-1251');
        
        // переводим xml в обьект
        $xmlObj = simplexml_load_string($text);
        
        // Превращаем в массив
        $arrXml = self::objectsIntoArray($xmlObj);
        
        // Bugfix for single entry
        if ( isset( $arrXml['orders'], $arrXml['orders']['order'], $arrXml['orders']['order']['shop_bill_id'] )){
            $arrXml['orders']['order'] = array( $arrXml['orders']['order'] );
        }
        
        $answer = array();
        foreach ($arrXml as $row){
            if(isset($row['order'])  ){
                foreach ($row['order'] as $item){
                    if ( !isset($item['status']) || $item['status'] != 'PAYED' ) continue;
                    
                    $o = new self();
                    $o->authCode = ( isset($row['auth_code']) ? $row['auth_code'] : '' );
                    $o->billDate = $row['bill_date'];
                    $o->billDateTimestamp = strtotime( implode('-',  array_reverse(explode('.',$o->billDate))) );
                    $o->description = ( isset($row['description']) ? $row['description'] : '' );
                    $o->payDate = $row['pay_date'];
                    $o->payDateTimestamp = strtotime( implode('-',  array_reverse(explode('.',$o->payDate))) );
                    $o->shopBillID = $row['shop_bill_id'];
                    $o->shopOrderNumber = $row['shop_order_number'];
                    
                    $o->timePayment = $o->payDateTimestamp;
                    $o->externalID = $o->shopBillID;
                    $o->amount = (float) str_replace(',', '.', $row['bill_amount']);
                }
            }
        }        
        
        return $answer;
    }
    
    // функция перевода обьекта в массив(http://php.net/manual/ru/book.simplexml.php)
    private static function objectsIntoArray($arrObjData, $arrSkipIndices = array()){
        $arrData = array();
        // if input is object, convert into array
        if (is_object($arrObjData)) {
            $arrObjData = get_object_vars($arrObjData);
        }
        if (is_array($arrObjData)) {
            foreach ($arrObjData as $index => $value) {
                if (is_object($value) || is_array($value)) {
                    $value = self::objectsIntoArray($value, $arrSkipIndices); // recursive call
                }
                if (in_array($index, $arrSkipIndices)) {
                    continue;
                }
                $arrData[$index] = $value;
            }
        }
        return $arrData;
    }

    public function isFinished() {return TRUE;}
    public function isSuccess() {return TRUE;}

    public function getUUID() {
        return 'Portmone-'.$this->shopBillID.'-'.$this->payDate;
    }

    public function isTestMode() {
        return FALSE;
    }    
}