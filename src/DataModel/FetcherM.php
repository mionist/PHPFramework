<?php
/**
 * @todo
 * @internal
 * @ignore
 */
class DataModel_FetcherM extends DataModel_Fetcher{

    private $info = NULL;
    private $joins							= array();

    
    public function __construct( $table_name ){
        $this->table = $table_name;
        $this->table_alias = $table_name;
    }
    
    protected function lazyInitialization(){
            $this->castEvent( StandardEventReciever::FINALIZE_INIT );
            $this->fetch();
    }    
    
    public function addFilter( $key, $value ){
            $this->filters[] = array( $key, $value );
            return $this;
    }    
    
    public function setFields( $f, $escape = TRUE ){
	$this->fetch_fields_list = $f;
    }
    
    private function fetch(){
        $m = Mongo_Wrapper::getDB();
        
	$filters = $fields = array();
	if (is_array( $this->fetch_fields_list ) ) foreach ( $this->fetch_fields_list as $row ){
	    $fields[$row] = 1;
	}
	if (is_array($this->filters) && count( $this->filters ) > 0 ) foreach ( $this->filters as $row ){
	    if (is_array( $row ) ){
		// Простой фильтр
		$filters[$row[0]] = $row[1];
	    } elseif(is_object($row) && $row instanceof SQL_Helper_Abstract ){
		$filters = array_merge( $filters, $row->getMongoFilter() );
	    }
	}
        
	//var_dump( $filters ); exit;
	
        $cursor = $m->selectCollection($this->table)->find( $filters, $fields );
	
	
	
	// LIMITS
        if ( $this->limit > 0 ) $cursor->limit( $this->limit );
	
        if ( isset($this->ordering) && count( $this->ordering  > 0) ){
            $mo = array();
            foreach ( $this->ordering as $row ){
                $row[1] = strtolower($row[1]);
                if ( $row[1] == 'asc' ) $row[1] = 1;
                elseif ( $row[1] == 'desc' ) $row[1] = -1;
                $mo[] = array( $row[0]=>$row[1] );
            }
            $cursor->sort( $mo );
        }
	
	$count = $cursor->count();
	if ( $this->limit > 0 ){
	    $this->pages = ceil( $count / $this->limit );
	}
	
	$this->info = $cursor->info();
	
        $this->data = iterator_to_array( $cursor );
    }
    
     public function description() {
         return $this->info;
     }
}