<?php

class Renderable_VideoURL extends Renderable_Item{
    
    public $width;
    public $height;
    
    public function __construct($data = NULL, $context = NULL, $width = 640, $height = 360) {
        parent::__construct($data, $context);
        $this->width = $width;
        $this->height = $height;
    }
    
    protected function produceContext($context) {
        switch ( $context ){
            case self::CONTEXT_PLAINTEXT:
            case self::CONTEXT_JSON:
                return JSON::encode( $this->getVideoSettings() );
            case self::CONTEXT_HTML:
                return $this->getEmbedCode();
        }
    }
    
    public function getVideoSettings(){
        $m = array();
        
        if ( $this->data == null || empty($this->data) ) return array('engine'=>'Unknown');
        
        if ( 
            preg_match( "%http://.*youtube.com/.*v=([a-z0-9\-_]*)%i", $this->data, $m ) ||
            preg_match( "%http://youtu.be/([a-z0-9\-_]*)%i", $this->data, $m )
           )
           return array('engine'=>'Youtube','id'=>$m[1],'thumb'=>'http://img.youtube.com/vi/'.$m[1].'/0.jpg','url'=>$this->data);
        
        if ( preg_match( "%http://vimeo.com/([0-9]*)%i", $this->data, $m ) )
           return array('engine'=>'Vimeo','id'=>$m[1],'thumb'=>null,'url'=>$this->data);
        
        return array('engine'=>'Unknown','url'=>$this->data);
    }
    
    public function getThumbnail( $forceNetworkingIfNeed = FALSE ){
        $videoSettings = $this->getVideoSettings();
        if ( $videoSettings['engine'] == 'Unknown' ) return NULL;
        if ( isset( $videoSettings['thumb'] ) ) return $videoSettings['thumb'];
        if ( $videoSettings['engine'] == 'Vimeo' && $forceNetworkingIfNeed ){
            try{
                $hash = unserialize(file_get_contents("http://vimeo.com/api/v2/video/{$videoSettings['id']}.php"));
                if ( isset($hash,$hash[0],$hash[0]['thumbnail_large']) )
                    return $hash[0]['thumbnail_large'];
            } catch ( Exception $e ) {}
        }
        return NULL;
    }
    
    private function getEmbedCode(){
        $videoSettings = $this->getVideoSettings();
        switch ( $videoSettings['engine'] ){
            case 'Unknown':
                return '';
            case 'Youtube':
                return "<iframe width='{$this->width}' height='{$this->height}' src='http://www.youtube.com/embed/{$videoSettings['id']}' frameborder='0' allowfullscreen></iframe>";
            case 'Vimeo':
                return "<iframe src='http://player.vimeo.com/video/{$videoSettings['id']}' width='{$this->width}' height='{$this->height}' frameborder='0' webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>";
            default:
                return '';
        }
    }
    
    
}
