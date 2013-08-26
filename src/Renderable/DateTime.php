<?php

class Renderable_DateTime extends Renderable_Item{
	
	const NORMAL = 1;
	const NORMAL_DATE = 11;
	const SYMBOLIC = 2;
        const TIMESTAMP = 3;
        const TIMESTAMP_JAVASCRIPT = 4;
	
	protected $timestamp;
	protected $behaviour;
	
	public function __construct( $value, $behaviour = self::NORMAL ){
		if ( $value === '0000-00-00' || $value === '0000-00-00 00:00:00' ){
			$this->timestamp = 0;
		} elseif ( strpos( $value, '-') !== FALSE ) {
			$this->timestamp = strtotime( $value );
		} else {
			$this->timestamp = $value;
		}
		$this->behaviour = $behaviour;
	}
        
        public function setBehavior( $value ){ $this->behaviour = $value; return $this; }
	
	protected function produceContext( $context ){
		if ( $this->timestamp == 0 && $context == Renderable_Item::CONTEXT_HTML ) return '&nbsp;';
		switch ( $this->behaviour ){
                        case self::TIMESTAMP:
                            return ''.$this->timestamp;
                        case self::TIMESTAMP_JAVASCRIPT:
                            return ''.$this->timestamp * 1000;
			case self::SYMBOLIC:
				if ( date('Y-m-d') == date('Y-m-d', $this->timestamp) )
					return 'сегодня в '.date('H:i', $this->timestamp);
				if ( date('Y-m-d') == date('Y-m-d', $this->timestamp+86400) )
					return 'вчера в '.date('H:i', $this->timestamp);
				return date('d.m.y в H:i', $this->timestamp);
			case self::NORMAL_DATE:
			     return date('d.m.y', $this->timestamp);
			case self::NORMAL:
			default:
				 return date('d.m.y H:i:s', $this->timestamp);
		}
	}
	
}