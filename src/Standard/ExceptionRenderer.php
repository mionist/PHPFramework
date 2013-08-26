<?php

class Standard_ExceptionRenderer{
	
        public $buffer;
    
	public function __construct( Exception $e, $returnAsStringInCli = FALSE /*$symbolicName, $code, $array, $trace = NULL*/ ){
		$symbolicName = get_class( $e );
		$code = $e->getCode();
		$array = array();
		$trace = $e->getTrace();
		if ( $e instanceof DbxException ){
			$array['Error']  = 'Database error';
			if ( $this->amIPrivileged() ){
				$array['SQL'] = $e->getSQL();
				$array['MySQL error message'] = $e->getMySQLMessage();
			}
		} elseif ( $e instanceof PHPStandartException ){
			$array['Error']  = 'Engine PHP error';
			if ( $this->amIPrivileged() ){
				$array['Message'] = $e->php_errstr;
				$array['File'] = $e->php_errfile;
				$array['Line'] = $e->php_errline;
			}
		} elseif ( $e instanceof Exception_UserNotPrivileged ){
			$array['Error'] = $e->getMessage();
			if ( $this->amIPrivileged() ){
			    $r_success = array();
			    $r_failed = array();
			    foreach ( Core::$auth->getRightsLog() as $k=>$v ){
				if ( $v ) $r_success[] = $k;
				else $r_failed[] = $k;
			    }
			    $array['Success rights checks'] = implode(', ', $r_success);
			    $array['Failed rights checks'] = implode(', ', $r_failed);
			}
		} else {
			$array['Error'] = $e->getMessage();
		}
		
		if ( Core::$mode == Core::MODE_CLI ) {
                    $this->buffer = $this->renderCLI($symbolicName, $code, $array, $trace);
                    if ( !$returnAsStringInCli ) die ( $this->buffer );
                    return;
                }
		if ( Core::$mode == Core::MODE_AJAX ) $this->renderAJAX( $symbolicName, $code, $array, $trace );
		
		if ( ob_get_length() > 0 || headers_sent() ) die($this->renderCLI($symbolicName, $code, $array, $trace));
		else $this->renderHTML($symbolicName, $code, $array, $trace);
		
		exit;
	}
	
	private function amIPrivileged(){
		if ( defined( 'Configuration::EXCEPTIONS_VIEW_IPS' ) ){
			$ips = explode( ',', Configuration::EXCEPTIONS_VIEW_IPS );
			if ( !isset($_SERVER['REMOTE_ADDR']) || in_array( $_SERVER['REMOTE_ADDR'], $ips) ) return TRUE;
		}
		if ( defined( 'Configuration::EXCEPTIONS_VIEW_COOKIE' ) ){
			if ( isset($_COOKIE[Configuration::EXCEPTIONS_VIEW_COOKIE]) ) return TRUE;
		}
		return FALSE;
	}
	
	private function renderAJAX( $symbolicName, $code, $array, $trace ){
		if ( !isset($array) ) $array = array();
		die( json_encode( array('result'=>'error', 'exception'=>TRUE, 'exception_name'=>$symbolicName, 'exception_code'=>$code, 'exception_data'=>$array, 'excention_trace'=>$trace ) ));
	}
	
	private function renderCLI( $symbolicName, $code, $array, $trace ){
                $buffer = '';
		// Отображем в CLI режиме
		$buffer .= "\n\nUnhandled exception caught '$symbolicName' at ".date('Y-m-d H:i:s')."\n";
		if ( isset($code) && $code != 0 ) 
			$buffer .= "Exception code: $code\n";
		if ( isset($array) && is_array($array) ) foreach ( $array as $k=>$v ){
			$buffer .= "$k: $v\n";
		}
		$i = 0;
		if ( isset($trace) ){
		$buffer .= "Trace:\n"; 
		foreach ( $trace as $row ){
			$i++;
                        if ( !isset($row['line'],$row['file']) )
                            $buffer .= ' '.( $i < 10 ? ' ': '' ).$i.". Anonymous \n";
                        else
                            $buffer .= ' '.( $i < 10 ? ' ': '' ).$i.". Line {$row['line']} in {$row['file']}\n";
                            
			$buffer .= "     ".( isset($row['class']) ? $row['class'].$row['type'] : '' ).$row['function'];
			if ( isset($row['args']) && count($row['args']) ){
				$buffer .= '('.implode(',',array_map('gettype', $row['args'])).')';
			}
			$buffer .= "\n";
		}}
                return $buffer;
	}
	
	private function renderHTML( $symbolicName, $code, $array, $trace ){
		header( 'Content-Type: text/html;charset=utf-8' );
		echo "<html>";
		echo "<head><title>Error - unhandled exception</title>";
		echo "<style>";
		echo "body{ background-color: white; color: black; font: normal 10pt 'Microsoft Sans Serif', Arial, sans-serif; padding: 10px; }\n";
		echo ".StandardExceptionTitle { font-family: 'Arial Narrow', Arial, sans-serif; font-size: 22pt; color: #777; font-weight: normal; margin: 0; padding: 0; }\n";
		echo ".StandardExceptionName { font-family: 'Arial Narrow', Arial, sans-serif; font-size: 19pt; color: #555; font-weight: bold; margin: 0 0 30px 0; padding: 0; }\n";
		echo ".StandardExplanation i { font-style: normal; color: #999; }\n";
		echo ".StandardExplanation b { font-weight: normal; }\n";
		echo ".StandardTraceLine i { font-style: normal; color: #999; }\n";
		echo ".StandardTraceLine b { font-weight: normal; }\n";
		echo ".StandardTraceLine small { font-family: monospace; font-size: 9pt; color: #666; }\n";
		echo "</style>";
		echo "</head>";
		echo "<body>";
		echo "<h2 class='StandardExceptionTitle'>Error 500</h2>";
		echo "<h3 class='StandardExceptionName'>$symbolicName</h3>";
		if ( isset($array) && is_array($array) ) foreach ( $array as $k=>$v )
			echo "<div class='StandardExplanation'><i>$k</i>: <b>$v</b></div>";
		
		if ( isset($trace) && $this->amIPrivileged() ){
			echo "<div class='StandardExplanation'><i>Trace</i>:</div>";
			$i = 0;
			foreach ( $trace as $row ){
				$i++;
				if (!isset($row['line']) ) $row['line'] = 0;
				if (!isset($row['file']) ) $row['file'] = 'unknown';
				echo '<div class="StandardTraceLine"><small>&nbsp;'.( $i < 10 ? '&nbsp;': '' ).$i.".&nbsp;</small><i>Line</i> <b>{$row['line']}</b> <i>in</i> <b>{$row['file']}</b><br />";
				echo "<small>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</small><b>".( isset($row['class']) ? $row['class'].$row['type'] : '' ).$row['function'].'</b>';
				if ( isset($row['args']) && count($row['args']) ){
					echo '( <b>'.implode(', ',array_map('gettype', $row['args'])).'</b> )';
				}
				echo '</div>';
			}
		}
	
		echo "</body>";
		echo "</html>";
	}
}