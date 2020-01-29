<?php 
class ES_Department extends ESSource 
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


	public function getById($id){
		try
		{
			$params = [];
			$params['index']  	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['id']  		= 	(int)$id;
			
			
			$object	=	$this->es_get($params);
			if (!empty($object['_source'])):
				return (object)$object['_source'];
			endif;
			return [];
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function getDepartments(){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['match_all']	=	new stdClass();
			$params['body']['sort']	=	[['department_id'=>	['order' => 'asc']]];
				
			$objects	=	$this->es_search($params);
			$results	=	[];
			if ($objects['hits']['total']>0):
				foreach ($objects['hits']['hits'] as $object):
					if (!empty($object['_source'])):
						$object	=	(object)$object['_source'];
						$robject 	=	new stdClass();
						$robject->department_id		=	(int)$object->department_id;
						$robject->function_id		=	(int)$object->function_id;
						$robject->department_code 	=	(string)$object->department_code;
						$robject->department_name	=	(string)$object->department_name;
				
						array_push($results, $robject);
					endif;
				endforeach;
			endif;
			return $results;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	
	public function getFunctionId($department_name){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']['must']	=	['match'	=>	['department_name' => $department_name]];
			
			$object	=	$this->es_search($params);
			
			if (!empty($object['hits']['hits'][0]['_source'])):
				$row				=	(object)$object['hits']['hits'][0]['_source'];
				$robject 			=	new stdClass();
				$robject->department_id		=	(int)$row->department_id;
				$robject->department_code 	=	(string)$row->department_code;
				$robject->department_name	=	(string)$row->department_name;
				$robject->function_id		=	(int)$row->function_id;
				$es_function				=	new ES_Function();
				$function_obj				=	$es_function->getById($robject->function_id);
				$robject->function_title	=	(string)$function_obj->value;
				return $robject;
			endif;
			
			return new stdClass();
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	
	public function getDepartmentByHeadId($id) {
		try{
			$params = [];
			$params['from']		=	(int)0;
			$params['size']  	= 	(int)1000;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']	=	[	'must' => ['term' => ['department_head_id'=>$id ]]];
			
			$objects			=		$this->es_search($params);
			$results	=	[];
			if ($objects['hits']['total']>0):
				foreach ($objects['hits']['hits'] as $object):
					if (!empty($object['_source'])):
						$object	=	(object)$object['_source'];
						$robject 	=	new stdClass();
						$robject->department_id		=	(int)$object->department_id;
						$robject->function_id		=	(int)$object->function_id;
						$robject->department_code 	=	(string)$object->department_code;
						$robject->department_name	=	(string)$object->department_name;		
						array_push($results, $robject);
					endif;
				endforeach;
			endif;
			return $results;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
}
?>