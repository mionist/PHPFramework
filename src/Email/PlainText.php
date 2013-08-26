<?php

class Email_PlainText {
	
	private static $allowedCharsets = array('utf-8','cp1251','windows-1251','koi8-r');
	
	protected $recipients = array();
	protected $subject = '';
	protected $body = '';
	protected $from;
	
	protected $ID_message;
	protected $ID_replyToMessage;
	
	protected $charset = 'utf-8';
	
	// Добавление получателя
	public function addRecipient( $recipient ){
		if ( is_array( $recipient ) ){
			foreach( $recipient as $row ) $this->addRecipient($row);
			return $this;
		}
		if ( strpos( $recipient, ',') !== FALSE ){
			foreach( explode(',', $recipient) as $row ) $this->addRecipient($row);
			return $this;
		}
		if ( strpos( $recipient, ';') !== FALSE ){
			foreach( explode(';', $recipient) as $row ) $this->addRecipient($row);
			return $this;
		}
		
		$this->recipients[] = trim($recipient);
		return $this;
	}
	
	// Установка from
	public final function setFrom( $email, $name = NULL ){ 
		$this->from = array($email, $name); 
		return $this;
	}
	public final function setSender( $email, $name = NULL ){ return $this->setFrom($email, $name); }
	
	// Установка MessageID
	public final function setMessageID( $id ){ $this->ID_message = $id; }
	
	// Установка Reply to ID
	public final function setReplyToID( $id ){ $this->ID_replyToMessage = $id; }
	
	// Установка кодировки
	public function setCharset( $new ){
		$new = strtolower( trim($new) );
		if ( !in_array( $new, self::$allowedCharsets) ) throw new StandardException("Charset $new not supported");
		if ( $new == 'cp1251' ) $new = 'windows-1251';
		$this->charset = $new;
		return $this;
	}
	public final function setEncoding( $new ){ return $this->setCharset($new); }
	
	// Установка заголовка
	public function setSubject( $text ){ $this->subject = $text; return $this; }
	public final function setTitle( $text ){ return $this->setSubject($text); }
	
	// Установка сообщения
	public function setBody( $text ){ $this->body = trim($text); return $this; }
	public final function setMessage( $text ){ return $this->setBody($text); }
	public final function setText( $text ){ return $this->setBody($text); }
	
	// Перекодировка 
	public function transform( $text, $processToBase64 = FALSE ){
		$text = mb_convert_encoding( $text, $this->charset);
		if ( $processToBase64 ){
			$text = '=?'.strtoupper( $this->charset ).'?B?'.base64_encode($text).'?=';
		}
		return $text;
	}
	
	protected function smartFrom(){
		if ( isset( $this->from[1] ) )
			return $this->transform( $this->from[1] ).' <'.$this->from[0].'>';
		return $this->from[0];
	}
	
	// Отправка сообщения
	public function send(){
		if ( !isset($this->from) ) throw new StandardException("Sender not set");
		
		$headers = $this->getHeaders();
		if ( isset($this->ID_message) ) $headers['Message-ID'] = $this->ID_message;
		if ( isset($this->ID_replyToMessage) ) $headers['In-Reply-To'] = $this->ID_replyToMessage;
		
		$recipients = array_unique( $this->recipients );
		$recipients = implode(', ',$recipients );
//		$headers['To'] = $recipients;
		
		// Перекодируем
		
		// Пакуем заголовки
		$headers_plain = '';
		foreach ( $headers as $k=>$v ){
			$headers_plain .= $k.': '.$v."\r\n";
		}
		$result = mail( $recipients, $this->transform( $this->subject, TRUE ), $this->transform( $this->body ), trim($headers_plain), '-f '.$this->from[0] );
		if ( $result === FALSE ) throw new Exception("Cant send email");
	}
	
	protected function getHeaders(){
		return array(
			'MIME-Version'=>'1.0',
			'From'=>$this->smartFrom(),
			'Return-Path'=>$this->from[0],
			'Reply-To'=>$this->smartFrom(),
			'Content-Type'=> 'text/plain; charset="'.$this->charset.'"',
			'Date'=>date('r'),
			'X-Mailer'=>'StandardMailer'.get_class($this),
		);
	} 
}