<?php

abstract class Api_Money_Transaction{
    
    // Unix timestamp
    public $timePayment;
    
    // Decimal
    public $amount;
    
    // String
    public $externalID;
    
    // Получить UUID операции
    public abstract function getUUID();

    public final function isOkToProcessMoney(){
        return $this->isFinished() && !$this->isTestMode() && $this->isSuccess();
    }

    // Тестовый ли режим
    public abstract function isTestMode();

    // Завершена ли текущая транзакция
    public abstract function isFinished();
    
    // Успешна ли текущая транзакция
    public abstract function isSuccess();
}