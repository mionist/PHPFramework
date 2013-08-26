<?php

class DataModel_Manage_MOTD extends DataModel_Array{
    protected function lazyInitialization() {
        $this->data = array();
        
        $this->data[] = 'Добро пожаловать!';
        $this->data[] = 'Версия PHP '.phpversion().', время сервера '.date('d.m.y H:i:s');
    }
}