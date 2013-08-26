<?php

class Helper_Color{
    
    /* Factory */
    public static function fromHex( $hexString ){
        if ( $hexString[0] == '#' ) $hexString = substr ($hexString, 1);
        if ( strlen( $hexString ) == 3 ) $hexString = $hexString[0].$hexString[0].$hexString[1].$hexString[1].$hexString[2].$hexString[2];
        if ( strlen( $hexString ) != 6 ) throw new StandardException("'$hexString' is not valid HEX representation on color");
        return self::fromRGB( 
                hexdec(substr($hexString, 0, 2)),
                hexdec(substr($hexString, 2, 2)),
                hexdec(substr($hexString, 4, 2))
                );
    }
    
    public static function fromRGB( $red, $green, $blue, $alpha = 1 ){
        $c = self::rgb2hsv($red, $green, $blue);
        $object = new self();
        $object->alpha = $alpha;
        $object->hue = $c[0];
        $object->saturation = $c[1];
        $object->value = $c[2];
        return $object;
    }
    
    /* Helpers */
    public static function rgb2hsv($r,$g,$b) { 
        $r/=255;$g/=255;$b/=255;
        $v=max($r,$g,$b); 
        $t=min($r,$g,$b); 
        $s=($v==0)?0:($v-$t)/$v; 
        if ($s==0) 
            $h=-1; 
        else { 
            $a=$v-$t; 
            $cr=($v-$r)/$a; 
            $cg=($v-$g)/$a; 
            $cb=($v-$b)/$a; 
            $h=($r==$v)?$cb-$cg:(($g==$v)?2+$cr-$cb:(($b==$v)?$h=4+$cg-$cr:0)); 
            $h=60*$h; 
            $h=($h<0)?$h+360:$h; 
        } 
        return array($h,$s,$v); 
    }     
    
    public static function hsv2rgb($h,$s,$v) { 
        if ($s==0) 
            return array($v,$v,$v); 
        else {
            $q=array();
            $h=($h%=360)/60; 
            $i=floor($h); 
            $f=$h-$i; 
            $q[0]=$q[1]=$v*(1-$s); 
            $q[2]=$v*(1-$s*(1-$f)); 
            $q[3]=$q[4]=$v; 
            $q[5]=$v*(1-$s*$f); 
            return(array(intval($q[($i+4)%6]*255),intval($q[($i+2)%6]*255),intval($q[$i%6]*255))); //[1] 
        }
    }
    
    private function dechex( $value ){
        $hex = dechex($value);
        if (strlen($hex) == 1 ) $hex = '0'.$hex;
        return $hex;
    }
    
    /* Object */
    private $hue; // 0..360
    private $saturation; // 0..1
    private $value; // 0..1
    private $alpha; // 0..1
    
    private function __construct() {}
    
    public function getRGB(){
        return self::hsv2rgb($this->hue, $this->saturation, $this->value);
    }
    public function getRGBA(){
        $a = $this->getRGB();
        $a[] = $this->alpha;
        return $a;
    }
    public function getHEX( $suffix = '#' ){
        $a = $this->getRGB();
        return $suffix.$this->dechex($a[0]).$this->dechex($a[1]).$this->dechex($a[2]);
    }
    public function hue( $delta = NULL, $relative = FALSE ){
        if ( !isset($delta) ) return $this->hue;
        $new_value = $delta;
        if ( $relative ) $new_value += $this->hue;
        return $this->hue = $new_value % 360;
    }
    public function value( $delta = NULL, $relative = FALSE ){
        if ( !isset($delta) ) return $this->value;
        $new_value = $delta;
        if ( $relative ) $new_value += $this->value;
        return $this->value = max(1, $new_value);
    }
}
