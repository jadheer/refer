<?php
class ES_Wall_post extends ESSource {
	
	var $common;
	public function __construct()
	{	parent::__construct();
	$this->index	=	str_replace('es_', '', strtolower(get_class($this))).'s';
	$this->type		=	str_replace('es_', '', strtolower(get_class($this)));
	$this->common			=	new Common();
	}
	
	public function __destruct()
	{	parent::__destruct();	}
	
	
	public function addPost($object){
		try
		{
			$params 	= 	[];
			
			$fobj					=	new stdClass();
			$fobj->geographies		=	(string)(!empty($object->geography_id))?$object->geography_id:'';
			$fobj->functions		=	(string)(!empty($object->function_id))?$object->function_id:'';
			$fobj->locations		=	(string)(!empty($object->location_id))?$object->location_id:'';
			$fobj->levels			=	(string)(!empty($object->level_id))?$object->level_id:'';
			$fobj->layers			=	(string)(!empty($object->layer_id))?$object->layer_id:'';
			
			$params['body']  		= 	[	'post_id'		=>	(int)$object->post_id,
											'employee_id'	=>	(int)$object->employee_id,
											'description'	=>	(string)$this->common->entityEncode($object->description),
											'created_on'	=>	(string)$object->created_on,
											'updated_on'	=>	(string)$object->updated_on,
											'pub_datetime'	=>	(string)$object->pub_datetime,
											'published'		=>	(int)1,
											'enabled'		=>	(int)1,
											'comments'		=>	[],
											'likes'			=>	[],
											'filters'		=>  $fobj,
											'tagged_employees'	=>	!empty($object->es_tags)?$object->es_tags:[]
										];
									
			$params['index']  		= 	$this->index;
			$params['type']  		= 	$this->type;
			$params['id']    		= 	(int)$object->post_id;
			return  $this->es_index($params);
		}
		catch(Exception $e)
		{ $this->es_error($e);	}
	}
	
	public function addTaggedEmployee($object){
		try{
			$params = [];
			$params['index']  	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['id']    	= 	(int)$object->post_id;
			$doc 				= 	(int)$object->tag;
		
			$params['body']['script']['inline']	=	'ctx._source.tagged_employees.add(params.tags)';
			$params['body']['script']['params']	=	['tags' => $doc];
			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function updateField($objectId, $field, $value){
		try
		{
			$params = [];
			$params['body']	= [	'doc' => [	"$field" =>	$value ]];
			
			$params['index'] = 	$this->index;
			$params['type']  = 	$this->type;
			$params['id']    = 	$objectId;
			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	

	public function removeTaggedEmployee($object){
		try{
			$params = [];
			$params['index']  	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['id']    	= 	(int)$object->post_id;
			$doc 				= 	(int)$object->tag;
			
			$params['body']['script']['inline']	=	"ctx._source.tagged_employees.removeAll(Collections.singleton(params.tags))";
			$params['body']['script']['params']	=	[	'tags'	=>	$doc];
			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	
	public function getWallPosts($object){
		try{
			$params = [];
			$params['from']		=	(int)$object->start??0;
			$params['size']  	= 	(int)$object->end??1000;
			$params['index'] 	= 	$this->index;
			
			$params['body']['query']['bool']	=	['must' => [['term' => ['published'=>(int)1]]]];
			
			$params['body']['sort']	=	[	['pub_datetime'	=>	['order'	=>	'desc']],
											['post_id'		=>	['order'	=>	'desc']]];
			
			$results	=	$this->es_search($params);
			$arr		=	[];
			if ($results['hits']['total']>0):
				$empIds		=	[];$tag_arr	=	[]; $emp_arr = [];
				/*get the employee ids from community array */
				foreach ($results['hits']['hits'] as $post_obj):
					if (!empty($post_obj['_source'])):
						$row		=	(object)$post_obj['_source'];
						$tag_arr	=	array_merge($tag_arr,$row->tagged_employees);
						array_push($emp_arr,$row->employee_id);
					endif;
				endforeach;
				
				/* get employee information */
				$empIds			=	array_unique(array_merge($emp_arr,$tag_arr));
			
				$objEmployees	=	[];
				if (!empty($empIds)):
					$ES_Employee	=	new ES_Employee();
					$objEmployees	=	array_column($ES_Employee->getEmployeeByIds($empIds),NULL,"employee_id");
				endif;
				
				$ES_Geography	=	new ES_Geography();
				$ES_Location	=	new ES_Location();
				$ES_Level		=	new ES_Level();
				$ES_Function	=	new ES_Function();
				$ES_Layer		=	new ES_Layer();
				
				$ObjGeos	=	$ES_Geography->getGeographies();
				$Objlocs	=	$ES_Location->getLocations();
				$ObjLevs	=	$ES_Level->getLevels();
				$ObjFuncs	=	$ES_Function->getFunctions();
				$objLayers		=	$ES_Layer->getLayers();
				
				$ObjMaster	=	(object)['geographies'	=>	$ObjGeos, 'locations' => $Objlocs, 'levels' => $ObjLevs, 'functions' => $ObjFuncs , 'layers'  =>  $objLayers];
				
				foreach ($results['hits']['hits'] as $post_obj):
					if (!empty($post_obj['_source']) && !empty($objEmployees)):
						$row			=	(object)$post_obj['_source'];
						if (isset($objEmployees[$row->employee_id]) && $objEmployees[$row->employee_id]['enabled']==1):
							array_push($arr, $this->setOutput($row,$object,$objEmployees,$ObjMaster));
						endif;
					endif;
				endforeach;
			endif;
			
			return $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function getWallPostById($object){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']		=	$this->type;
			$params['id'] 		= 	(int)$object->post_id;
			$result				=	$this->es_get($params);
			
			if (!empty($result['_source'])):
				$objEmployees	=	[];
				$row		=	(object)$result['_source'];
				/* get employee information */
				$empIds			=	[];
				$row->tagged_employees	=	$row->tagged_employees??[];
				$empIds			=	array_unique(array_merge(array($row->employee_id),$row->tagged_employees));
				if (!empty($empIds)):
					$ES_Employee	=	new ES_Employee();
					$objEmployees	=	array_column($ES_Employee->getEmployeeByIds($empIds),NULL,"employee_id");
				endif;
				
				$ES_Geography	=	new ES_Geography();
				$ES_Location	=	new ES_Location();
				$ES_Level		=	new ES_Level();
				$ES_Function	=	new ES_Function();
				$ES_Layer		=	new ES_Layer();
				
				$ObjGeos		=	$ES_Geography->getGeographies();
				$Objlocs		=	$ES_Location->getLocations();
				$ObjLevs		=	$ES_Level->getLevels();
				$ObjFuncs		=	$ES_Function->getFunctions();
				$objLayers		=	$ES_Layer->getLayers();
				
				$ObjMaster		=	(object)['geographies'	=>	$ObjGeos, 'locations' => $Objlocs, 'levels' => $ObjLevs, 'functions' => $ObjFuncs , 'layers'  =>  $objLayers];
				return $this->setOutput($row,$object,$objEmployees,$ObjMaster);
			endif;
			return new stdClass();
			
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setOutput($row,$object,$objEmployees,$ObjMaster){
		$robject					=	new stdClass();
		$robject->post_id			=	(int)$row->post_id;
		$robject->description		=	(string)html_entity_decode($this->common->entityDecode($row->description), ENT_QUOTES);
		$robject->description		=	(string)$this->common->removeTagsLinkify($robject->description);
		$robject->published_on		=	(string)$row->pub_datetime;
		$robject->comment_count 	=	(int)count($row->comments);
		$robject->like_count 		=	(int)count($row->likes);
		$robject->liked 			=	(boolean)(in_array($object->employee_id,$row->likes));	
		$fobj						=	new stdClass();
		$fobj->geography_id			=	(string)(!empty($row->filters['geographies'])?$row->filters['geographies']:'');
		$fobj->location_id			=	(string)(!empty($row->filters['locations'])?$row->filters['locations']:'');
		$fobj->function_id			=	(string)(!empty($row->filters['functions'])?$row->filters['functions']:'');
		$fobj->level_id				=	(string)(!empty($row->filters['levels'])?$row->filters['levels']:'');
		$fobj->layer_id				=	(string)(!empty($row->filters['layers'])?$row->filters['layers']:'');
		$robject->filters			=	(object)$fobj;
		
		$robject->tags				=	[];

		foreach ($row->tagged_employees as $key => $tag):
			if(isset($objEmployees[$tag]) && $objEmployees[$tag]['enabled']==1):
				$emp					=	new stdClass();
				$emp->employee_id 		= 	(int)$objEmployees[$tag]['employee_id'];
				$first_name				=	$objEmployees[$tag]['first_name']?$this->common->entityDecode($objEmployees[$tag]['first_name'])." ":"";
				$middle_name			=	$objEmployees[$tag]['middle_name']?$this->common->entityDecode($objEmployees[$tag]['middle_name'])." ":"";
				$last_name				=	$objEmployees[$tag]['last_name']?$this->common->entityDecode($objEmployees[$tag]['last_name']):"";
				$emp->first_name		=	$objEmployees[$tag]['first_name']?$this->common->entityDecode($objEmployees[$tag]['first_name']):"";
				$emp->middle_name		=	$objEmployees[$tag]['middle_name']?$this->common->entityDecode($objEmployees[$tag]['middle_name']):"";
				$emp->last_name			=	$objEmployees[$tag]['last_name']?$this->common->entityDecode($objEmployees[$tag]['last_name']):"";
				$emp->employee_name		=	(string)$first_name.$middle_name.$last_name;
				$emp->employee_code		=	(string)$objEmployees[$tag]['employee_code'];
				$emp->profile_picture	=	(object)[	'base_url'		=>	(string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
														'image_path'	=>	(string)$objEmployees[$tag]['profile_picture']];
				
				$emp->geography	=	new stdClass();
				$Key	=	array_search($objEmployees[$tag]['geography_id'], array_column($ObjMaster->geographies, 'geography_id'));
				if ($Key!==false):
					$emp->geography	= (object)['geography_id' => (int)$ObjMaster->geographies[$Key]->geography_id,
												'title' => (string)$ObjMaster->geographies[$Key]->title];
				endif;
				
				$emp->location	=	new stdClass();
				$Key	=	array_search($objEmployees[$tag]['location_id'], array_column($ObjMaster->locations, 'location_id'));
				if ($Key!==false):
					$emp->location	= (object)[	'location_id' => (int)$ObjMaster->locations[$Key]->location_id,
												'title' => (string)$ObjMaster->locations[$Key]->title];
				endif;
				
				$emp->function	=	new stdClass();
				$Key	=	array_search($objEmployees[$tag]['function_id'], array_column($ObjMaster->functions, 'function_id'));
				if ($Key!==false):
					$emp->function	= (object)[	'function_id' => (int)$ObjMaster->functions[$Key]->function_id,
												'title' => (string)$ObjMaster->functions[$Key]->title];
				endif;
				
				$emp->level		=	new stdClass();
				$Key	=	array_search($objEmployees[$tag]['level_id'], array_column($ObjMaster->levels, 'level_id'));
				if ($Key!==false):
				$emp->level		= (object)[	'level_id' => (int)$ObjMaster->levels[$Key]->level_id,
											'title' => (string)$ObjMaster->levels[$Key]->title];
				endif;
				
				$emp->layer		=	new stdClass();
				$Key			=	array_search($objEmployees[$tag]['layer_id'], array_column($ObjMaster->layers, 'layer_id'));
				if ($Key!==false):
					$emp->layer	= 	(object)[	'layer_id' => (int)$ObjMaster->layers[$Key]->layer_id,
												'title' => (string)$ObjMaster->layers[$Key]->title];
				endif;
				
				array_push($robject->tags, $emp);
			endif;
		endforeach;
		$robject->employee	=	(object)[	'employee_id' 		=> 	(int)$objEmployees[$row->employee_id]['employee_id'],
											'first_name'		=>	(string)$this->common->entityDecode($objEmployees[$row->employee_id]['first_name']),
											'middle_name'		=>	(string)$this->common->entityDecode($objEmployees[$row->employee_id]['middle_name']),
											'last_name'			=>	(string)$this->common->entityDecode($objEmployees[$row->employee_id]['last_name']),
											'display_name'		=>	(string)$this->common->entityDecode($objEmployees[$row->employee_id]['display_name']),
											'profile_picture'	=>	(object)[	'base_url'		=>	(string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
																				'image_path'	=>	(string)$objEmployees[$row->employee_id]['profile_picture']]];
		return $robject;
	}
	
	public function refresh(){
		try{
			$params = [];
			$params['index']  	= 	$this->index;
			return $this->es_indices_refresh($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
}
?>