<?php
$o->sendContentType( Output::CONTENT_TYPE_PLAIN );

$headers = $o->data->report->getHeaders();
$headers_count = count( $headers );
$data = $o->data->report->getRowset();


foreach ( $headers as $h ){
    if (is_array($h) ) $h = $h[0];
    echo '"'.str_replace('"',"'",$h).'";';
}
echo "\n";
foreach ( $data as $rowindex=>$row ){
    for ( $i=0; $i < $headers_count; $i++ ){
	if ( isset($row[$i]) && is_object( $row[$i] ) && $row[$i] instanceof Renderable_Item ) { 
            $row[$i]->setContext(Renderable_Item::CONTEXT_PLAINTEXT );
            $row[$i] = ''.$row[$i];
        }
	if ( !isset($row[$i]) ) $row[$i] = "NULL";
        if ( !is_numeric($row[$i]) ) $row[$i] = '"'.str_replace (array('"',';'), array("'",","), $row[$i]).'"';
	echo $row[$i].";";
    }
    echo "\n";
}
