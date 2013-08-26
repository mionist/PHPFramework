<?php
class JSON{
	public static function encode($data){
		if (!function_exists('json_encode')){
			throw new Exception('Function "json_encode" does not exist!');
		}
		
		$string = json_encode($data);
		
		// Converting utf chars to readable
		$string = self::decodeUnicodeString($string);
		
		return $string;
	}
	
	public static function decode($string){
		if (!function_exists('json_decode')){
			throw new Exception('Function "json_decode" does not exist!');
		}
		
		return json_decode($string,true);
	}
	
	public static function decodeUnicodeString($chrs){
		$delim			= substr($chrs, 0, 1);
		$utf8			= '';
		$strlen_chrs	= strlen($chrs);

		for($i = 0; $i < $strlen_chrs; $i++) {

			$substr_chrs_c_2 = substr($chrs, $i, 2);
			$ord_chrs_c = ord($chrs[$i]);

			switch (true) {
				case preg_match('/\\\u[0-9A-F]{4}/i', substr($chrs, $i, 6)):
					// single, escaped unicode character
					$utf16 = chr(hexdec(substr($chrs, ($i + 2), 2)))
						   . chr(hexdec(substr($chrs, ($i + 4), 2)));
					$utf8 .= mb_convert_encoding($utf16, 'UTF-8', 'UTF-16'); 
					$i += 5;
					break;
				case ($ord_chrs_c >= 0x20) && ($ord_chrs_c <= 0x7F):
					$utf8 .= $chrs{$i};
					break;
				case ($ord_chrs_c & 0xE0) == 0xC0:
					// characters U-00000080 - U-000007FF, mask 110XXXXX
					//see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
					$utf8 .= substr($chrs, $i, 2);
					++$i;
					break;
				case ($ord_chrs_c & 0xF0) == 0xE0:
					// characters U-00000800 - U-0000FFFF, mask 1110XXXX
					// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
					$utf8 .= substr($chrs, $i, 3);
					$i += 2;
					break;
				case ($ord_chrs_c & 0xF8) == 0xF0:
					// characters U-00010000 - U-001FFFFF, mask 11110XXX
					// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
					$utf8 .= substr($chrs, $i, 4);
					$i += 3;
					break;
				case ($ord_chrs_c & 0xFC) == 0xF8:
					// characters U-00200000 - U-03FFFFFF, mask 111110XX
					// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
					$utf8 .= substr($chrs, $i, 5);
					$i += 4;
					break;
				case ($ord_chrs_c & 0xFE) == 0xFC:
					// characters U-04000000 - U-7FFFFFFF, mask 1111110X
					// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
					$utf8 .= substr($chrs, $i, 6);
					$i += 5;
					break;
			}
		}

		return $utf8;
	}
}
?>
