<?php

abstract class Api_Money_PayRequest{
    public $url;
    public $type;
    public $fields = array();
    
    protected $variables = array();
    
    private $rate = 1;
    
    public abstract function build();
    public abstract function getName();
    public abstract function getAmountFieldName();
    
    /**
     * Это - сколько пользователь хочет получить
     */
    public final function setAmount( $amount ){
        $this->variables['amount'] = (float) $amount;
    }
    
    public final function getAmount(){
        if ( !isset($this->variables['amount']) ) throw new StandardException("Amount not set");
        return $this->variables['amount'];
    }
    
    public final function getAmountWithRate(){
        return number_format( $this->getAmount() * $this->rate, 2, '.', '');
    }


    public final function setRate( $value ){
        $this->rate = $value;
    }

    public function setUniqueBillNumber( $value ){
        $this->variables['bill_id'] = $value;
    }

    
    public final function setDescription( $text ){
        $this->variables['description'] = trim( $text );
    }
     
    protected final function addField($key, $value){ $this->fields[$key] = $value; }
    protected final function setVar( $key, $value ) { $this->setVariable($key, $value); }
    protected final function setVariable( $key, $value ){
        $this->variables[$key] = $value;
    }
    
    public final function getVariable( $key, $default = '' ){
        if ( !isset($this->variables[$key]) ) return $default;
        return $this->variables[$key];
    }
    
    public final function reset(){
        $this->url = NULL;
        $this->type = NULL;
        $this->fields = array();
    }
}