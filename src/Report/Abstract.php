<?php

abstract class Report_Abstract{
    private $headers = array();
    private $data = array();
    private $summary = array();
    private $graphs = array();
    private $formData = NULL;
    
    private $summaryStarted = FALSE;
    
    private $row = NULL;
    private $column = 0;
    
    private $reportInTestingMode = FALSE;
    private $generationTime = 0;
    
    protected $input;
    
    protected abstract function form();
    protected abstract function fill();
    
    public function getTitle(){
	return get_class($this);
    }
    
    protected final function nextRow(){
	if ( !isset($this->row) ) $this->row = 0;
	else $this->row++;
	$this->column=0;
    }
    
    protected final function insert( $value, $colspan = 1 ){
	if ( !isset($this->row) ) $this->nextRow();
	
	$operator = &$this->data;
	if ( $this->summaryStarted ) $operator = &$this->summary;
	
        if ( $colspan == 1 ){
            if ( !isset($operator[$this->row]) ) $operator[$this->row] = array();
            $operator[$this->row][$this->column] = $value;
	    $this->column++;
        } else {
            $this->insert( $value, 1 );
            for ( $i=1; $i<$colspan; $i++ )
                $this->insert(NULL,1);
        }
    }
    
    public function erase(){
	$this->data = array();
	$this->headers = array();
        $this->graphs = array();
        $this->summary = array();
    }
    
    protected final function addHeader( $value, $colspan = 1 ){
        if ( $colspan == 1 ){
            $this->headers[] = $value;
        } else for( $i=0; $i<$colspan; $i++ ) $this->addHeader ($value);
    }
    
    protected final function addFormElement( $caption, Renderable_Item $item ){ 
        $this->formData[] = array(
            'caption'=>$caption,
            'object'=>$item
        );
	return $this;
    }
    
    public final function doFill( $input = NULL ){
        $start = microtime(TRUE);
        if ( isset($input) ) $this->input = $input;
        $this->fill();
        $this->generationTime = microtime(TRUE) - $start;
    }
    
    public final function getForm(){ 
        if ( !isset( $this->formData ) ){
            $this->formData = array();
            $this->form();
        }
        return $this->formData;
    }
    
    protected final function addGraphIntersect( $name, $legendColumn, $xColumn, $yColumns, $options = NULL ){
	if ( !is_array( $yColumns ) ) $yColumns = array( $yColumns );
	$this->graph[] = array(
	    'type'=>'intersect',
	    'name'=>$name,
	    'legend'=>$legendColumn,
	    'x'=>$xColumn,
	    'y'=>$yColumns,
	    'options'=>$options
	);
    }
    
    protected final function startSummary(){ $this->summaryStarted = TRUE; $this->row = NULL; }
    
    public final function testMode(){ $this->reportInTestingMode = TRUE; }
    public final function isInTestMode(){ return $this->reportInTestingMode; }
    
    public final function getHeaders(){ return $this->headers; }
    public final function getRowset(){ return $this->data; }
    public final function getSummary(){ return $this->summary; }
    
    // Graph
    public final function addGraph( $name, $xAxis, $yAxisArray, $options = NULL ){
        if ( !is_array( $yAxisArray ) ) $yAxisArray = array( $yAxisArray );
        $this->graphs[] = array(
            'type'=>'normal',
            'name'=>$name,
            'options'=>$options,
            'x'=>$xAxis,
            'y'=>$yAxisArray
        );
    }
    public final function addGraphItersect( $name, $legendColumn, $dataColumns, $options = NULL  ){
         $this->graphs[] = array(
            'type'=>'intersect',
            'name'=>$name,
            'options'=>$options,
            'legend'=>$legendColumn,
            'data'=>$dataColumns
        );
    }
    public final function getGraphs(){ return $this->graphs; }
    
    // Saveload
    public final function load( $packet ){
        if ( !function_exists('gzinflate') ) throw new StandardException("GZip is required for reports");
        $this->input = self::decompact( $packet['input'] );
        $this->headers = self::decompact( $packet['headers'] );
	if ( isset($packet['summary']) && $packet['summary'] != '' ) $this->summary = self::decompact( $packet['summary'] );
	else $this->summary = array();
	if ( isset($packet['graphs']) && $packet['graphs'] != '' ) $this->graphs = self::decompact( $packet['graphs'] );
	else $this->graphs = array();
        $this->data = self::decompact( $packet['data'] );
    }
    

    private static function compact( $data ){
        if ( !function_exists('gzdeflate') ) throw new StandardException("GZip is required for reports");
        
        return base64_encode( gzdeflate(serialize($data), 9) );
    }
    private static function decompact( $data ){
        if ( !function_exists('gzinflate') ) throw new StandardException("GZip is required for reports");
        
        return unserialize( gzinflate( base64_decode($data) ));
    }
    
    public final function save(){
        return array(
            'input'=>self::compact( $this->input ),
            'headers'=>self::compact( $this->headers ),
            'data'=>self::compact( $this->data ),
            'graphs'=>self::compact( $this->graphs ),
	    'summary'=>self::compact( $this->summary ),
            'stats_generation_time'=>$this->generationTime
        );
    }
    
}
