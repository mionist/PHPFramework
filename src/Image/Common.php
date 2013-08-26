<?php

class Image_Common {
	
	protected $im;
	protected $width;
	protected $height;
	
	public function __construct( $imOrFilename ){
		if ( is_string( $imOrFilename ) ) $this->im = $this->createFromFile( $imOrFilename );
		else $this->im = $imOrFilename;
		
		$this->width = imagesx( $this->im );
		$this->height = imagesy( $this->im );
	}
	
	private function createFromFile( $filename ){
		$dot = strrpos( $filename, '.');
		$ext = strtolower(substr( $filename, $dot+1));
		switch ( $ext ){
			case 'jpg':
			case 'jpeg':
				return imagecreatefromjpeg( $filename );
			case 'png':
				return imagecreatefrompng( $filename );
			case 'gif':
				return imagecreatefromgif( $filename );
		}
		throw new StandardException("Filetype $ext not supported");
	}
	
	public function __destruct(){imagedestroy( $this->im );}
	public function destroy(){ $this->__destruct(); }
	
	public function grayscale(){imagefilter( $this->im, IMG_FILTER_GRAYSCALE );}
	
        public function save( $filename ){
                // Определяем расширение
                $dot = strrpos( $filename, '.');
                $ext = strtolower(substr( $filename, $dot+1));
		switch ( $ext ){
			case 'jpg':
			case 'jpeg':
				return $this->saveJPG( $filename );
			case 'png':
				return $this->savePNG( $filename );
			case 'gif':
				return $this->saveGIF( $filename );
		}
		throw new StandardException("Filetype $ext not supported");
        }
        
	public function saveJPG( $filename, $quality = 90 ){
		imagejpeg( $this->im, $filename, $quality );
	}
        
        public function savePNG( $filename ){
            imagepng( $this->im, $filename );
        }
        
        public function saveGIF( $filename ){
            imagegif( $this->im, $filename );
        }
	
	public function getWidth() { return $this->width; }
	public function getHeight() { return $this->height; }
	
	public function getResizedToBoundingBox( $width, $height, $onlyToLower = TRUE ){
		$aspect = max( $this->width / $width, $this->height / $height );
		if ( $aspect < 1 && $onlyToLower ) return $this;
		return $this->getResizedToDimensions( round($this->width / $aspect ) , round( $this->height / $aspect ) );
	}
	
	public function getResizedToDimensions( $width, $height ){
		$im = imagecreatetruecolor($width, $height);
		imagecopyresampled($im, $this->im, 0, 0, 0, 0, $width, $height, $this->width, $this->height);
		return new self( $im );
	}
	
	public function rect( $x1, $y1, $x2, $y2, $lineColorArray = null, $fillColorArray = null ){
		if ( $x1 < 0 ) $x1 += $this->width;
		if ( $x2 <= 0 ) $x2 += $this->width;
		if ( $y1 < 0 ) $y1 += $this->height;
		if ( $y2 <= 0 ) $y2 += $this->height;
		
		if ( isset($fillColorArray) ){
			imagefilledrectangle($this->im, $x1, $y1, $x2, $y2, $this->getColor($fillColorArray));
		}
	}
	
	public function text( $font, $size, $x, $y, $text, $colorArray = NULL ){
		if ( $x < 0 ) $x += $this->width;
		if ( $y < 0 ) $y += $this->height;
		if ( !isset($colorArray) ){
			/*
				0	lower left corner, X position
				1	lower left corner, Y position
				2	lower right corner, X position
				3	lower right corner, Y position
				4	upper right corner, X position
				5	upper right corner, Y position
				6	upper left corner, X position
				7	upper left corner, Y position
			 */
			return imagettfbbox( $size , 0, $font, $text);
		}
		imagettftext( $this->im , $size, 0, $x, $y, $this->getColor($colorArray), $font, $text);
	}
	
	public function getColor( $array ){
		if ( count($array) == 3 )
			return imagecolorallocate( $this->im, $array[0], $array[1], $array[2] );
		if ( count($array) == 4 )
			return imagecolorallocatealpha( $this->im, $array[0], $array[1], $array[2], $array[3] );
	}
	
	/**
	 * 
	 * Меняем контрастность от -100 до 100
	 * @param int $delta
	 */
	public function contrast( $delta ){imagefilter( $this->im, IMG_FILTER_CONTRAST, $delta);}
	
	/**
	 * 
	 * Меняем контрастность от -255 до 255
	 * @param int $delta
	 */
	public function brightness( $delta ){imagefilter( $this->im, IMG_FILTER_BRIGHTNESS, $delta);}
	
	
}