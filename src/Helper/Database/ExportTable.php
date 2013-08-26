<?php

class Helper_Database_ExportTable{
    
   public static function Export( $table, $db = NULL, $structure = TRUE, $data = TRUE ){
       if ( !isset( $db ) ) $db = Core::getDatabase();
       
       $answer = '';
       $answer .= "-- Engine: Standard Dumper ( Test Mode)\n";
       $answer .= "-- Time  : ".date('Y-m-d H:i:s')."\n";
       $answer .= "\n\n";
       if ( $structure ) {
	   $answer .= "-- Structure --\n";
	   $db->getBuilder('plain')
		   ->showCreate( $table )
		   ->exec();
	   $answer .= $db->result->getValue('Create Table').";\n\n";
       }
       
       if ( $data ){
	   $db->getBuilder('plain')
                   ->select($table, '* FROM ??')
                   ->exec();
           $insert_prefix = NULL;
           foreach ( $db->result as $row ){
               if ( !isset($insert_prefix) ){
                   $insert_prefix = "INSERT INTO `$table` (`".implode('`,`', array_keys($row))."`) VALUES ";
               }
               
               $answer .= $insert_prefix .'(\''.implode("','",  array_map(array($db,'escape'),array_values($row))).'\')'. ";\n";
           }
       }
       $answer .= "\n\n-- Dump finished: ".date('Y-m-d H:i:s')."\n";
       return $answer;
   }
    
}