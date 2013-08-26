<?php

class Manage_ORM_ImageFileField extends Manage_ORM_FileField{

	public function getPropertiesEdit( $row ){
		if ( !$this->hasFile( $row ) ) return array();
		
                try{
                    $size = getimagesize( $this->getFullFilename( $row ) );
                } catch ( Exception $e ){
                    return parent::getPropertiesEdit( $row );
                }
		
		return array_merge( parent::getPropertiesEdit( $row ), array(
			'холст'=>$size[0].'x'.$size[1].' пкс; тип: '.$size['mime'],
			'картинка'=>'<a href="/'.$this->getFullFilename( $row ).'"><img border="0" src="/'.$this->getFullFilename( $row ).'" style="max-width: 400px; max-height: 100px;"/></a>',
		));
	}
	

}