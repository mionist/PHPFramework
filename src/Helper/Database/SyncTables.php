<?php

class Helper_Database_SyncTables implements StandardObservable{
    
    /**
     *
     * @var Dbx
     */
    private $db1;
    
    /**
     *
     * @var Dbx 
     */
    private $db2;
    
    private $t1;
    private $t2;
    
    public function __construct( $table_from, $table_to, $db_from = NULL, $db_to = NULL ) {
	if ( !isset( $db_from ) ) $db_from = Core::getDatabase();
	if ( !isset( $db_to ) ) $db_to = Core::getDatabase();
	
	$this->db1 = $db_from;
	$this->db2 = $db_to;
	$this->t1 = $table_from;
	$this->t2 = $table_to;
    }
    
    public function sync( $start = 0, $update = TRUE, $insert = TRUE, $debug = FALSE ){
	if ( isset($this->log) ) $this->log->notify ("Starting sync from {$this->t1} into {$this->t2}");
	$list = array(
	    'update'=>array(),
	    'insert'=>array()
	);
	
	// Считаем количество
	$this->db1->getBuilder('plain')
		->select( $this->t1, 'count(*) FROM ?? WHERE `id`>='.$start )
		->exec();
	$count = $this->db1->result->getValue('count(*)');
	if ( isset($this->log) ) $this->log->notify ("Found $count entries in {$this->t1}");

	$last = $start - 1; $j = 0; $start_unix = time();
	while ( TRUE ){
	    $j++;
	    if ( isset($this->log) ) $this->log->process ($j, $count, $start_unix);
	    $this->db1->getBuilder('plain')
		    ->select($this->t1, '* FROM ?? WHERE `id`>? ORDER BY `id` ASC LIMIT 1', array($last))
		    ->exec();
	    if ( $this->db1->result->count() == 0 ) break;
	    $last = $this->db1->result->getValue('id');
	    $source = $this->db1->result->getRow();
	    
	    // Проверяем наличие
	    $this->db2->getBuilder('plain')
		    ->select($this->t2,'* FROM ?? WHERE `id` = ? LIMIT 1', array($last))
		    ->exec();
	    
	    if ( $this->db2->result->count() == 0 ){
		$list['insert'][] = $last;
		continue;
	    }
	    $target = $this->db2->result->getRow();
	    // Ищем разницу
	    foreach ( $source as $k=>$v ){
		if ( $v != $target[$k] ){
		    $list['update'][] = $last;
		    break;
		}
	    }
	}
	
	if ( $debug ) return $list;
	
	// Doing inserts
	if ( $insert ) foreach ( $list['insert'] as $id ){
	    if ( isset($this->log) ) $this->log->notify ("Inserting $id");
	    $this->db1->getBuilder('plain')
		    ->select($this->t1, '* FROM ?? WHERE `id`=?',array($id))
		    ->exec();
	    $data = $this->db1->result->getRow();
	    $this->db2->getBuilder('plain')
		    ->insert($this->t2, $data)
		    ->exec();
	}
	// Doing updates
	if ( $update ) foreach ( $list['update'] as $id ){
	    if ( isset($this->log) ) $this->log->notify ("Updating $id");
	    $this->db1->getBuilder('plain')
		    ->select($this->t1, '* FROM ?? WHERE `id`=?',array($id))
		    ->exec();
	    $data = $this->db1->result->getRow();
	    unset($data['id']);
	    $this->db2->getBuilder('plain')
		    ->update($this->t2, $data, (int) $id)
		    ->exec();
	}
	
    }

    /**
     *
     * @var Helper_Observer_Abstract 
     */
    private $log = NULL;
    public function bindObserver(Helper_Observer_Abstract $w) {
	$this->log = $w;
    }
    
}