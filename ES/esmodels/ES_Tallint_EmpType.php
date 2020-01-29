<?php
class ES_Tallint_EmpType extends ESSource
{
	var $index;
	var $type;
	
	public function __construct()
	{
		parent::__construct();
		$this->index	=	str_replace('es_', '', strtolower(get_class($this))).'s';
		$this->type		=	str_replace('es_', '', strtolower(get_class($this)));
	}
	
	
	public function __destruct()
	{	parent::__destruct();	}
	
	
	public function getTallintEmployeeType($title){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']['must']	=	['match'	=>	['title' => $title]];
			
			$object	=	$this->es_search($params);
			
			if (!empty($object['hits']['hits'][0]['_source'])):
				$row				=	(object)$object['hits']['hits'][0]['_source'];
				return (string)$row->type;
			endif;
			return "";
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	
}
?>