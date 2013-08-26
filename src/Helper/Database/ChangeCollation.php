<?php

class Helper_Database_ChangeCollation implements StandardObservable{
    /**
     *
     * @var Helper_Observer_Abstract 
     */
    private $l;
    
    public function process( $databaseName, $charset = 'utf8', $collate = 'utf8_general_ci', Dbx $connector = NULL ){
        if ( !isset( $connector ) ) $connector = Core::getDatabase ();
        
        $connector->getBuilder()
                ->show("TABLES FROM `$databaseName`")
                ->exec();
        
        foreach ($connector->result->exportColumn('Tables_in_'.$databaseName) as $table){
            if ( isset($this->l) ) $this->l->notify("Processing $table");
            $sql = "DEFAULT CHARACTER SET $charset COLLATE $collate";
            $connector->getBuilder('extended')
                    ->alter($table,$sql)
                    ->exec();
            
            if ( isset($this->l) ) $this->l->notify("Done $sql; Starting fields");
            
            $connector->getBuilder()
                    ->show("FULL COLUMNS FROM `$table`")
                    ->exec();
            foreach ( $connector->result->getData() as $row ){
                if ( $row['Collation'] == '' || $row['Collation'] == 'null' ) continue;
                $connector->getBuilder('extended')
                        ->alter( $table, "CHANGE `{$row['Field']}` `{$row['Field']}` {$row['Type']} CHARACTER SET $charset COLLATE $collate NOT NULL DEFAULT '{$row['Default']}'" )
                        ->exec();
            }
            
        }
        
    }

    public function bindObserver(Helper_Observer_Abstract $w) { $this->l = $w; }
    
}