<?php
/**
 * 
 * Базовый код для работы с элементами, реализущими представление 
 * самих себя в зависимости от запрашиваемого контекста.
 * 
 * 
 * @author gotter
 */

class Renderable_Item{
    
        // Константы
    
    	const CONTEXT_PLAINTEXT                         = 0; // Текстовый контекст
	const CONTEXT_HTML				= 1; // HTML-контекст
	const CONTEXT_JSON				= 2; // JSON-контекст
	
        // Static members
        
	public static $GLOBAL_RENDER_CONTEXT = self::CONTEXT_HTML; // Контекст по-умолчанию
	
        // Свойства
        
        /**
         * Данные объекта
         */
	protected $data                                 = NULL;
        /**
         * Запрашиваемый контекст
         */
	protected $context                              = NULL;
	
        // Конструктор
        /**
         * 
         * @param mixed $data Данные
         * @param int $context Требуемый контекст
         */
	public function __construct( $data = NULL, $context = NULL ){
		if ( isset($data) ) $this->data = $data;
		if ( isset($context) ) $this->context = (int) $context;
	}
	
        // Public members
        
        /**
         * Установка контента
         * 
         * @param int $context
         */
	public final function setContext( $context ){ $this->context = (int) $context; }
	
        /**
         * Получение значения объекта в желаемом контенте
         * 
         * @param int $context
         * @return string
         */
	public function getContext( $context = NULL ){
		return $this->produceContext( $this->findContext($context) );
	}
	
        /**
         * Неявный вызов getContext(NULL)
         * 
         * @override
         * @return string
         */
        public final function __toString(){ return $this->getContext(); }
        
        // Protected members
        
        /**
         * Автоопределение контекста
         * Приоритет:
         *  аргумент функции -> this.context -> static::context
         * 
         * @param int $context
         * @return int
         */
	protected final function findContext( $context ){
		if ( isset($context) ) return (int) $context;
		elseif ( isset($this->context) ) return $this->context;
		return self::$GLOBAL_RENDER_CONTEXT;
	}
	
	/**
         * Метод, генерирующий представление объекта в требуемом контексте
         * Главная функция для переопредения в наследниках
         * 
         * @param int $contextIgnored
         * @return string
         */
	protected function produceContext( $context ){ return "".$this->data; }
	
	
}