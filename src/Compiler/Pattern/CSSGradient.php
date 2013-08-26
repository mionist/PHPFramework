<?php

/**
 * First parameter - from, second parameter - to
 */

class Compiler_Pattern_CSSGradient extends Compiler_Pattern{
public function getContext() {
$from = $this->get(0);
$to   = $this->get(1);
return " background: $from; " /* Old browsers */
."background: -moz-linear-gradient(top,  $from 0%, $to 100%); " /* FF3.6+ */
."background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,$from), color-stop(100%,$to)); " /* Chrome,Safari4+ */
."background: -webkit-linear-gradient(top,  $from 0%,$to 100%); " /* Chrome10+,Safari5.1+ */
."background: -o-linear-gradient(top,  $from 0%,$to 100%); " /* Opera 11.10+ */
."background: -ms-linear-gradient(top,  $from 0%,$to 100%); " /* IE10+ */
."background: linear-gradient(top,  $from 0%,$to 100%); " /* W3C */
."filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='$from', endColorstr='$to',GradientType=0 ); " /* IE6-9 */;
}
}