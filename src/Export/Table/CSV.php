<?php

class Export_Table_CSV extends Export_Table {
	
	public function export(){
		$answer = "";
		foreach ( $this->data as $row ) {
			$first = TRUE;
			foreach ( $row as $k=>$v ){
				if ( !$first ) $answer .= ';';
				
				// Форматируем 
				$v = $this->formatValue( $v , $k );
				
				// Экранируем
				if ( $this->needEscape($k) ){
					$v = '"'.str_replace(array('"',"\n","\r"), array('\"',' ',''), $v).'"';
				}
				$answer .= $v;
				$first = FALSE;
			}
			$answer .= "\n";
		}
		
		return $answer;
	}
	
	public function exportOutput(){
		if ( !headers_sent() ) header("Content-Type: text/plain");
		echo $this->export();
	}
	
}