<?php
class ES_Joinee_itemlist extends ESSource
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

	
	public function getEmployeeItems($object){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']		=	$this->type;
			$params['id']		=	(int)$object->employee_id;
			$result	=	$this->es_get($params);
			return !empty($result["_source"]["items"])?$result["_source"]["items"]:[];
		}
		catch(Exception $e)
		{  $this->es_error($e);	}
	}

	public function update($object){
		try
		{
			$params = [];
			$params['body']		= 	[	'doc' 	=> 	[	'employee_id'		=>	(int)$object->employee_id,
														'items'				=>	$object->es_items],
														'doc_as_upsert'	=>	true];
			
			$params['index'] = 	$this->index;
			$params['type']  = 	$this->type;
			$params['id']    = 	(int)$object->employee_id;
			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function refresh(){
		try
		{
			$params = [];
			$params['index']  		= 	$this->index;
			return $this->es_indices_refresh($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
}
?>