<?php
class ES_Distribution  extends ESSource {
	var $index;
	var $type;
	public function __construct(){
		parent::__construct();
		$this->index	=	str_replace('es_', '', strtolower(get_class($this))).'s';
		$this->type		=	str_replace('es_', '', strtolower(get_class($this)));
	}
    public function __destruct()
	{	parent::__destruct();	}

	public function getSalesDistributionByEmployeeId($employee_id){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']['must']	=	['match'	=>	['employee_id' => $employee_id]];

			$object	=	$this->es_search($params);

			if (!empty($object['hits']['hits'][0]['_source'])):
				$row				=	(object)$object['hits']['hits'][0]['_source'];
				$robject 			=	new stdClass();
				$robject->sales_distribution_id	=	(int)$row->sales_distribution_id;
				return $robject;
			endif;

			return new stdClass();
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

    public function refresh(){
		try{
			$params = [];
			$params['index']  		= 	$this->index;
			return $this->es_indices_refresh($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
}
?>
