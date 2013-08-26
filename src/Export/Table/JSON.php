<?php

class Export_Table_JSON extends Export_Table {
	
	public function export(){
		return $this->encodeString(JSON::encode( $this->data ));
	}
	
	public function exportOutput(){
		if ( !headers_sent() ) header("Content-Type: text/plain");
		echo $this->export();
	}	
	
}