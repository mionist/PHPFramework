<?php

class Compiler_Pattern_Color extends Compiler_Pattern{
public function getContext() {
        $color = NULL;
        for ( $i=0; $i < $this->count(); $i+=2 ){
            $command = strtolower( $this->get($i) );
            $arg = strtolower( $this->get($i+1) );
            if ( $command == 'base' ) $color = Helper_Color::fromHex ($arg);
            else {
                if ( !isset($color) ) $color = Helper_Color::fromRGB (0, 0, 0);
            }
            switch ( $command ){
                case 'hue':
                    $color->hue( $arg, TRUE );
                    break;
                case 'value':
                    $color->value( $arg, TRUE );
                    break;
            }
        }
        
        return $color->getHEX();
    }
}