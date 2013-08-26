<?php

class Renderable_META extends Renderable_Item{
	
	
    
	public function __construct( $containerWithMetaInformation){
		$this->data = $containerWithMetaInformation;
	}
	
	public function getContext( $ignore = NULL ){
		$answer = '';
		$answer .= "\t<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\" />\n";
		$answer .= "\t<meta name=\"generator\" content=\"Standard\" />\n";
		if ( isset( $this->data['index'], $this->data['follow'] ) ){
		    $answer .= "\t".'<meta name="robots" content="'.( $this->data['index'] ? '' : 'NO' ).'INDEX,'.( $this->data['follow'] ? '' : 'NO' ).'FOLLOW">'."\n";
		}
		$answer .= "\t<title>".$this->data->title->implode(' ')."</title>\n";
		if ( $this->data->keywords->count() > 0 )
			$answer .= "\t<meta name=\"keywords\" content=\"".$this->data->keywords->map('strip_tags')->map('trim')->implode(',')."\" />\n";
		if ( !empty($this->data->description) )
			$answer .= "\t<meta name=\"description\" content=\"".str_replace(array("\n","\r","\t",'"'),array('','','',"'"),trim(strip_tags($this->data->description)))."\" />\n";
		if ( count($this->data->open_graph) ) foreach ( $this->data->open_graph as $k=>$v ){
			$v = trim(strip_tags($v));
                        $v = str_replace(array("\n","\r","\t",'"'),array('','','',"'"), $v);
			$answer .= "\t<meta property=\"og:{$k}\" content=\"{$v}\" />\n";
		}
		$answer .= "\t\n";
		return $answer;
	}
}