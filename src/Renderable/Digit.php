<?php
class Renderable_Digit extends Renderable_Item{

	const DIGIT_INT = 1;
	const DIGIT_INT_SIMPLIFIED = 11;
	const DIGIT_INT_SPACED = 12;
	const DIGIT_CURRENCY = 2;
        const DIGIT_CURRENCY_SEPARATED = 21;
	const DIGIT_CURRENCY_COLORIZED = 3;
	const DIGIT_FLOAT = 31;
	const DIGIT_INT_WITH_TEXT = 4;
	const DIGIT_PLURAL_ONLY = 5;
	const DIGIT_INT_WITH_TEXT_ZERO_IGNORE = 6;

	private $behavoir = self::DIGIT_INT;
	private $parameter = null;

	public function __construct( $digit, $behavior, $context = null, $parameter = null ){
		$digit = str_replace(array(',',' '), array('.',''), $digit);
		$this->behavoir = $behavior;
		$this->parameter = $parameter;
		parent::__construct( $digit, $context );
	}

	public function setBehavior( $value ){ $this->behavoir = $value; return $this; }
	public function setParameter( $value ){ $this->parameter = $value; return $this; }

	protected function produceContext( $context ){
		$context = $this->findContext($context);
		$value = '';

                if ( $context == Renderable_Item::CONTEXT_PLAINTEXT && $this->behavoir != self::DIGIT_PLURAL_ONLY ) return $this->data;
                if ( $context == Renderable_Item::CONTEXT_HTML && $this->data == 0 && isset($this->parameter, $this->parameter['hide_zero']) && $this->parameter['hide_zero'] ) return '&nbsp;';

		switch ( $this->behavoir ){
			case self::DIGIT_FLOAT:
				$precision = 2;
				if ( isset($this->parameter, $this->parameter['precision']) ) $precision = $this->parameter['precision'];
				$value = number_format( $this->data, $precision, '.', '' );
				break;
			case self::DIGIT_CURRENCY:
			case self::DIGIT_CURRENCY_COLORIZED:
				$value = number_format( $this->data, 2, '.', '' );
				break;
                        case self::DIGIT_CURRENCY_SEPARATED:
                                $value = number_format( $this->data, 2, '.', '`' );
                                break;
			case self::DIGIT_PLURAL_ONLY:
			case self::DIGIT_INT_WITH_TEXT_ZERO_IGNORE:
				if ( $this->data < 1 ) break; // Не генерируем ничего
			case self::DIGIT_INT_WITH_TEXT:
				if ( !isset($this->parameter) ) throw new Exception("Empty texts for plural form");
				if ( !is_array( $this->parameter ) ) $this->parameter = explode(',', $this->parameter);
				$one = $this->parameter[0];
				$two = $this->parameter[1];
				$many = $this->parameter[2];
				if ( $this->data % 100 == 1 || ($this->data % 100>20) && ( $this->data % 10 == 1 ) ) $value = $one;
				elseif ( $this->data % 100 == 2 || ($this->data % 100>20) && ( $this->data % 10 == 2 ) ) $value = $two;
				elseif ( $this->data % 100 == 3 || ($this->data % 100>20) && ( $this->data % 10 == 3 ) ) $value = $two;
				elseif ( $this->data % 100 == 4 || ($this->data % 100>20) && ( $this->data % 10 == 4 ) ) $value = $two;
				else $value = $many;

				if ( $this->behavoir != self::DIGIT_PLURAL_ONLY ) $value = $this->data.' '.$value;
				break;
			case self::DIGIT_INT_SIMPLIFIED:
				if ( $this->data > 100000000 ) return intval( $this->data / 1000000 ).'M';
				if ( $this->data > 100000 ) return intval( $this->data / 1000 ).'K';
				return "".((int) $this->data);
				break;
			case self::DIGIT_INT_SPACED:
				$value = "".number_format( intval( $this->data ), 0, '', ' ' );
				break;
			case self::DIGIT_INT:
				$value = "".((int) $this->data);
				break;
		}
		// Если если префиксы и/или суффиксы
		if ( isset($this->parameter, $this->parameter['prefix']) && $context == Renderable_Item::CONTEXT_HTML ){
		    $value = $this->parameter['prefix'].$value;
		}
		if ( isset($this->parameter, $this->parameter['suffix']) && $context == Renderable_Item::CONTEXT_HTML ){
		    $value .= $this->parameter['suffix'];
		}

		// Если HTML
		if ( $context == Renderable_Item::CONTEXT_HTML ){
			if ( $this->behavoir == self::DIGIT_CURRENCY_COLORIZED ){
				if ( $this->data > 0 )
					$value = "<span class='digit_positive'>$value</span>";
				elseif ( $this->data < 0 )
					$value = "<span class='digit_negative'>$value</span>";
				else
					$value = "<span class='digit_zero'>$value</span>";
			}
		}

		return $value;
	}

}