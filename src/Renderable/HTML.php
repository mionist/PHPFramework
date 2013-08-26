<?php
class Renderable_HTML extends Renderable_Item{
	protected $html_type;

	protected $html_name;
	protected $html_id;
	protected $html_classes;
	protected $html_styles;
	protected $html_values;
	protected $html_value;
	protected $html_default;
	protected $html_description;
	protected $html_attr;
	
	const HTML_INPUT = 1;
	const HTML_PASSWORD = 2;
	const HTML_BUTTON = 3;
	const HTML_BUTTON_SUBMIT = 4;
	const HTML_SELECT = 5;
	const HTML_RADIO = 6;
	const HTML_RADIO_LINE = 61;
	const HTML_CHECKBOX = 7;
	
	public function __construct( $type, $name = null, $id = null, $classes = null, $styles = null, $values = null, $value = null, $default = null, $description = null, $attributes = null ){
		parent::__construct( null, Renderable_Item::CONTEXT_HTML );
		$this->html_type = $type;
		$this->html_name = $name;
		$this->html_id = $id;
		$this->html_classes = $classes;
		$this->html_styles = $styles;
		$this->html_values = $values;
		$this->html_value = $value;
		$this->html_description = $description;
		$this->html_default = $default;
		$this->html_attr = $attributes;
	}
	
	public function setNameAndId( $name = null, $id = null ){
		$this->html_name = $name;
		if ( isset($name) && ( !isset($id) ) ) $this->html_id = $name;
		elseif ( isset($id) ) $this->html_id = $id;
		
		return $this;
	}
	
	public function setClasses( $value ) { $this->html_classes = $value; return $this; }
	public function addClass( $value ) {
		if ( !isset($this->html_classes) ) {
			$this->html_classes = array($value);
			return $this;
		}
		if ( !is_array( $this->html_classes ) ) $this->html_classes = array( $this->html_classes );
		$this->html_classes[] = $value;
		return $this;
	}
	
	public function setStyles( $value ) { $this->html_styles = $value; return $this; }
	
	public function setValuesList( $value ){ $this->html_values = $value; return $this; }
	public function setValue( $value ){ $this->html_value = $value; return $this; }
	
	protected function produceContext( $ignore ){
		// Стили
		$string_html_style = '';
		if ( isset($this->html_styles) ){
			if ( is_array($this->html_styles) ) $string_html_style = implode("; ", $this->html_styles);
			else $string_html_style = ''.$this->html_styles;
			
			$string_html_style = " style=\"$string_html_style\"";
		}
		// Классы
		$string_html_class = '';
		if ( isset($this->html_classes) ){
			if ( is_array($this->html_classes) ) $string_html_class = implode(' ', $this->html_classes);
			else $string_html_class = ''.$this->html_classes;

			$string_html_class = " class=\"$string_html_class\"";
		}
		
		$result = "";
		if ( $this->html_type == self::HTML_SELECT ){ // Отображение списка
			$result .= '<select';
			if ( isset($this->html_name) ) $result .= ' name="'.$this->html_name.'"';
			if ( isset($this->html_id) ) $result .= ' id="'.$this->html_id.'"';
			$result .= $string_html_class;
			$result .= $string_html_style;
			$result .= '>';
			$look = ( isset( $this->html_value ) ? $this->html_value : $this->html_default );
			if ( isset($this->html_values) && is_array( $this->html_values ) ) foreach ( $this->html_values as $value=>$text ){
				$result .= '<option value="'.$value.'"'.( isset($look) && $look == $value ? ' selected' : '' ).'>'.$text.'</option>';
			}
			$result .= '</select>';
		} elseif ( $this->html_type == self::HTML_INPUT || $this->html_type == self::HTML_PASSWORD ) { // Отображение поля ввода
			$result .= '<input type="'.( $this->html_type == self::HTML_PASSWORD ? 'password' : 'text' ).'"';
			if ( isset($this->html_name) ) $result .= ' name="'.$this->html_name.'"';
			if ( isset($this->html_id) ) $result .= ' id="'.$this->html_id.'"';
			$result .= $string_html_class;
			$result .= $string_html_style;
			if ( isset($this->html_default) ) $result .= " default=\"{$this->html_default}\"";
			if ( isset($this->html_value) ) $result .= " value=\"{$this->html_value}\"";
			if ( $this->html_attr != NULL ) foreach ( $this->html_attr as $k=>$v ){
				$result .= " $k=\"$v\"";
			}
			$result .= ' />';
		} elseif ( $this->html_type == self::HTML_RADIO || $this->html_type == self::HTML_CHECKBOX ){ // Отображение чекбоксов
			$result .= '<input type="'.( $this->html_type == self::HTML_RADIO ? 'radio' : 'checkbox' ).'"';
			if ( isset($this->html_name) ) $result .= ' name="'.$this->html_name.'"';
			if ( isset($this->html_id) ) $result .= ' id="'.$this->html_id.'"';
			$result .= $string_html_class;
			$result .= $string_html_style;
			if ( isset($this->html_values) ) $result .= " value=\"{$this->html_values}\"";
			if ( isset($this->html_values) && isset($this->html_value) && $this->html_values == $this->html_value ) $result .= ' checked';
			if ( isset($this->html_values) && !isset($this->html_value) && isset($this->html_default) && $this->html_values == $this->html_default ) $result .= ' checked';
			$result .= ' />';
			if ( isset($this->html_description, $this->html_id) ) $result .= " <label for=\"{$this->html_id}\">{$this->html_description}</label>"; 
		} elseif ( $this->html_type == self::HTML_RADIO_LINE ){
			$temp_id = 0;
			if ( isset($this->html_values) && is_array( $this->html_values ) ) foreach ( $this->html_values as $value=>$text ){
				$temp_id++;
				$result .= '<span>';
				$result .= '<input type="radio"';
				$result .= ' name="'.$this->html_name.'"';
				$result .= ' id="ORMAutoID'.$this->html_name.'_'.$temp_id.'" ';
				$result .= " value=\"{$value}\"";
				if ( isset($this->html_value) && $this->html_value == $value )  $result .= ' checked';
				$result .= ' />';
				$result .= "<label for=\"ORMAutoID".$this->html_name.'_'.$temp_id."\">{$text}</label>";
				$result .= '&nbsp;</span>';
			}
		} elseif ( $this->html_type == self::HTML_BUTTON || $this->html_type == self::HTML_BUTTON_SUBMIT ) { // Отображение кнопки
			$result .= '<input type="'.( $this->html_type == self::HTML_BUTTON_SUBMIT ? 'submit' : 'button' ).'"';
			if ( isset($this->html_name) ) $result .= ' name="'.$this->html_name.'"';
			if ( isset($this->html_id) ) $result .= ' id="'.$this->html_id.'"';
			$result .= $string_html_class;
			$result .= $string_html_style;
			if ( isset($this->html_value) ) $result .= " value=\"{$this->html_value}\"";
			$result .= ' />';
		}
		
		return $result;
	}
}