<? 
Core::$out->sendContentType( Output::CONTENT_TYPE_JSON );
$headers_count = count( $o->data->report->getHeaders() );

$json = array(
    'status'=>'ok',
    'title'=>$o->data->report->getTitle(),
    'headers'=>$o->data->report->getHeaders(),
    'data'=>array()
);

foreach ( $o->data->report->getRowset() as $row ){
    $x = array();
    for ( $i=0; $i < $headers_count; $i++ ){
	if ( isset($row[$i]) && is_object( $row[$i] ) && $row[$i] instanceof Renderable_Item ) $row[$i]->setContext(Renderable_Item::CONTEXT_PLAINTEXT );
	if ( !isset($row[$i]) ) $row[$i] = NULL;
	$x[] = ''.$row[$i];
    }
    
    $json['data'][] = $x;
}

die( JSON::encode($json) );
