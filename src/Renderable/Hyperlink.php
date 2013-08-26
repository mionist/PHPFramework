<?php

/**
 * Оболочка для представления html ссылок
 * Пример:
 * 
 * echo " or use ".( new Renderable_Hyperlink( "Google", "http://google.com/", Renderable_Item::CONTEXT_HTML ) )." search engine";
 * 
 * @author gotter
 */

class Renderable_Hyperlink extends Renderable_Item{

    /**
     * URL адрес ссылки
     * @var string 
     */
    protected $url;
    /**
     * Требуется ли обрезание длины текста ссылки
     * 
     * @var int 
     */
    protected $trim;
    
    /**
     * 
     * @param string $text Текст ссылки
     * @param string $url Адрес ссылки
     * @param int $context Требуемый контекст
     * @param int $trim Количество символов, после которого будет произведено обрезание текста ссылки
     */
    public function __construct( $text, $url, $context = NULL, $trim = NULL ){
	$this->url = $url;
	$this->trim = $trim;
	parent::__construct( $text, $context );
    }
    
    /**
     * @override
     * @param int $context
     * @return string
     */
    protected function produceContext( $context ){
        switch ( (int) $context ){
            case Renderable_Item::CONTEXT_JSON: // Запрашивается json
                return '{"link":'.JSON::encode( array( 'text' => $this->data, 'url' => $this->url ) ).'}';
            case Renderable_Item::CONTEXT_HTML: // Запрашивается html
                $text = $this->data;
                if ( isset($this->trim) && $this->trim > 0 && mb_strlen($text) > $this->trim ) $text = mb_substr ($text, 0, $this->trim-3).'...';
                return '<a href="'.$this->url.'">'.$text.'</a>';
            default: // Принимаем plaintext
                return $this->data;
        }
    }
}