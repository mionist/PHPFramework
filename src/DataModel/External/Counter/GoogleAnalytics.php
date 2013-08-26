<?php
/**
 * @internal 
 */
class DataModel_External_Counter_GoogleAnalytics extends DataModel_Abstract{

    private static $paramsMapping = array(
	'hosts'=>'visitors',
	'sessions'=>'visitors',
	'hits'=>'pageviews',
	'pervisit'=>'pageviewsPerVisit',
	'avgtime'=>'avgTimeOnSite',
	'newvisits'=>'percentNewVisits'
    );
   
    private $params;
    private $defaultDim = NULL;

    private $email, $password, $counterId, $startDate, $endDate;
    
    public function __construct( $email, $password, $counterId, $startDate, $endDate = NULL, $params = NULL ){
	Core::Require3rdParty('com.google.api', 'gapi.class.php');
	if ( !isset($params) ) $params = array_unique (array_values ( self::$paramsMapping ) );
	$this->params = $params;
	
	if ( substr($counterId,0,3) == 'ga:' ) $counterId = substr ($counterId, 3);
	
	if ( !isset( $endDate ) ) $endDate = $startDate;
	$this->email = $email;
	$this->password = $password;
	$this->counterId = $counterId;
	$this->startDate = $startDate;
	$this->endDate = $endDate;
    }

    protected function lazyInitialization() {
	$ga = new gapi( $this->email, $this->password );
	$ga->requestReportData( $this->counterId, 'date', $this->params, NULL, NULL, $this->startDate, $this->endDate );
	
	$this->data = array();
	foreach( $ga->getResults() as $row ){
	    $dim = $row->getDimesions();
	    $dim = $dim['date'];
	    $this->data[$dim] = array();
	    if ( !isset( $this->defaultDim ) ) $this->defaultDim = $dim;
	    foreach ( $this->params as $k ){
		$this->data[$dim][$k] = $ga->__call('get'.$k,NULL);
	    }
	}
    }
    
    public function getForDate( $name, $row = NULL ){
	$this->initialize();
	if ( !isset($row) ) $row = $this->defaultDim;
	$i = $this[$row];
	return $i[ self::$paramsMapping[$name] ];
    }
    
}