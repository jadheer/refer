<?php 
class ES_MlThirty_discussion extends ESSource 
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

	public function createIndex()
	{
		try
		{
			$params['index']  = $this->index;
			if (!$this->es_indices()->exists($params))
			{
				$mapping = ['_source' 	=>	[	'enabled'			=>	true],
												'properties'=>	[	'discussion_id'		=>	['type' => 'integer'],
																	'employee_id'		=>	['type' => 'integer'],
																	'description'		=>	['type' => 'text'],
																	'created_on' 		=> 	['type' => 'date', 'format'=>'yyyy-MM-dd HH:mm:ss'],
																	'enabled'			=>	['type' => 'integer'],
																]];
				
				$params['body']['settings']					=	['index.mapping.ignore_malformed' => true, 'index.max_inner_result_window' => 500000];
				$params['body']['mappings'][$this->type]	=	$mapping;
				return $this->es_indices()->create($params);
			}
			return false;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function insert($object){
		try
		{
			$params = [];

			
			$params['body']  		= 	[	'discussion_id'		=>	(int)$object->discussion_id,
											'employee_id'		=>	(int)$object->employee_id,
											'description'		=>	(string)$this->common->entityEncode($object->description),
											'created_on'		=>	(string)$object->created_on,
											'enabled'			=>	(int)1];
									
			$params['index']  		= 	$this->index;
			$params['type']  		= 	$this->type;
			$params['id']    		= 	(int)$object->discussion_id;
			return $this->es_index($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	public function getDiscussions($object){
		try{
			$params = [];
			$params['from']		=	$object->start??0;
			$params['size']  	= 	$object->end??1000;
			$params['index'] 	= 	$this->index;
			
			$params['body']['query']['bool']	=	['must' => [['term' => ['enabled'=>(int)1]]]];
			$params['body']['sort']				=	[['discussion_id' => ['order' => 'desc']]];
			
			$results							=	$this->es_search($params);
			$robjects	=	[];
			$empIds		=	[];
			if ($results['hits']['total']>0):
				foreach ($results['hits']['hits'] as $obj):
					if (!empty($obj['_source'])):
						$obj						=	(object)$obj['_source'];
						$object						=	new stdClass();
						$object->discussion_id		=	(int)$obj->discussion_id;
						$object->description		=	(string)$this->common->entityDecode($obj->description);
						$object->created_on			=	(string)$obj->created_on;
						$object->employee			=	(object)['employee_id' 	=> 	(int)$obj->employee_id];
						
						array_push($empIds, (string)$obj->employee_id);
						array_push($robjects, $object);
					endif;
				endforeach;
			endif;
			
			$posts		=	[];
			$empIds		=	array_unique($empIds);
			if (!empty($empIds)):
				$ES_Employee	=	new ES_Employee();
				$objEmployees	=	$ES_Employee->getEmployeeByIds($empIds);
				
				if (!empty($objEmployees)):
					foreach ($robjects as $robject):
						$Key	=	array_search($robject->employee->employee_id, array_column($objEmployees, 'employee_id'));
						if ($Key!==false):
							$robject->employee		=	(object)[	'employee_id' 		=> 	(int)$objEmployees[$Key]['employee_id'],
																	'first_name'		=>	(string)$this->common->entityDecode($objEmployees[$Key]['first_name']),
																	'middle_name'		=>	(string)$this->common->entityDecode($objEmployees[$Key]['middle_name']),
																	'last_name'			=>	(string)$this->common->entityDecode($objEmployees[$Key]['last_name']),
																	'display_name'		=>	(string)$this->common->entityDecode($objEmployees[$Key]['display_name']),
																	'profile_picture'	=>	(object)[	'base_url'		=>	(string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
																										'image_path'	=>	(string)$objEmployees[$Key]['profile_picture']]];
							array_push($posts,$robject);
						endif;
					endforeach;
				endif;
			endif;
			return $posts;
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